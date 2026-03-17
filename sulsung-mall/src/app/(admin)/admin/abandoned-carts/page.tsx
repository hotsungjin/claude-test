import { createClient } from '@/lib/supabase/server'
import { formatPrice, formatDateTime } from '@/utils/format'
import AbandonedCartActions from './AbandonedCartActions'

export default async function AdminAbandonedCartsPage() {
  const supabase = await createClient() as any

  const oneDayAgo = new Date(Date.now() - 24 * 3600000).toISOString()
  const twoDaysAgo = new Date(Date.now() - 48 * 3600000).toISOString()
  const sevenDaysAgo = new Date(Date.now() - 7 * 86400000).toISOString()

  // 장바구니에 상품이 있지만 최근 주문이 없는 회원
  const { data: abandonedCarts } = await supabase
    .from('carts')
    .select(`
      id, member_id, goods_id, qty, created_at, updated_at,
      members!inner(id, name, email, phone),
      goods!inner(id, name, price, sale_price, thumbnail_url, status)
    `)
    .not('member_id', 'is', null)
    .gte('created_at', sevenDaysAgo)
    .eq('goods.status', 'active')
    .order('created_at', { ascending: false })
    .limit(200)

  // 리마인드 발송 이력
  const { data: reminders } = await supabase
    .from('cart_reminders')
    .select('member_id, reminded_at')
    .gte('reminded_at', sevenDaysAgo)
    .order('reminded_at', { ascending: false })

  const reminderMap = new Map<string, string>()
  for (const r of (reminders ?? [])) {
    if (!reminderMap.has(r.member_id)) {
      reminderMap.set(r.member_id, r.reminded_at)
    }
  }

  // 회원별 그룹핑
  const memberMap = new Map<string, { member: any; items: any[]; lastReminded: string | null }>()
  for (const cart of (abandonedCarts ?? [])) {
    const mid = cart.member_id
    if (!memberMap.has(mid)) {
      memberMap.set(mid, {
        member: cart.members,
        items: [],
        lastReminded: reminderMap.get(mid) ?? null,
      })
    }
    memberMap.get(mid)!.items.push(cart)
  }

  const memberList = Array.from(memberMap.entries())
    .map(([memberId, data]) => ({
      memberId,
      ...data,
      totalAmount: data.items.reduce((s: number, c: any) => {
        const price = c.goods?.sale_price ?? c.goods?.price ?? 0
        return s + price * c.qty
      }, 0),
    }))
    .sort((a, b) => b.totalAmount - a.totalAmount)

  // 통계
  const totalMembers = memberList.length
  const totalItems = (abandonedCarts ?? []).length
  const totalValue = memberList.reduce((s, m) => s + m.totalAmount, 0)
  const remindedCount = memberList.filter(m => m.lastReminded).length

  // 리마인드 설정
  const { data: config } = await supabase
    .from('site_configs')
    .select('value')
    .eq('key', 'cart_reminder')
    .single()
  const settings = (config?.value as any) ?? { enabled: true, hours: 24, max_per_day: 100 }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">이탈 장바구니</h1>
        <span className={`text-xs px-3 py-1.5 rounded-full font-medium ${settings.enabled ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'}`}>
          자동 리마인드 {settings.enabled ? 'ON' : 'OFF'}
        </span>
      </div>

      {/* 요약 카드 */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이탈 회원</p>
          <p className="text-xl font-bold text-gray-900">{totalMembers}명</p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이탈 상품수</p>
          <p className="text-xl font-bold text-gray-900">{totalItems}건</p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">예상 매출</p>
          <p className="text-xl font-bold text-blue-700">{formatPrice(totalValue)}</p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">리마인드 발송</p>
          <p className="text-xl font-bold text-blue-600">{remindedCount}명</p>
        </div>
      </div>

      {/* 회원별 이탈 장바구니 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <span className="text-sm text-gray-500">최근 7일 기준</span>
        </div>
        <div className="divide-y divide-gray-50">
          {memberList.length === 0 && (
            <div className="px-6 py-12 text-center text-gray-400 text-sm">이탈 장바구니가 없습니다.</div>
          )}
          {memberList.map(({ memberId, member, items, totalAmount, lastReminded }) => (
            <div key={memberId} className="px-6 py-4">
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-3">
                  <div>
                    <span className="font-medium text-gray-900 text-sm">{member?.name ?? '알 수 없음'}</span>
                    <span className="text-xs text-gray-400 ml-2">{member?.phone ?? ''}</span>
                  </div>
                  {lastReminded && (
                    <span className="text-[10px] px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full">
                      리마인드 발송 ({formatDateTime(lastReminded)})
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-semibold text-blue-700">{formatPrice(totalAmount)}</span>
                  <AbandonedCartActions
                    memberId={memberId}
                    memberName={member?.name ?? '고객'}
                    phone={member?.phone}
                    goodsName={items[0]?.goods?.name ?? '상품'}
                    itemCount={items.length}
                  />
                </div>
              </div>
              <div className="flex gap-3 overflow-x-auto pb-1">
                {items.map((item: any) => (
                  <div key={item.id} className="flex-shrink-0 flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                    {item.goods?.thumbnail_url ? (
                      <img src={item.goods.thumbnail_url} alt="" className="w-8 h-8 rounded object-cover" />
                    ) : (
                      <div className="w-8 h-8 bg-gray-200 rounded" />
                    )}
                    <div>
                      <p className="text-xs text-gray-700 max-w-32 truncate">{item.goods?.name}</p>
                      <p className="text-[10px] text-gray-400">
                        {formatPrice(item.goods?.sale_price ?? item.goods?.price)} x {item.qty}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
