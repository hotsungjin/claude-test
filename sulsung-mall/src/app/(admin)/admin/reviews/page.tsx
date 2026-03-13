import { createClient } from '@/lib/supabase/server'
import { formatDateTime } from '@/utils/format'
import { Star } from 'lucide-react'

export default async function AdminReviewsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; rating?: string; best?: string; page?: string }>
}) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 20
  const from = (page - 1) * PAGE_SIZE

  let query = supabase
    .from('reviews')
    .select(`
      id, rating, title, content, is_best, is_visible, mileage_given, created_at,
      members(name),
      goods(name)
    `, { count: 'exact' })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  if (params.rating) query = query.eq('rating', Number(params.rating))
  if (params.best === 'true') query = query.eq('is_best', true)
  if (params.q) query = query.or(`content.ilike.%${params.q}%,title.ilike.%${params.q}%`)

  const { data: reviews, count } = await query
  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">리뷰 관리</h1>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-xl p-4 shadow-sm mb-6">
        <form className="flex flex-wrap gap-3">
          <input name="q" defaultValue={params.q} placeholder="내용 검색"
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-40 focus:outline-none focus:border-green-500" />
          <select name="rating" defaultValue={params.rating}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 평점</option>
            {[5, 4, 3, 2, 1].map(r => <option key={r} value={r}>{r}점</option>)}
          </select>
          <select name="best" defaultValue={params.best}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체</option>
            <option value="true">베스트 리뷰</option>
          </select>
          <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
            검색
          </button>
          {(params.q || params.rating || params.best) && (
            <a href="/admin/reviews" className="text-sm text-gray-500 self-center hover:underline">초기화</a>
          )}
        </form>
      </div>

      {/* 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100">
          <span className="text-sm text-gray-500">총 <strong>{count ?? 0}</strong>건</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[700px]">
            <thead className="border-b border-gray-100">
              <tr>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">작성일</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">작성자</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">상품</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">평점</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">내용</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">베스트</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">공개</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {(reviews ?? []).map((review: any) => (
                <tr key={review.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-xs text-gray-500">{formatDateTime(review.created_at).slice(0, 10)}</td>
                  <td className="px-4 py-3 text-gray-700">{review.members?.name ?? '-'}</td>
                  <td className="px-4 py-3 text-gray-600 text-xs max-w-[160px] truncate">{review.goods?.name ?? '-'}</td>
                  <td className="px-4 py-3 text-center">
                    <div className="flex items-center justify-center gap-0.5">
                      {Array.from({ length: 5 }).map((_, i) => (
                        <Star key={i} className={`w-3 h-3 ${i < review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-200'}`} />
                      ))}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-600 text-xs max-w-[200px]">
                    {review.title && <p className="font-medium text-gray-800 mb-0.5">{review.title}</p>}
                    <p className="truncate">{review.content}</p>
                  </td>
                  <td className="px-4 py-3 text-center">
                    {review.is_best && <span className="text-xs px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full">베스트</span>}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-xs px-2 py-1 rounded-full ${review.is_visible ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                      {review.is_visible ? '공개' : '비공개'}
                    </span>
                  </td>
                </tr>
              ))}
              {(reviews ?? []).length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-12 text-center text-gray-400 text-sm">리뷰가 없습니다.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="flex justify-center gap-1 py-4 border-t border-gray-50">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
              <a key={p} href={`/admin/reviews?page=${p}`}
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
