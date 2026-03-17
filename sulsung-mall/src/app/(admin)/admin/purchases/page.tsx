import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { AlertTriangle, TrendingDown, Package, ClipboardList, Plus } from 'lucide-react'
import PurchaseRecommendTable from './PurchaseRecommendTable'

export default async function PurchasesPage() {
  const supabase = await createClient() as any

  // 1) 최근 90일 주문 데이터에서 상품별 판매량 집계
  const ninetyDaysAgo = new Date(Date.now() - 90 * 86400000).toISOString()
  let salesData: any[] = []
  try {
    const res = await supabase.rpc('get_goods_sales_stats', { since_date: ninetyDaysAgo })
    salesData = res.data ?? []
  } catch {}


  // 2) 전체 상품 재고 현황
  const { data: goods } = await supabase
    .from('goods')
    .select('id, name, slug, stock, stock_alert_qty, thumbnail_url, status, category_id, categories(name)')
    .neq('status', 'deleted')
    .order('stock', { ascending: true })

  // 3) 발주서 현황
  const { data: orders, count: poCount } = await supabase
    .from('purchase_orders')
    .select('id, status, total_amount, created_at, supplier_id, suppliers(name)', { count: 'exact' })
    .neq('status', 'cancelled')
    .order('created_at', { ascending: false })
    .limit(10)

  // 4) 공급처 목록
  const { data: suppliers } = await supabase
    .from('suppliers')
    .select('id, name, lead_days')
    .eq('is_active', true)
    .order('name')

  // 판매 통계 맵 생성
  const salesMap = new Map<string, { total_sold: number; avg_daily: number }>()
  if (salesData) {
    for (const s of salesData) {
      salesMap.set(s.goods_id, { total_sold: s.total_sold, avg_daily: s.avg_daily })
    }
  }

  // AI 발주 추천 계산
  const DEFAULT_LEAD_DAYS = 3
  const SAFETY_DAYS = 2

  const recommendations = (goods ?? [])
    .map((g: any) => {
      const sales = salesMap.get(g.id)
      const avgDaily = sales?.avg_daily ?? 0
      const leadDays = DEFAULT_LEAD_DAYS
      const alertQty = g.stock_alert_qty || 5
      const reorderPoint = avgDaily > 0 ? Math.ceil(avgDaily * (leadDays + SAFETY_DAYS)) : alertQty
      const optimalStock = avgDaily > 0 ? Math.ceil(avgDaily * (leadDays + SAFETY_DAYS + 7)) : alertQty * 3
      const recommendQty = Math.max(0, optimalStock - g.stock)
      const daysUntilOut = avgDaily > 0 ? Math.floor(g.stock / avgDaily) : (g.stock <= alertQty ? 0 : 999)
      const isLow = g.stock <= alertQty
      // 발주 필요: 판매 데이터가 있으면 발주점 기준, 없으면 재고부족 기준
      const needsOrder = avgDaily > 0 ? g.stock <= reorderPoint : isLow

      return {
        ...g,
        avgDaily: Math.round(avgDaily * 10) / 10,
        totalSold90: sales?.total_sold ?? 0,
        reorderPoint,
        optimalStock,
        recommendQty,
        daysUntilOut,
        needsOrder,
        isLow,
      }
    })
    .sort((a: any, b: any) => {
      if (a.needsOrder && !b.needsOrder) return -1
      if (!a.needsOrder && b.needsOrder) return 1
      return a.daysUntilOut - b.daysUntilOut
    })

  const urgentCount = recommendations.filter((r: any) => r.needsOrder).length
  const lowStockCount = recommendations.filter((r: any) => r.isLow).length
  const activePoCount = (orders ?? []).filter((o: any) => o.status === 'ordered' || o.status === 'receiving').length

  const STATUS_LABEL: Record<string, string> = {
    draft: '작성중', ordered: '발주완료', receiving: '입고중', completed: '입고완료', cancelled: '취소',
  }
  const STATUS_COLOR: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-600',
    ordered: 'bg-blue-100 text-blue-700',
    receiving: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-blue-100 text-blue-700',
    cancelled: 'bg-red-100 text-red-600',
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">발주 관리</h1>
        <Link href="/admin/purchases/new"
          className="flex items-center gap-2 bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-800">
          <Plus className="w-4 h-4" /> 발주서 작성
        </Link>
      </div>

      {/* 요약 카드 */}
      <div className="grid grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
              <AlertTriangle className="w-5 h-5 text-red-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">발주 필요</p>
              <p className="text-xl font-bold text-red-600">{urgentCount}건</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">
              <TrendingDown className="w-5 h-5 text-orange-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">재고 부족</p>
              <p className="text-xl font-bold text-orange-600">{lowStockCount}건</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
              <ClipboardList className="w-5 h-5 text-blue-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">진행중 발주</p>
              <p className="text-xl font-bold text-blue-600">{activePoCount}건</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
              <Package className="w-5 h-5 text-blue-500" />
            </div>
            <div>
              <p className="text-xs text-gray-500">전체 상품</p>
              <p className="text-xl font-bold text-gray-800">{(goods ?? []).length}개</p>
            </div>
          </div>
        </div>
      </div>

      {/* AI 발주 추천 */}
      <div className="bg-white rounded-xl shadow-sm mb-6">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-gray-900">AI 발주 추천</h2>
            <p className="text-xs text-gray-400 mt-0.5">최근 90일 판매 데이터 기반 · 리드타임 {DEFAULT_LEAD_DAYS}일 + 안전재고 {SAFETY_DAYS}일</p>
          </div>
        </div>
        <PurchaseRecommendTable items={recommendations} />
      </div>

      {/* 최근 발주서 */}
      <div className="bg-white rounded-xl shadow-sm">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-lg font-bold text-gray-900">최근 발주서</h2>
          <span className="text-sm text-gray-400">총 {poCount ?? 0}건</span>
        </div>
        {(orders ?? []).length === 0 ? (
          <div className="py-12 text-center text-gray-400 text-sm">발주서가 없습니다</div>
        ) : (
          <table className="w-full text-sm">
            <thead className="border-b border-gray-100">
              <tr>
                <th className="px-6 py-3 text-left text-xs text-gray-500 font-medium">번호</th>
                <th className="px-6 py-3 text-left text-xs text-gray-500 font-medium">공급처</th>
                <th className="px-6 py-3 text-right text-xs text-gray-500 font-medium">금액</th>
                <th className="px-6 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
                <th className="px-6 py-3 text-left text-xs text-gray-500 font-medium">작성일</th>
                <th className="px-6 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {(orders ?? []).map((po: any) => (
                <tr key={po.id} className="hover:bg-gray-50">
                  <td className="px-6 py-3 font-medium text-gray-900">PO-{String(po.id).padStart(4, '0')}</td>
                  <td className="px-6 py-3 text-gray-700">{po.suppliers?.name ?? '-'}</td>
                  <td className="px-6 py-3 text-right text-gray-700">{(po.total_amount ?? 0).toLocaleString()}원</td>
                  <td className="px-6 py-3 text-center">
                    <span className={`text-xs px-2 py-1 rounded-full font-medium ${STATUS_COLOR[po.status]}`}>
                      {STATUS_LABEL[po.status]}
                    </span>
                  </td>
                  <td className="px-6 py-3 text-gray-500">{new Date(po.created_at).toLocaleDateString('ko-KR')}</td>
                  <td className="px-6 py-3 text-center">
                    <Link href={`/admin/purchases/${po.id}`} className="text-xs text-blue-600 hover:underline">상세</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  )
}
