'use client'

import { useState, useEffect, useRef } from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { ChevronLeft, Home, ShoppingCart, Heart, Star, Share2, Minus, Plus, X, Check } from 'lucide-react'
import { formatPrice, calcDiscountRate } from '@/utils/format'
import { cn } from '@/utils/cn'

interface Option {
  id: number
  name: string
  price_delta: number
  stock: number
  is_active: boolean
}

interface OptionGroup {
  id: number
  name: string
  goods_options: Option[]
}

const FREE_SHIPPING_THRESHOLD = 50000

export default function GoodsDetailClient({ goods, relatedGoods = [] }: { goods: any; relatedGoods?: any[] }) {
  const [selectedOptions, setSelectedOptions] = useState<Record<number, number>>({})
  const [qty, setQty] = useState(1)
  const [tab, setTab] = useState<'detail' | 'info' | 'review' | 'qna'>('detail')
  const [currentImage, setCurrentImage] = useState(0)
  const [showCartSheet, setShowCartSheet] = useState(false)
  const [showConfirmSheet, setShowConfirmSheet] = useState(false)

  const images: string[] = goods.thumbnail_url
    ? [goods.thumbnail_url, ...(Array.isArray(goods.images) ? goods.images.map((i: any) => i.url ?? i) : [])]
    : []

  const discountRate = goods.sale_price ? calcDiscountRate(goods.price, goods.sale_price) : 0
  const basePrice = goods.sale_price ?? goods.price
  const totalPrice = basePrice * qty

  const avgRating = goods.reviews?.length
    ? (goods.reviews.reduce((s: number, r: any) => s + r.rating, 0) / goods.reviews.length).toFixed(1)
    : null

  const reviewCount = goods.reviews?.length ?? 0

  const [wishlisted, setWishlisted] = useState(false)

  useEffect(() => {
    fetch('/api/v1/wishlist')
      .then(r => r.json())
      .then(data => {
        if (data.items?.some((w: any) => w.goods_id === goods.id)) setWishlisted(true)
      })
      .catch(() => {})
  }, [goods.id])

  async function toggleWishlist() {
    const res = await fetch('/api/v1/wishlist', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ goodsId: goods.id }),
    })
    if (res.ok) {
      const data = await res.json()
      setWishlisted(data.wishlisted)
    } else {
      alert('로그인이 필요합니다.')
    }
  }

  async function addToCart() {
    const res = await fetch('/api/v1/cart', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ goodsId: goods.id, qty }),
    })
    if (!res.ok) { const j = await res.json().catch(() => ({})); alert(j.error ?? '장바구니 담기에 실패했습니다.'); return }
    setShowCartSheet(false)
    setShowConfirmSheet(true)
    setQty(1)
  }

  // 터치 스와이프
  const touchStart = useRef(0)
  function onTouchStart(e: React.TouchEvent) { touchStart.current = e.touches[0].clientX }
  function onTouchEnd(e: React.TouchEvent) {
    const diff = touchStart.current - e.changedTouches[0].clientX
    if (Math.abs(diff) > 50) {
      if (diff > 0 && currentImage < images.length - 1) setCurrentImage(i => i + 1)
      if (diff < 0 && currentImage > 0) setCurrentImage(i => i - 1)
    }
  }

  // 무료배송까지 남은 금액
  const remainForFreeShipping = Math.max(0, FREE_SHIPPING_THRESHOLD - totalPrice)
  const shippingProgress = Math.min(100, (totalPrice / FREE_SHIPPING_THRESHOLD) * 100)

  return (
    <div className="flex flex-col min-h-full bg-white">
      {/* 스토어 헤더/바텀네비/풋터 숨기기 */}
      <style>{`
        [data-store-header], [data-bottom-nav], [data-store-footer] { display: none !important; }
        .flex-1.pb-\\[52px\\] { padding-bottom: 0 !important; }
      `}</style>

      {/* ── 상단 헤더 (컬리 스타일) ── */}
      <div className="sticky top-0 z-50 bg-white border-b" style={{ borderColor: '#f0f0f0' }}>
        <div className="flex items-center h-[48px] px-3 gap-2">
          <button onClick={() => history.back()} className="p-1 flex-shrink-0">
            <ChevronLeft className="w-6 h-6" style={{ color: '#333' }} />
          </button>
          <p className="flex-1 text-[15px] font-medium truncate text-left" style={{ color: '#333' }}>
            {goods.name}
          </p>
          <div className="flex items-center gap-1 flex-shrink-0">
            <Link href="/" className="p-1.5">
              <Home className="w-5 h-5" style={{ color: '#333' }} />
            </Link>
            <Link href="/cart" className="p-1.5">
              <ShoppingCart className="w-5 h-5" style={{ color: '#333' }} />
            </Link>
          </div>
        </div>

        {/* 탭 바 */}
        <div className="flex">
          {([
            { key: 'detail', label: '상품설명' },
            { key: 'info', label: '상세정보' },
            { key: 'review', label: `후기 ${reviewCount > 0 ? reviewCount.toLocaleString() : ''}` },
            { key: 'qna', label: '문의' },
          ] as const).map(t => (
            <button key={t.key} onClick={() => setTab(t.key)}
              className="flex-1 py-2.5 text-[14px] font-medium border-b-2 -mb-px"
              style={tab === t.key
                ? { borderColor: '#968774', color: '#968774', fontWeight: 700 }
                : { borderColor: 'transparent', color: '#aaa' }}>
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {/* ── 콘텐츠 영역 ── */}
      <div className="flex-1">
        {tab === 'detail' && (
          <>
            {/* 이미지 슬라이더 */}
            <div className="relative bg-gray-50" style={{ aspectRatio: '1/1' }}
              onTouchStart={onTouchStart} onTouchEnd={onTouchEnd}>
              {images[currentImage] ? (
                <Image src={images[currentImage]} alt={goods.name} fill className="object-cover" />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-[40px]">🥩</div>
              )}
              <button className="absolute top-3 right-3 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center">
                <Share2 className="w-4 h-4" style={{ color: '#555' }} />
              </button>
              {images.length > 1 && (
                <div className="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5">
                  {images.map((_, i) => (
                    <button key={i} onClick={() => setCurrentImage(i)}
                      className="w-[6px] h-[6px] rounded-full"
                      style={{ backgroundColor: i === currentImage ? '#333' : 'rgba(0,0,0,0.2)' }} />
                  ))}
                </div>
              )}
            </div>

            {/* 상품 정보 */}
            <div className="px-4 pt-5 pb-4">
              {reviewCount > 0 && (
                <button onClick={() => setTab('review')}
                  className="text-[13px] underline mb-2 block" style={{ color: '#888' }}>
                  후기 {reviewCount.toLocaleString()}건
                </button>
              )}
              {goods.categories?.name && (
                <p className="text-[12px] mb-1" style={{ color: '#999' }}>
                  설성목장 · {goods.categories.name}
                </p>
              )}
              <h1 className="text-[18px] font-bold leading-snug mb-1" style={{ color: '#333' }}>
                {goods.name}
              </h1>
              {goods.summary && (
                <p className="text-[13px] mb-1" style={{ color: '#999' }}>{goods.summary}</p>
              )}
              <p className="text-[12px] mb-4" style={{ color: '#b5b5b5' }}>
                원산지: 국내산 (경기도 이천)
              </p>

              <div className="mb-4">
                {discountRate > 0 && (
                  <div className="flex items-baseline gap-2 mb-0.5">
                    <span className="text-[28px] font-extrabold" style={{ color: '#e84a3b' }}>{discountRate}%</span>
                    <span className="text-[28px] font-extrabold" style={{ color: '#333' }}>
                      {basePrice.toLocaleString()}<span className="text-[18px]">원</span>
                    </span>
                    <span className="text-[14px] line-through" style={{ color: '#b5b5b5' }}>
                      {goods.price.toLocaleString()}원
                    </span>
                  </div>
                )}
                {!discountRate && (
                  <div className="flex items-baseline">
                    <span className="text-[28px] font-extrabold" style={{ color: '#333' }}>
                      {basePrice.toLocaleString()}<span className="text-[18px]">원</span>
                    </span>
                  </div>
                )}
                <p className="text-[12px] mt-1" style={{ color: '#999' }}>
                  마일리지 {Math.floor(basePrice * (goods.mileage_rate ?? 1) / 100)}P 적립
                </p>
              </div>

              <div className="border-t pt-4 space-y-2" style={{ borderColor: '#f0f0f0' }}>
                <div className="flex text-[13px]">
                  <span className="w-[60px] flex-shrink-0" style={{ color: '#999' }}>배송</span>
                  <span style={{ color: '#333' }}>택배배송</span>
                </div>
                <div className="flex text-[13px]">
                  <span className="w-[60px] flex-shrink-0" style={{ color: '#999' }}>배송비</span>
                  <span style={{ color: '#333' }}>3,000원 (50,000원 이상 무료)</span>
                </div>
              </div>
            </div>

            {/* 상품 상세 설명 (HTML) */}
            <div className="border-t" style={{ borderColor: '#f0f0f0' }}>
              <div className="px-4 py-6 prose prose-sm max-w-none text-[13px]"
                dangerouslySetInnerHTML={{ __html: goods.description ?? '<p style="color:#aaa;text-align:center;padding:40px 0">상품 상세 정보가 없습니다.</p>' }} />
            </div>

            {/* 관련 상품 */}
            {relatedGoods.length > 0 && (
              <div className="border-t px-4 py-5" style={{ borderColor: '#f0f0f0' }}>
                <h3 className="text-[15px] font-bold mb-4" style={{ color: '#333' }}>함께 보면 좋은 상품</h3>
                <div className="flex gap-3 overflow-x-auto scrollbar-hide pb-1">
                  {relatedGoods.map((item: any) => {
                    const hasDiscount = item.sale_price && item.sale_price < item.price
                    const displayPrice = item.sale_price ?? item.price
                    return (
                      <Link key={item.id} href={`/goods/${item.slug}`} className="flex-shrink-0 w-[130px]">
                        <div className="relative w-[130px] h-[130px] rounded-lg overflow-hidden bg-gray-100 mb-2">
                          {item.thumbnail_url ? (
                            <Image src={item.thumbnail_url} alt={item.name} fill className="object-cover" />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center text-[11px]" style={{ color: '#ccc' }}>No Image</div>
                          )}
                        </div>
                        <p className="text-[12px] line-clamp-2 leading-tight mb-1" style={{ color: '#333' }}>{item.name}</p>
                        <div className="flex items-center gap-1">
                          {hasDiscount && (
                            <span className="text-[12px] font-bold" style={{ color: '#e84a3b' }}>
                              {calcDiscountRate(item.price, item.sale_price)}%
                            </span>
                          )}
                          <span className="text-[13px] font-bold" style={{ color: '#333' }}>{formatPrice(displayPrice)}</span>
                        </div>
                      </Link>
                    )
                  })}
                </div>
              </div>
            )}
          </>
        )}

        {tab === 'info' && (
          <div className="px-4 py-6">
            <h3 className="text-[15px] font-bold mb-4" style={{ color: '#333' }}>상세정보</h3>
            <table className="w-full text-[13px]">
              <tbody>
                {[
                  ['상품명', goods.name],
                  ['원산지', '국내산 (경기도 이천)'],
                  ['배송방법', '택배배송'],
                  ['배송비', '3,000원 (50,000원 이상 무료)'],
                  ['포장타입', '냉장/냉동'],
                ].map(([label, value]) => (
                  <tr key={label} className="border-b" style={{ borderColor: '#f5f5f5' }}>
                    <td className="py-3 w-[100px]" style={{ color: '#999' }}>{label}</td>
                    <td className="py-3" style={{ color: '#333' }}>{value}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {tab === 'review' && (
          <div className="px-4 py-5">
            {reviewCount > 0 && avgRating && (
              <div className="flex items-center gap-3 pb-4 mb-4 border-b" style={{ borderColor: '#f0f0f0' }}>
                <div className="text-center">
                  <p className="text-[32px] font-bold" style={{ color: '#333' }}>{avgRating}</p>
                  <div className="flex gap-0.5 mt-1">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star key={i} className={cn('w-4 h-4', i < Math.round(Number(avgRating)) ? 'fill-yellow-400 text-yellow-400' : 'text-gray-200')} />
                    ))}
                  </div>
                </div>
                <p className="text-[13px]" style={{ color: '#999' }}>{reviewCount.toLocaleString()}건의 리뷰</p>
              </div>
            )}
            {reviewCount === 0 ? (
              <p className="text-center py-16 text-[13px]" style={{ color: '#aaa' }}>아직 리뷰가 없습니다.</p>
            ) : (
              goods.reviews.map((review: any) => (
                <div key={review.id} className="border-b py-4" style={{ borderColor: '#f0f0f0' }}>
                  <div className="flex items-center gap-1 mb-1">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star key={i} className={cn('w-3 h-3', i < review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-200')} />
                    ))}
                  </div>
                  <p className="text-[12px] mb-2" style={{ color: '#b5b5b5' }}>
                    {review.members?.name} · {new Date(review.created_at).toLocaleDateString('ko-KR')}
                  </p>
                  {review.title && <p className="text-[14px] font-medium mb-1" style={{ color: '#333' }}>{review.title}</p>}
                  <p className="text-[13px] leading-relaxed" style={{ color: '#666' }}>{review.content}</p>
                </div>
              ))
            )}
          </div>
        )}

        {tab === 'qna' && (
          <div className="px-4 py-16 text-center">
            <p className="text-[14px] mb-2" style={{ color: '#333' }}>궁금한 점이 있으신가요?</p>
            <p className="text-[13px] mb-6" style={{ color: '#aaa' }}>상품에 대해 궁금한 점을 문의해주세요.</p>
            <Link href="/mypage/inquiries/new"
              className="inline-block px-8 py-3 rounded-lg text-[14px] font-medium text-white"
              style={{ backgroundColor: '#968774' }}>
              문의 작성하기
            </Link>
          </div>
        )}
      </div>

      {/* ── 하단 고정 구매 버튼 ── */}
      <div className="sticky bottom-0 z-40 px-4 py-2 bg-white border-t"
        style={{ borderColor: '#f0f0f0' }}>
        <div className="flex gap-2">
          <button onClick={toggleWishlist}
            className="flex-shrink-0 w-[52px] h-[52px] flex items-center justify-center rounded-lg border"
            style={{ borderColor: '#e5e5e5' }}>
            <Heart className={cn('w-6 h-6', wishlisted ? 'fill-red-500 text-red-500' : '')} style={wishlisted ? {} : { color: '#d5d5d5' }} />
          </button>
          <button onClick={() => { setQty(1); setShowCartSheet(true) }}
            className="flex-1 flex items-center justify-center h-[52px] rounded-lg font-bold text-[16px] text-white"
            style={{ backgroundColor: '#968774' }}>
            장바구니 담기
          </button>
        </div>
      </div>

      {/* ══════ 장바구니 담기 바텀시트 ══════ */}
      {showCartSheet && (
        <div className="fixed inset-0 z-[100]" onClick={() => setShowCartSheet(false)}>
          {/* 딤 */}
          <div className="absolute inset-0 bg-black/40" />
          {/* 시트 */}
          <div className="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl animate-slide-up"
            onClick={e => e.stopPropagation()}>
            {/* 핸들 */}
            <div className="flex justify-center pt-3 pb-2">
              <div className="w-10 h-1 rounded-full bg-gray-300" />
            </div>

            {/* 상품 정보 */}
            <div className="flex items-center gap-3 px-5 pb-4 border-b" style={{ borderColor: '#f0f0f0' }}>
              <div className="relative w-[56px] h-[56px] rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
                {goods.thumbnail_url && (
                  <Image src={goods.thumbnail_url} alt={goods.name} fill className="object-cover" />
                )}
              </div>
              <p className="text-[15px] font-medium line-clamp-2" style={{ color: '#333' }}>{goods.name}</p>
            </div>

            {/* 상품명 + 가격 + 수량 */}
            <div className="px-5 py-5">
              <p className="text-[14px] mb-4" style={{ color: '#333' }}>{goods.name}</p>
              <div className="flex items-center justify-between">
                <p className="text-[18px] font-bold" style={{ color: '#333' }}>
                  {basePrice.toLocaleString()}<span className="text-[14px]">원</span>
                </p>
                <div className="flex items-center border rounded-lg" style={{ borderColor: '#e5e5e5' }}>
                  <button onClick={() => setQty(q => Math.max(1, q - 1))}
                    className="w-10 h-10 flex items-center justify-center"
                    style={{ color: qty <= 1 ? '#ddd' : '#333' }}>
                    <Minus className="w-4 h-4" />
                  </button>
                  <span className="w-10 text-center text-[15px] font-medium" style={{ color: '#333' }}>{qty}</span>
                  <button onClick={() => setQty(q => Math.min(goods.stock ?? 99, q + 1))}
                    className="w-10 h-10 flex items-center justify-center" style={{ color: '#333' }}>
                    <Plus className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            {/* 담기 버튼 */}
            <div className="px-5 pb-6">
              <button onClick={addToCart}
                className="w-full h-[54px] rounded-xl font-bold text-[16px] text-white"
                style={{ backgroundColor: '#968774' }}>
                총 {(basePrice * qty).toLocaleString()}원 장바구니 담기
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ══════ 장바구니 담기 완료 바텀시트 ══════ */}
      {showConfirmSheet && (
        <div className="fixed inset-0 z-[100]" onClick={() => setShowConfirmSheet(false)}>
          <div className="absolute inset-0 bg-black/40" />
          <div className="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl animate-slide-up"
            onClick={e => e.stopPropagation()}>
            <div className="flex justify-center pt-3 pb-2">
              <div className="w-10 h-1 rounded-full bg-gray-300" />
            </div>

            {/* 담기 완료 메시지 */}
            <div className="flex items-center justify-between px-5 py-4">
              <div className="flex items-center gap-2">
                <div className="w-6 h-6 rounded-full flex items-center justify-center" style={{ backgroundColor: '#968774' }}>
                  <Check className="w-4 h-4 text-white" />
                </div>
                <p className="text-[16px] font-bold" style={{ color: '#333' }}>장바구니에 상품을 담았습니다.</p>
              </div>
              <Link href="/cart"
                className="flex items-center gap-1 px-4 py-2 rounded-full border text-[13px] font-medium"
                style={{ borderColor: '#e5e5e5', color: '#333' }}>
                <ShoppingCart className="w-4 h-4" />
                바로가기
              </Link>
            </div>

            {/* 무료배송 프로그레스 */}
            <div className="px-5 pb-4">
              <div className="relative h-[6px] rounded-full overflow-hidden" style={{ backgroundColor: '#f0f0f0' }}>
                <div className="h-full rounded-full transition-all" style={{
                  width: `${shippingProgress}%`,
                  backgroundColor: '#968774',
                }} />
                {shippingProgress >= 100 && (
                  <div className="absolute right-0 top-1/2 -translate-y-1/2">
                    <Check className="w-4 h-4" style={{ color: '#968774' }} />
                  </div>
                )}
              </div>
              <p className="text-[13px] mt-2" style={{ color: '#666' }}>
                {remainForFreeShipping > 0 ? (
                  <><span className="font-bold" style={{ color: '#968774' }}>{remainForFreeShipping.toLocaleString()}원</span> 더 담으면 무료 배송!</>
                ) : (
                  <span className="font-bold" style={{ color: '#968774' }}>무료 배송 조건을 충족했습니다!</span>
                )}
              </p>
            </div>

            {/* 함께 구매한 상품 */}
            {relatedGoods.length > 0 && (
              <div className="px-5 pt-2 pb-3">
                <h4 className="text-[15px] font-bold mb-3" style={{ color: '#333' }}>다른 고객이 함께 구매한 상품</h4>
                <div className="flex gap-3 overflow-x-auto scrollbar-hide pb-1">
                  {relatedGoods.slice(0, 6).map((item: any) => {
                    const displayPrice = item.sale_price ?? item.price
                    return (
                      <Link key={item.id} href={`/goods/${item.slug}`} className="flex-shrink-0 w-[120px]"
                        onClick={() => setShowConfirmSheet(false)}>
                        <div className="relative w-[120px] h-[120px] rounded-lg overflow-hidden bg-gray-100 mb-2">
                          {item.thumbnail_url ? (
                            <Image src={item.thumbnail_url} alt={item.name} fill className="object-cover" />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center text-[11px]" style={{ color: '#ccc' }}>No Image</div>
                          )}
                        </div>
                        <p className="text-[12px] line-clamp-2 leading-tight mb-1" style={{ color: '#333' }}>{item.name}</p>
                        <span className="text-[13px] font-bold" style={{ color: '#333' }}>{formatPrice(displayPrice)}</span>
                      </Link>
                    )
                  })}
                </div>
              </div>
            )}

            {/* 쇼핑 계속하기 */}
            <div className="px-5 pb-6">
              <button onClick={() => setShowConfirmSheet(false)}
                className="w-full h-[50px] rounded-xl text-[15px] font-medium border"
                style={{ borderColor: '#e5e5e5', color: '#333' }}>
                쇼핑 계속하기
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 슬라이드 애니메이션 */}
      <style>{`
        @keyframes slide-up {
          from { transform: translateY(100%); }
          to { transform: translateY(0); }
        }
        .animate-slide-up {
          animation: slide-up 0.3s ease-out;
        }
      `}</style>
    </div>
  )
}
