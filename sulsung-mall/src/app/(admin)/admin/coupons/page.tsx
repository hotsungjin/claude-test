import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDateTime } from '@/utils/format'
import { Plus } from 'lucide-react'

function formatDiscount(coupon: any) {
  if (coupon.type === 'amount') return `${coupon.discount_amount?.toLocaleString()}원`
  if (coupon.type === 'rate') return `${coupon.discount_rate}%${coupon.max_discount ? ` (최대 ${coupon.max_discount.toLocaleString()}원)` : ''}`
  if (coupon.type === 'shipping') return '배송비 무료'
  return '-'
}

const TYPE_LABEL: Record<string, string> = {
  amount: '정액할인',
  rate: '정률할인',
  shipping: '배송비무료',
}

export default async function AdminCouponsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; type?: string; status?: string; page?: string }>
}) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 20
  const from = (page - 1) * PAGE_SIZE

  let query = supabase
    .from('coupons')
    .select('*', { count: 'exact' })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  if (params.q) query = query.ilike('name', `%${params.q}%`)
  if (params.type) query = query.eq('type', params.type)
  if (params.status === 'active') query = query.eq('is_active', true)
  if (params.status === 'inactive') query = query.eq('is_active', false)

  const { data: coupons, count } = await query
  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">쿠폰 관리</h1>
        <Link href="/admin/coupons/new"
          className="flex items-center gap-2 bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600">
          <Plus className="w-4 h-4" />
          쿠폰 생성
        </Link>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-xl p-4 shadow-sm mb-6">
        <form className="flex flex-wrap gap-3">
          <input name="q" defaultValue={params.q} placeholder="쿠폰명 검색"
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-40 focus:outline-none focus:border-blue-500" />
          <select name="type" defaultValue={params.type}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 유형</option>
            <option value="amount">정액할인</option>
            <option value="rate">정률할인</option>
            <option value="shipping">배송비무료</option>
          </select>
          <select name="status" defaultValue={params.status}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 상태</option>
            <option value="active">활성</option>
            <option value="inactive">비활성</option>
          </select>
          <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
            검색
          </button>
          {(params.q || params.type || params.status) && (
            <a href="/admin/coupons" className="text-sm text-gray-500 self-center hover:underline">초기화</a>
          )}
        </form>
      </div>

      {/* 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100">
          <span className="text-sm text-gray-500">총 <strong>{count ?? 0}</strong>개</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[800px]">
            <thead className="border-b border-gray-100">
              <tr>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">쿠폰명</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">코드</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">유형</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">할인</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">최소주문</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">사용/발급</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">유효기간</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {(coupons ?? []).map((coupon: any) => (
                <tr key={coupon.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{coupon.name}</td>
                  <td className="px-4 py-3">
                    {coupon.code
                      ? <span className="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{coupon.code}</span>
                      : <span className="text-xs text-gray-400">자동발급</span>}
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700">
                      {TYPE_LABEL[coupon.type] ?? coupon.type}
                    </span>
                  </td>
                  <td className="px-4 py-3 font-medium text-gray-800">{formatDiscount(coupon)}</td>
                  <td className="px-4 py-3 text-right text-gray-600 text-xs">
                    {coupon.min_order_amount > 0 ? `${coupon.min_order_amount.toLocaleString()}원 이상` : '제한없음'}
                  </td>
                  <td className="px-4 py-3 text-center text-xs text-gray-600">
                    {coupon.use_count} / {coupon.max_uses ?? '무제한'}
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-500">
                    {coupon.starts_at && <span>{formatDateTime(coupon.starts_at).slice(0, 10)}</span>}
                    {coupon.starts_at && coupon.expires_at && ' ~ '}
                    {coupon.expires_at && <span>{formatDateTime(coupon.expires_at).slice(0, 10)}</span>}
                    {!coupon.starts_at && !coupon.expires_at && <span className="text-gray-400">무제한</span>}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-xs px-2 py-1 rounded-full font-medium ${
                      coupon.is_active ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'
                    }`}>
                      {coupon.is_active ? '활성' : '비활성'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <Link href={`/admin/coupons/${coupon.id}`}
                      className="text-xs text-blue-600 hover:underline">
                      수정
                    </Link>
                  </td>
                </tr>
              ))}
              {(coupons ?? []).length === 0 && (
                <tr>
                  <td colSpan={9} className="px-4 py-12 text-center text-gray-400 text-sm">쿠폰이 없습니다.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="flex justify-center gap-1 py-4 border-t border-gray-50">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
              <a key={p} href={`/admin/coupons?page=${p}${params.type ? `&type=${params.type}` : ''}`}
                className={`w-8 h-8 flex items-center justify-center rounded text-xs font-medium ${
                  p === page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'
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
