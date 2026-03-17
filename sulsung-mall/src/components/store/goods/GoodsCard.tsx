'use client'

import { useState } from 'react'
import Link from 'next/link'
import { ShoppingCart } from 'lucide-react'
import { formatPrice, calcDiscountRate } from '@/utils/format'
import CartBottomSheet from './CartBottomSheet'

interface GoodsCardProps {
  goods: {
    id: string
    name: string
    slug: string
    price: number
    sale_price: number | null
    member_price?: number | null
    thumbnail_url: string | null
    sale_count?: number
    review_count?: number
  }
  variant?: 'grid' | 'scroll'
}

export default function GoodsCard({ goods, variant = 'grid' }: GoodsCardProps) {
  const [showSheet, setShowSheet] = useState(false)
  const discountRate = goods.sale_price ? calcDiscountRate(goods.price, goods.sale_price) : 0
  const displayPrice = goods.sale_price ?? goods.price
  const memberPrice = goods.member_price ?? null

  const isScroll = variant === 'scroll'

  return (
    <>
      <div style={isScroll ? { width: '155px', flexShrink: 0, scrollSnapAlign: 'start' } : undefined}>
        <Link href={`/goods/${goods.slug}`} className="block">
          {/* 이미지 — 4:5 비율 */}
          <div
            className="w-full overflow-hidden bg-gray-100 relative"
            style={{ aspectRatio: '4 / 5', borderRadius: '4px' }}
          >
            {goods.thumbnail_url ? (
              <img src={goods.thumbnail_url} alt={goods.name} className="w-full h-full object-cover" loading="lazy" />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-3xl bg-gray-50">🥩</div>
            )}
            {discountRate > 0 && (
              <span className="absolute top-2 left-2 text-[11px] font-bold text-white px-1.5 py-0.5 rounded"
                style={{ backgroundColor: '#e84a3b' }}>
                {discountRate}%
              </span>
            )}
          </div>
        </Link>

        {/* 담기 버튼 */}
        <button onClick={() => setShowSheet(true)}
          className="w-full flex items-center justify-center gap-1.5"
          style={{
            border: '1px solid #e5e5e5',
            borderRadius: '4px',
            color: '#555',
            marginTop: '6px',
            marginBottom: '6px',
            height: '32px',
            fontSize: '13px',
            fontWeight: 500,
          }}>
          <ShoppingCart style={{ width: 14, height: 14 }} />
          담기
        </button>

        {/* 상품 정보 */}
        <Link href={`/goods/${goods.slug}`} className="block">
          <p className="text-[13px] font-medium line-clamp-2 leading-snug mb-1" style={{ color: '#333' }}>
            {goods.name}
          </p>
          {discountRate > 0 && (
            <p className="text-[11px] line-through" style={{ color: '#aaa' }}>{formatPrice(goods.price)}</p>
          )}
          <div className="flex items-center gap-1">
            {discountRate > 0 && (
              <span className="text-[15px] font-extrabold" style={{ color: '#e84a3b' }}>
                {discountRate}%
              </span>
            )}
            <span className="text-[15px] font-bold" style={{ color: '#333' }}>{formatPrice(displayPrice)}</span>
          </div>
          {memberPrice && (
            <p className="text-[14px] font-bold mt-0.5" style={{ color: '#6B9E6B' }}>
              멤버십 {formatPrice(memberPrice)}
            </p>
          )}
          {/* 리뷰/판매 수 */}
          {((goods.review_count ?? goods.sale_count ?? 0) > 0) && (
            <p className="flex items-center gap-0.5 mt-1" style={{ fontSize: '12px', color: '#aaa' }}>
              <span>💬</span> {(goods.review_count ?? goods.sale_count ?? 0).toLocaleString()}+
            </p>
          )}
        </Link>
      </div>

      {showSheet && (
        <CartBottomSheet goods={goods} onClose={() => setShowSheet(false)} />
      )}
    </>
  )
}
