import { createClient } from '@/lib/supabase/server'
import CartAbandonmentClient from './CartAbandonmentClient'

export default async function CartAbandonmentPage() {
  const supabase = await createClient() as any

  // 24시간 전 ~ 7일 이내 장바구니에 담고 주문하지 않은 회원
  const oneDayAgo = new Date(Date.now() - 24 * 3600000).toISOString()
  const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 3600000).toISOString()

  const { data: carts } = await supabase
    .from('carts')
    .select(`
      id, member_id, goods_id, qty, created_at,
      members!inner(id, name, phone, email),
      goods!inner(id, name, price, sale_price, thumbnail_url, status)
    `)
    .not('member_id', 'is', null)
    .lte('created_at', oneDayAgo)
    .gte('created_at', sevenDaysAgo)
    .eq('goods.status', 'active')
    .order('created_at', { ascending: false })
    .limit(500)

  // 회원별로 그룹핑
  const memberMap = new Map<string, { member: any; items: any[]; latestAt: string }>()
  for (const cart of carts ?? []) {
    const mid = cart.member_id
    if (!memberMap.has(mid)) {
      memberMap.set(mid, { member: cart.members, items: [], latestAt: cart.created_at })
    }
    memberMap.get(mid)!.items.push({ ...cart.goods, qty: cart.qty })
  }

  // 최근 리마인드 발송 내역
  const { data: reminders } = await supabase
    .from('cart_reminders')
    .select('member_id, reminded_at')
    .gte('reminded_at', oneDayAgo)

  const remindedSet = new Set((reminders ?? []).map((r: any) => r.member_id))

  const abandonedList = Array.from(memberMap.entries()).map(([memberId, data]) => ({
    memberId,
    ...data,
    alreadyReminded: remindedSet.has(memberId),
  }))

  // 통계
  const totalMembers = abandonedList.length
  const totalItems = abandonedList.reduce((acc, m) => acc + m.items.length, 0)
  const totalValue = abandonedList.reduce((acc, m) =>
    acc + m.items.reduce((s: number, i: any) => s + (i.sale_price || i.price) * i.qty, 0), 0)
  const remindedCount = abandonedList.filter(m => m.alreadyReminded).length

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">장바구니 이탈 관리</h1>

      {/* 요약 카드 */}
      <div className="grid grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이탈 회원</p>
          <p className="text-2xl font-bold text-gray-900">{totalMembers}<span className="text-sm font-normal text-gray-400">명</span></p>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이탈 상품 수</p>
          <p className="text-2xl font-bold text-gray-900">{totalItems}<span className="text-sm font-normal text-gray-400">건</span></p>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">예상 매출</p>
          <p className="text-2xl font-bold text-blue-600">{totalValue.toLocaleString()}<span className="text-sm font-normal text-gray-400">원</span></p>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">발송 완료</p>
          <p className="text-2xl font-bold text-green-600">{remindedCount}<span className="text-sm font-normal text-gray-400">명</span></p>
        </div>
      </div>

      <CartAbandonmentClient members={abandonedList} />
    </div>
  )
}
