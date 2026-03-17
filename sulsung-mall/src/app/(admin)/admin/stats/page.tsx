import { createClient } from '@/lib/supabase/server'
import { formatPrice } from '@/utils/format'
import StatsCharts from './StatsCharts'

export default async function AdminStatsPage() {
  const supabase = await createClient() as any

  const now = new Date()
  const thisMonthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString()
  const lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString()
  const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59).toISOString()
  const thirtyDaysAgo = new Date(now.getTime() - 30 * 86400000).toISOString()

  const [
    { data: thisMonthOrders },
    { data: lastMonthOrders },
    { count: totalMembers },
    { count: thisMonthMembers },
    { data: topGoods },
    { data: recentOrders },
    { data: recentMembers },
    { data: categoryStats },
  ] = await Promise.all([
    supabase.from('orders').select('total_amount, status, created_at').gte('created_at', thisMonthStart),
    supabase.from('orders').select('total_amount, status').gte('created_at', lastMonthStart).lte('created_at', lastMonthEnd),
    supabase.from('members').select('*', { count: 'exact', head: true }),
    supabase.from('members').select('*', { count: 'exact', head: true }).gte('created_at', thisMonthStart),
    supabase.from('goods').select('name, sale_count, price, sale_price').eq('status', 'active').order('sale_count', { ascending: false }).limit(10),
    // 최근 30일 주문 (일별 매출 차트용)
    supabase.from('orders').select('total_amount, status, created_at').gte('created_at', thirtyDaysAgo),
    // 최근 30일 신규 회원
    supabase.from('members').select('created_at').gte('created_at', thirtyDaysAgo),
    // 카테고리별 매출
    supabase.from('order_items').select('total_price, goods(category_id, categories(name))').limit(500),
  ])

  const calcSales = (orders: any[]) =>
    (orders ?? []).filter(o => !['cancelled', 'returned'].includes(o.status)).reduce((s: number, o: any) => s + o.total_amount, 0)

  const thisMonthSales = calcSales(thisMonthOrders ?? [])
  const lastMonthSales = calcSales(lastMonthOrders ?? [])
  const salesGrowth = lastMonthSales > 0 ? Math.round(((thisMonthSales - lastMonthSales) / lastMonthSales) * 100) : 0
  const avgOrderValue = (thisMonthOrders ?? []).length > 0
    ? Math.round(thisMonthSales / (thisMonthOrders ?? []).filter(o => !['cancelled', 'returned'].includes(o.status)).length)
    : 0

  // 일별 매출 데이터 (30일)
  const dailySales: { date: string; amount: number; count: number }[] = []
  for (let i = 29; i >= 0; i--) {
    const d = new Date(now.getTime() - i * 86400000)
    const dateStr = `${d.getMonth() + 1}/${d.getDate()}`
    const dayStart = new Date(d.getFullYear(), d.getMonth(), d.getDate())
    const dayEnd = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1)
    const dayOrders = (recentOrders ?? []).filter((o: any) => {
      const t = new Date(o.created_at)
      return t >= dayStart && t < dayEnd && !['cancelled', 'returned'].includes(o.status)
    })
    dailySales.push({
      date: dateStr,
      amount: dayOrders.reduce((s: number, o: any) => s + o.total_amount, 0),
      count: dayOrders.length,
    })
  }

  // 일별 신규 회원 데이터 (30일)
  const dailyMembers: { date: string; count: number }[] = []
  for (let i = 29; i >= 0; i--) {
    const d = new Date(now.getTime() - i * 86400000)
    const dateStr = `${d.getMonth() + 1}/${d.getDate()}`
    const dayStart = new Date(d.getFullYear(), d.getMonth(), d.getDate())
    const dayEnd = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1)
    const count = (recentMembers ?? []).filter((m: any) => {
      const t = new Date(m.created_at)
      return t >= dayStart && t < dayEnd
    }).length
    dailyMembers.push({ date: dateStr, count })
  }

  // 주문 상태 분포
  const statusCounts: Record<string, number> = {}
  for (const o of (thisMonthOrders ?? [])) {
    statusCounts[o.status] = (statusCounts[o.status] ?? 0) + 1
  }

  // 카테고리별 매출
  const catSales: Record<string, number> = {}
  for (const item of (categoryStats ?? [])) {
    const catName = (item as any).goods?.categories?.name ?? '미분류'
    catSales[catName] = (catSales[catName] ?? 0) + ((item as any).total_price ?? 0)
  }
  const categorySalesArray = Object.entries(catSales)
    .map(([name, amount]) => ({ name, amount }))
    .sort((a, b) => b.amount - a.amount)
    .slice(0, 8)

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">통계</h1>

      {/* 이번달 요약 */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이번달 매출</p>
          <p className="text-xl font-bold text-gray-900">{formatPrice(thisMonthSales)}</p>
          <p className={`text-xs mt-1 ${salesGrowth >= 0 ? 'text-blue-600' : 'text-red-500'}`}>
            전월 대비 {salesGrowth >= 0 ? '+' : ''}{salesGrowth}%
          </p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이번달 주문</p>
          <p className="text-xl font-bold text-gray-900">{(thisMonthOrders ?? []).length}건</p>
          <p className="text-xs mt-1 text-gray-400">지난달 {(lastMonthOrders ?? []).length}건</p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">평균 주문액</p>
          <p className="text-xl font-bold text-gray-900">{formatPrice(avgOrderValue)}</p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">전체 회원</p>
          <p className="text-xl font-bold text-gray-900">{(totalMembers ?? 0).toLocaleString()}명</p>
        </div>
        <div className="bg-white rounded-xl p-5 shadow-sm">
          <p className="text-xs text-gray-500 mb-1">이번달 신규</p>
          <p className="text-xl font-bold text-gray-900">{thisMonthMembers ?? 0}명</p>
        </div>
      </div>

      {/* 차트 영역 (클라이언트) */}
      <StatsCharts
        dailySales={dailySales}
        dailyMembers={dailyMembers}
        statusCounts={statusCounts}
        categorySales={categorySalesArray}
      />

      {/* 인기 상품 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
        <div className="px-6 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-900">판매 상위 상품 TOP 10</h2>
        </div>
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">순위</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">상품명</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">판매수</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">판매가</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">추정 매출</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {(topGoods ?? []).map((goods: any, idx: number) => {
              const price = goods.sale_price ?? goods.price
              return (
                <tr key={goods.name} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-center">
                    <span className={`w-6 h-6 inline-flex items-center justify-center rounded-full text-xs font-bold ${
                      idx === 0 ? 'bg-yellow-400 text-white' :
                      idx === 1 ? 'bg-gray-300 text-white' :
                      idx === 2 ? 'bg-orange-300 text-white' : 'text-gray-400'
                    }`}>
                      {idx + 1}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-gray-800">{goods.name}</td>
                  <td className="px-4 py-3 text-right font-medium text-gray-900">{goods.sale_count?.toLocaleString() ?? 0}개</td>
                  <td className="px-4 py-3 text-right text-gray-600">{formatPrice(price)}</td>
                  <td className="px-4 py-3 text-right font-medium text-blue-700">{formatPrice(price * (goods.sale_count ?? 0))}</td>
                </tr>
              )
            })}
            {(topGoods ?? []).length === 0 && (
              <tr><td colSpan={5} className="px-4 py-12 text-center text-gray-400 text-sm">데이터가 없습니다.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
