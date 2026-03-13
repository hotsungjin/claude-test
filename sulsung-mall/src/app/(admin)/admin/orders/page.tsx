import { createClient } from '@/lib/supabase/server'
import { ORDER_STATUS_LABEL } from '@/utils/format'
import OrdersTableClient from './OrdersTableClient'

export default async function AdminOrdersPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; status?: string; page?: string; date?: string }>
}) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 30
  const from = (page - 1) * PAGE_SIZE

  let query = supabase
    .from('orders')
    .select(`
      id, order_no, status, total_amount, goods_amount, shipping_amount,
      recipient, phone, created_at, paid_at,
      members(name, email)
    `, { count: 'exact' })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  if (params.q) {
    query = query.or(`order_no.ilike.%${params.q}%,recipient.ilike.%${params.q}%,phone.ilike.%${params.q}%`)
  }
  if (params.status) query = query.eq('status', params.status)

  const { data: orders, count } = await query
  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">주문 관리</h1>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-xl p-4 shadow-sm mb-6">
        <form className="flex flex-wrap gap-3">
          <input name="q" defaultValue={params.q} placeholder="주문번호 / 수령인 / 연락처"
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-40 focus:outline-none focus:border-green-500" />
          <select name="status" defaultValue={params.status}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 상태</option>
            {Object.entries(ORDER_STATUS_LABEL).map(([v, l]) => (
              <option key={v} value={v}>{l}</option>
            ))}
          </select>
          <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
            검색
          </button>
          {(params.q || params.status) && (
            <a href="/admin/orders" className="text-sm text-gray-500 self-center hover:underline">초기화</a>
          )}
        </form>
      </div>

      {/* 통계 탭 */}
      <div className="flex gap-2 mb-4 overflow-x-auto">
        {[
          ['', '전체'],
          ['paid', '결제완료'],
          ['preparing', '배송준비중'],
          ['shipped', '배송중'],
          ['delivered', '배송완료'],
          ['cancel_requested', '취소요청'],
          ['return_requested', '반품요청'],
        ].map(([v, l]) => (
          <a key={v} href={v ? `/admin/orders?status=${v}` : '/admin/orders'}
            className={`flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border ${
              params.status === v || (!params.status && !v)
                ? 'bg-green-700 text-white border-green-700'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
            }`}>
            {l}
          </a>
        ))}
      </div>

      {/* 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <span className="text-sm text-gray-500">총 <strong>{count ?? 0}</strong>건</span>
        </div>
        <OrdersTableClient orders={orders ?? []} />

        {totalPages > 1 && (
          <div className="flex justify-center gap-1 py-4 border-t border-gray-50">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
              <a key={p} href={`/admin/orders?page=${p}${params.status ? `&status=${params.status}` : ''}`}
                className={`w-8 h-8 flex items-center justify-center rounded text-xs font-medium ${
                  p === page ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100'
                }`}>
                {p}
              </a>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
