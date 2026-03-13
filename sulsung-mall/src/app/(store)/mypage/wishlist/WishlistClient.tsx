'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { X } from 'lucide-react'
import { formatPrice, calcDiscountRate } from '@/utils/format'
import CartBottomSheet from '@/components/store/goods/CartBottomSheet'

export default function WishlistClient({ items: initialItems }: { items: any[] }) {
  const router = useRouter()
  const [items, setItems] = useState(initialItems)
  const [cartSheetGoods, setCartSheetGoods] = useState<any>(null)

  async function removeItem(goodsId: string) {
    const res = await fetch('/api/v1/wishlist', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ goodsId }),
    })
    if (res.ok) {
      setItems(prev => prev.filter(i => i.goods_id !== goodsId))
    }
  }

  return (
    <div className="px-4 py-4 space-y-3">
      {items.map((item: any) => {
        const g = item.goods
        if (!g) return null
        const hasDiscount = g.sale_price && g.sale_price < g.price
        const displayPrice = g.sale_price ?? g.price
        const isSoldout = g.status !== 'active'

        return (
          <div key={item.id} className="bg-white rounded-xl p-4 relative">
            {/* 삭제 버튼 */}
            <button onClick={() => removeItem(item.goods_id)}
              className="absolute top-3 right-3 w-6 h-6 flex items-center justify-center rounded-full"
              style={{ backgroundColor: '#f3f0ed' }}>
              <X className="w-3.5 h-3.5" style={{ color: '#999' }} />
            </button>

            <Link href={`/goods/${g.slug}`} className="flex gap-3">
              {/* 썸네일 */}
              <div className="relative w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                {g.thumbnail_url ? (
                  <img src={g.thumbnail_url} alt={g.name} className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-[11px]" style={{ color: '#ccc' }}>No Image</div>
                )}
                {isSoldout && (
                  <div className="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span className="text-white text-[11px] font-bold">품절</span>
                  </div>
                )}
              </div>

              {/* 상품 정보 */}
              <div className="flex-1 min-w-0 pr-6">
                <p className="text-[13px] font-medium line-clamp-2 leading-tight mb-1.5" style={{ color: isSoldout ? '#aaa' : '#333' }}>
                  {g.name}
                </p>
                <div className="flex items-center gap-1.5">
                  {hasDiscount && (
                    <span className="text-[12px] font-bold" style={{ color: '#e84a3b' }}>
                      {calcDiscountRate(g.price, g.sale_price)}%
                    </span>
                  )}
                  <span className="text-[14px] font-bold" style={{ color: isSoldout ? '#aaa' : '#222' }}>
                    {formatPrice(displayPrice)}
                  </span>
                </div>
              </div>
            </Link>

            {/* 장바구니 담기 */}
            {!isSoldout && (
              <button onClick={() => setCartSheetGoods(g)}
                className="w-full mt-3 py-2 rounded-lg text-[12px] font-medium border"
                style={{ borderColor: '#e0dbd5', color: '#968774' }}>
                장바구니 담기
              </button>
            )}
          </div>
        )
      })}

      {cartSheetGoods && (
        <CartBottomSheet goods={cartSheetGoods} onClose={() => setCartSheetGoods(null)} />
      )}
    </div>
  )
}
