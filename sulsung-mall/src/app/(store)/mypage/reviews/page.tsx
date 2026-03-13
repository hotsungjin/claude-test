import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'
import { formatDate } from '@/utils/format'
import { Star } from 'lucide-react'
import MypageHeader from '@/components/store/mypage/MypageHeader'

function StarRating({ rating }: { rating: number }) {
  return (
    <div className="flex gap-0.5">
      {Array.from({ length: 5 }, (_, i) => (
        <Star key={i} className={`w-3.5 h-3.5 ${i < rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-200'}`} />
      ))}
    </div>
  )
}

export default async function ReviewsPage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) redirect('/auth/login')

  const { data: reviews } = await supabase
    .from('reviews')
    .select('id, rating, content, images, is_best, created_at, goods(name, thumbnail_url, slug)')
    .eq('member_id', member.id)
    .order('created_at', { ascending: false })

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="내 리뷰" />

      {(reviews ?? []).length > 0 ? (
        <div className="space-y-4">
          {(reviews ?? []).map((review: any) => (
            <div key={review.id} className="bg-white rounded-xl p-5 shadow-sm">
              <div className="flex gap-4">
                {review.goods?.thumbnail_url && (
                  <img src={review.goods.thumbnail_url} alt="" className="w-16 h-16 rounded-lg object-cover flex-shrink-0" />
                )}
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 mb-1">{review.goods?.name ?? '상품'}</p>
                  <div className="flex items-center gap-2 mb-2">
                    <StarRating rating={review.rating} />
                    {review.is_best && (
                      <span className="text-xs bg-yellow-100 text-yellow-700 font-medium px-1.5 py-0.5 rounded">BEST</span>
                    )}
                    <span className="text-xs text-gray-400">{formatDate(review.created_at)}</span>
                  </div>
                  <p className="text-sm text-gray-700 leading-relaxed line-clamp-3">{review.content}</p>
                  {(review.images ?? []).length > 0 && (
                    <div className="flex gap-2 mt-3">
                      {review.images.slice(0, 4).map((img: string, idx: number) => (
                        <img key={idx} src={img} alt="" className="w-14 h-14 rounded-lg object-cover" />
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-xl p-12 text-center shadow-sm">
          <p className="text-gray-400">작성한 리뷰가 없습니다.</p>
          <p className="text-sm text-gray-400 mt-1">상품 구매 후 리뷰를 작성해보세요!</p>
        </div>
      )}
    </div>
  )
}
