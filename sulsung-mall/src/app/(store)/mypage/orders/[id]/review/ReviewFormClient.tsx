'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Image from 'next/image'
import { Star } from 'lucide-react'

interface OrderItem {
  id: string
  goods_id: string
  goods_name: string
  qty: number
  thumbnail_url: string | null
  reviewed: boolean
}

export default function ReviewFormClient({ items }: { items: OrderItem[] }) {
  const router = useRouter()
  const [reviews, setReviews] = useState<Record<string, { rating: number; title: string; content: string }>>(() =>
    Object.fromEntries(items.filter(i => !i.reviewed).map(i => [i.id, { rating: 5, title: '', content: '' }]))
  )
  const [submitting, setSubmitting] = useState<string | null>(null)
  const [done, setDone] = useState<Set<string>>(new Set())

  function setReview(itemId: string, key: string, value: any) {
    setReviews(prev => ({ ...prev, [itemId]: { ...prev[itemId], [key]: value } }))
  }

  async function handleSubmit(item: OrderItem) {
    const r = reviews[item.id]
    if (!r?.content) { alert('리뷰 내용을 입력하세요'); return }

    setSubmitting(item.id)
    const res = await fetch('/api/v1/reviews', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        goods_id: item.goods_id,
        order_item_id: item.id,
        rating: r.rating,
        title: r.title || null,
        content: r.content,
      }),
    })
    const data = await res.json()
    setSubmitting(null)

    if (data.error) { alert(data.error); return }
    setDone(prev => new Set([...prev, item.id]))
    alert('리뷰가 등록되었습니다! 100P 마일리지가 적립됩니다.')
  }

  const pendingItems = items.filter(i => !i.reviewed)
  const allDone = pendingItems.every(i => done.has(i.id))

  if (pendingItems.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center px-8 text-center" style={{ height: '50vh' }}>
        <p className="text-[40px] mb-3">✅</p>
        <p className="text-[15px] font-medium" style={{ color: '#555' }}>모든 상품의 리뷰를 작성하셨습니다</p>
        <button onClick={() => router.push('/mypage/orders')}
          className="mt-5 px-6 py-2.5 rounded-full text-[13px] font-medium text-white"
          style={{ backgroundColor: '#968774' }}>
          주문내역으로
        </button>
      </div>
    )
  }

  return (
    <div className="pb-8">
      {pendingItems.map(item => (
        <div key={item.id} className="bg-white mt-2 px-4 py-4">
          {/* 상품 정보 */}
          <div className="flex gap-3 mb-4">
            {item.thumbnail_url && (
              <div className="relative w-14 h-14 rounded-xl overflow-hidden flex-shrink-0" style={{ backgroundColor: '#f3f0ed' }}>
                <Image src={item.thumbnail_url} alt={item.goods_name} fill className="object-cover" />
              </div>
            )}
            <div>
              <p className="text-[13px] font-medium" style={{ color: '#333' }}>{item.goods_name}</p>
              <p className="text-[11px] mt-0.5" style={{ color: '#aaa' }}>× {item.qty}</p>
            </div>
          </div>

          {done.has(item.id) ? (
            <div className="text-center py-4">
              <p className="text-[13px] font-medium" style={{ color: '#968774' }}>✅ 리뷰 작성 완료!</p>
              <p className="text-[11px] mt-1" style={{ color: '#aaa' }}>100P 마일리지가 적립됩니다</p>
            </div>
          ) : (
            <>
              {/* 별점 */}
              <div className="mb-3">
                <p className="text-[12px] font-medium mb-2" style={{ color: '#555' }}>별점</p>
                <div className="flex gap-1.5">
                  {[1, 2, 3, 4, 5].map(n => (
                    <button key={n} type="button" onClick={() => setReview(item.id, 'rating', n)}>
                      <Star className={`w-7 h-7 ${n <= reviews[item.id]?.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-200'}`} />
                    </button>
                  ))}
                  <span className="text-[13px] ml-1 self-center font-medium" style={{ color: '#968774' }}>
                    {reviews[item.id]?.rating}점
                  </span>
                </div>
              </div>

              {/* 제목 */}
              <input
                value={reviews[item.id]?.title ?? ''}
                onChange={e => setReview(item.id, 'title', e.target.value)}
                placeholder="리뷰 제목 (선택)"
                className="w-full border rounded-xl px-3.5 py-2.5 text-[13px] focus:outline-none mb-2"
                style={{ borderColor: '#e0dbd5' }}
              />

              {/* 내용 */}
              <textarea
                value={reviews[item.id]?.content ?? ''}
                onChange={e => setReview(item.id, 'content', e.target.value)}
                placeholder="상품 리뷰를 작성해주세요 (필수)"
                rows={4}
                className="w-full border rounded-xl px-3.5 py-2.5 text-[13px] focus:outline-none resize-none mb-3"
                style={{ borderColor: '#e0dbd5' }}
              />

              <button
                onClick={() => handleSubmit(item)}
                disabled={submitting === item.id}
                className="w-full py-3 rounded-xl font-semibold text-[14px] text-white disabled:opacity-50"
                style={{ backgroundColor: '#968774' }}
              >
                {submitting === item.id ? '등록 중...' : '리뷰 등록 (+100P)'}
              </button>
            </>
          )}
        </div>
      ))}

      {allDone && (
        <div className="px-4 mt-4">
          <button onClick={() => router.push('/mypage/orders')}
            className="w-full py-3 rounded-xl font-semibold text-[14px]"
            style={{ backgroundColor: '#f3f0ed', color: '#666' }}>
            주문내역으로 돌아가기
          </button>
        </div>
      )}
    </div>
  )
}
