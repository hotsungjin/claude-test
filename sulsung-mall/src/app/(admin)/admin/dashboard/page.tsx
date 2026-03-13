import { createClient } from '@/lib/supabase/server'
import { formatPrice } from '@/utils/format'
import { ShoppingBag, Users, TrendingUp, Package } from 'lucide-react'

async function getStats(supabase: Awaited<ReturnType<typeof createClient>>) {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  const [
    { count: totalOrders },
    { count: todayOrders },
    { count: totalMembers },
    { data: salesData },
    { count: pendingOrders },
    { count: lowStockGoods },
  ] = await Promise.all([
    supabase.from('orders').select('*', { count: 'exact', head: true }),
    supabase.from('orders').select('*', { count: 'exact', head: true }).gte('created_at', today.toISOString()),
    supabase.from('members').select('*', { count: 'exact', head: true }),
    supabase.from('orders').select('total_amount').eq('status', 'confirmed'),
    supabase.from('orders').select('*', { count: 'exact', head: true }).eq('status', 'paid'),
    supabase.from('goods').select('*', { count: 'exact', head: true }).lte('stock', 5).eq('status', 'active'),
  ])

  const totalSales = ((salesData ?? []) as { total_amount: number }[]).reduce((s, o) => s + o.total_amount, 0)

  return { totalOrders, todayOrders, totalMembers, totalSales, pendingOrders, lowStockGoods }
}

export default async function DashboardPage() {
  const supabase = await createClient()
  const stats = await getStats(supabase)

  const { data: recentOrdersRaw } = await supabase
    .from('orders')
    .select('id, order_no, total_amount, status, created_at, members(name)')
    .order('created_at', { ascending: false })
    .limit(10)
  const recentOrders = recentOrdersRaw as unknown as {
    id: string; order_no: string; total_amount: number; status: string; created_at: string; members: { name: string } | null
  }[]

  const cards = [
    { label: '오늘 주문', value: `${stats.todayOrders ?? 0}건`, icon: ShoppingBag, color: 'text-blue-600 bg-blue-50' },
    { label: '처리 대기', value: `${stats.pendingOrders ?? 0}건`, icon: Package, color: 'text-orange-600 bg-orange-50' },
    { label: '총 회원수', value: `${(stats.totalMembers ?? 0).toLocaleString()}명`, icon: Users, color: 'text-purple-600 bg-purple-50' },
    { label: '누적 매출', value: formatPrice(stats.totalSales), icon: TrendingUp, color: 'text-green-600 bg-green-50' },
  ]

  const STATUS_LABEL: Record<string, string> = {
    pending_payment: '입금대기', paid: '결제완료', preparing: '준비중',
    shipped: '배송중', delivered: '배송완료', confirmed: '구매확정',
    cancelled: '취소', returned: '반품',
  }
  const STATUS_COLOR: Record<string, string> = {
    pending_payment: 'bg-yellow-100 text-yellow-700',
    paid: 'bg-blue-100 text-blue-700',
    preparing: 'bg-indigo-100 text-indigo-700',
    shipped: 'bg-purple-100 text-purple-700',
    delivered: 'bg-teal-100 text-teal-700',
    confirmed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    returned: 'bg-gray-100 text-gray-700',
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">대시보드</h1>

      {/* 통계 카드 */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {cards.map(({ label, value, icon: Icon, color }) => (
          <div key={label} className="bg-white rounded-xl p-5 shadow-sm">
            <div className="flex items-center justify-between mb-3">
              <span className="text-sm text-gray-500">{label}</span>
              <div className={`p-2 rounded-lg ${color}`}>
                <Icon className="w-4 h-4" />
              </div>
            </div>
            <p className="text-2xl font-bold text-gray-900">{value}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* 최근 주문 */}
        <div className="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 className="font-semibold text-gray-900">최근 주문</h2>
            <a href="/admin/orders" className="text-xs text-green-600 hover:underline">전체보기</a>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">주문번호</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">회원</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">금액</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {(recentOrders ?? []).map((order) => (
                <tr key={order.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-mono text-xs text-blue-600">
                    <a href={`/admin/orders/${order.id}`}>{order.order_no}</a>
                  </td>
                  <td className="px-4 py-3 text-gray-600">{(order.members as any)?.name ?? '비회원'}</td>
                  <td className="px-4 py-3 text-right font-medium">{formatPrice(order.total_amount)}</td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-xs px-2 py-1 rounded-full font-medium ${STATUS_COLOR[order.status] ?? 'bg-gray-100 text-gray-600'}`}>
                      {STATUS_LABEL[order.status] ?? order.status}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* 알림 패널 */}
        <div className="bg-white rounded-xl shadow-sm">
          <div className="px-6 py-4 border-b border-gray-100">
            <h2 className="font-semibold text-gray-900">주의 항목</h2>
          </div>
          <div className="p-6 space-y-4">
            <div className="flex items-start gap-3 p-3 bg-orange-50 rounded-lg">
              <span className="text-orange-500 text-lg">⚠</span>
              <div>
                <p className="text-sm font-medium text-orange-800">재고 부족 상품</p>
                <p className="text-xs text-orange-600">{stats.lowStockGoods ?? 0}개 상품 재고 5개 이하</p>
                <a href="/admin/goods?filter=low_stock" className="text-xs text-orange-700 underline mt-1 block">확인하기</a>
              </div>
            </div>
            <div className="flex items-start gap-3 p-3 bg-blue-50 rounded-lg">
              <span className="text-blue-500 text-lg">📦</span>
              <div>
                <p className="text-sm font-medium text-blue-800">결제완료 주문</p>
                <p className="text-xs text-blue-600">{stats.pendingOrders ?? 0}건 배송 처리 대기 중</p>
                <a href="/admin/orders?status=paid" className="text-xs text-blue-700 underline mt-1 block">처리하기</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
