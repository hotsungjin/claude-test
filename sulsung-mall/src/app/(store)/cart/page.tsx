'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import Image from 'next/image'
import { X, ShoppingBag, Minus, Plus, Check } from 'lucide-react'
import { formatPrice } from '@/utils/format'
import { BASE_SHIPPING_FEE, FREE_SHIPPING_THRESHOLD } from '@/constants'
import { MapPin } from 'lucide-react'

interface CartItem {
  id: string
  qty: number
  option_name: string | null
  goods: { id: string; name: string; slug: string; price: number; sale_price: number | null; thumbnail_url: string | null; stock: number; status: string }
}

interface DefaultAddress {
  id: string; name: string; address1: string; address2: string; label: string
}

export default function CartPage() {
  const router = useRouter()
  const [items, setItems] = useState<CartItem[]>([])
  const [selected, setSelected] = useState<Set<string>>(new Set())
  const [loading, setLoading] = useState(true)
  const [defaultAddress, setDefaultAddress] = useState<DefaultAddress | null>(null)

  useEffect(() => {
    fetch('/api/v1/cart').then(r => r.json()).then(d => {
      const list = d.items ?? []
      setItems(list)
      setSelected(new Set(list.map((i: CartItem) => i.id)))
      if (d.defaultAddress) setDefaultAddress(d.defaultAddress)
      setLoading(false)
    })
  }, [])

  async function updateQty(cartId: string, qty: number) {
    if (qty < 1) return
    await fetch('/api/v1/cart', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cartId, qty }) })
    setItems(prev => prev.map(i => i.id === cartId ? { ...i, qty } : i))
  }

  async function removeItem(cartId: string) {
    // 즉시 UI에서 제거 (낙관적 업데이트)
    setItems(prev => prev.filter(i => i.id !== cartId))
    setSelected(prev => { const n = new Set(prev); n.delete(cartId); return n })
    try {
      await fetch('/api/v1/cart', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cartId }) })
    } catch {}
  }

  async function removeSelected() {
    const ids = Array.from(selected)
    if (ids.length === 0) return
    // 즉시 UI에서 제거 (낙관적 업데이트)
    const removedSet = new Set(ids)
    setItems(prev => prev.filter(i => !removedSet.has(i.id)))
    setSelected(new Set())
    // 백그라운드에서 서버 삭제 처리
    for (const id of ids) {
      try {
        await fetch('/api/v1/cart', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cartId: id }) })
      } catch {}
    }
  }

  function toggleSelect(id: string) {
    setSelected(prev => {
      const n = new Set(prev)
      if (n.has(id)) n.delete(id); else n.add(id)
      return n
    })
  }

  function toggleAll() {
    if (selected.size === items.length) {
      setSelected(new Set())
    } else {
      setSelected(new Set(items.map(i => i.id)))
    }
  }

  const selectedItems = items.filter(i => selected.has(i.id))
  const originalTotal = selectedItems.reduce((s, i) => s + i.goods.price * i.qty, 0)
  const goodsTotal = selectedItems.reduce((s, i) => s + (i.goods.sale_price ?? i.goods.price) * i.qty, 0)
  const discount = originalTotal - goodsTotal
  const shippingFee = goodsTotal >= FREE_SHIPPING_THRESHOLD ? 0 : selectedItems.length > 0 ? BASE_SHIPPING_FEE : 0
  const total = goodsTotal + shippingFee
  const remaining = FREE_SHIPPING_THRESHOLD - goodsTotal
  const progress = Math.min(100, (goodsTotal / FREE_SHIPPING_THRESHOLD) * 100)

  if (loading) {
    return (
      <div className="flex items-center justify-center" style={{ height: '60vh' }}>
        <div className="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin" style={{ borderColor: '#968774', borderTopColor: 'transparent' }} />
      </div>
    )
  }

  if (items.length === 0) {
    return (
      <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
        <style>{`
          [data-store-header], [data-bottom-nav], [data-store-footer] { display: none !important; }
          .flex-1.pb-\\[52px\\] { padding-bottom: 0 !important; }
        `}</style>
        {/* 헤더 */}
        <div className="flex items-center h-[52px] px-4">
          <button onClick={() => router.push('/')} className="mr-3">
            <X className="w-6 h-6" style={{ color: '#333' }} />
          </button>
          <h1 className="text-[18px] font-bold" style={{ color: '#333' }}>장바구니</h1>
        </div>
        <div className="flex flex-col items-center justify-center px-8 text-center" style={{ height: '60vh' }}>
          <ShoppingBag className="w-14 h-14 mb-4" style={{ color: '#ddd' }} />
          <h2 className="text-[16px] font-bold mb-2" style={{ color: '#555' }}>장바구니가 비어있습니다</h2>
          <p className="text-[13px] mb-8" style={{ color: '#aaa' }}>원하는 상품을 장바구니에 담아보세요</p>
          <Link href="/goods"
            className="px-8 py-3 rounded-lg text-[14px] font-semibold text-white"
            style={{ backgroundColor: '#968774' }}>
            쇼핑 계속하기
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col" style={{ backgroundColor: '#f5f5f5', minHeight: '100vh' }}>
      <style>{`
        [data-store-header], [data-bottom-nav], [data-store-footer] { display: none !important; }
        .flex-1.pb-\\[52px\\] { padding-bottom: 0 !important; }
      `}</style>
      {/* ── 헤더 ── */}
      <div className="bg-white flex items-center h-[52px] px-4">
        <button onClick={() => router.push('/')} className="mr-3">
          <X className="w-6 h-6" style={{ color: '#333' }} />
        </button>
        <h1 className="text-[18px] font-bold" style={{ color: '#333' }}>장바구니</h1>
      </div>

      {/* ── 배송주소 ── */}
      {defaultAddress && (
        <div className="bg-white px-4 py-3 border-b" style={{ borderColor: '#f0f0f0' }}>
          <div className="flex items-start justify-between">
            <div className="flex items-start gap-2 flex-1 min-w-0">
              <MapPin className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: '#968774' }} />
              <div className="min-w-0">
                {defaultAddress.label && (
                  <span className="inline-block text-[11px] font-semibold px-1.5 py-0.5 rounded mb-1 text-white"
                    style={{ backgroundColor: '#968774' }}>
                    {defaultAddress.label}
                  </span>
                )}
                <p className="text-[14px] leading-snug truncate" style={{ color: '#333' }}>
                  {defaultAddress.address1} {defaultAddress.address2}
                </p>
              </div>
            </div>
            <Link href="/mypage/addresses"
              className="flex-shrink-0 ml-3 px-3 py-1.5 rounded-lg border text-[13px]"
              style={{ borderColor: '#e5e5e5', color: '#666' }}>
              변경
            </Link>
          </div>
        </div>
      )}

      {/* ── 전체선택 / 선택삭제 ── */}
      <div className="bg-white flex items-center justify-between px-4 py-3 border-b" style={{ borderColor: '#f0f0f0' }}>
        <button onClick={toggleAll} className="flex items-center gap-2">
          <div className="w-6 h-6 rounded flex items-center justify-center"
            style={{
              backgroundColor: selected.size === items.length ? '#333' : '#fff',
              border: selected.size === items.length ? 'none' : '2px solid #ddd',
            }}>
            {selected.size === items.length && <Check className="w-4 h-4 text-white" />}
          </div>
          <span className="text-[14px]" style={{ color: '#333' }}>전체선택 {selected.size}/{items.length}</span>
        </button>
        <button onClick={removeSelected}
          className="px-3 py-1.5 rounded-lg border text-[13px]"
          style={{ borderColor: '#e5e5e5', color: '#666' }}>
          선택삭제
        </button>
      </div>

      {/* ── 상품 목록 ── */}
      <div className="mt-2 bg-white rounded-xl mx-2 overflow-hidden">
        {items.map((item, idx) => {
          const unitPrice = item.goods.sale_price ?? item.goods.price
          const hasDiscount = item.goods.sale_price && item.goods.sale_price < item.goods.price
          const isSelected = selected.has(item.id)

          return (
            <div key={item.id} className="px-4 py-4 border-b" style={{ borderColor: '#f5f5f5' }}>
              {/* 체크 + 상품명 + X */}
              <div className="flex items-start gap-3 mb-3">
                <button onClick={() => toggleSelect(item.id)} className="flex-shrink-0 mt-0.5">
                  <div className="w-6 h-6 rounded flex items-center justify-center"
                    style={{
                      backgroundColor: isSelected ? '#333' : '#fff',
                      border: isSelected ? 'none' : '2px solid #ddd',
                    }}>
                    {isSelected && <Check className="w-4 h-4 text-white" />}
                  </div>
                </button>
                <Link href={`/goods/${item.goods.slug}`} className="flex-1 min-w-0">
                  <p className="text-[14px] leading-snug" style={{ color: '#333' }}>{item.goods.name}</p>
                </Link>
                <button onClick={() => removeItem(item.id)} className="flex-shrink-0">
                  <X className="w-5 h-5" style={{ color: '#ccc' }} />
                </button>
              </div>

              {/* 이미지 + 가격 + 수량 */}
              <div className="flex gap-3 ml-9">
                <Link href={`/goods/${item.goods.slug}`} className="flex-shrink-0">
                  <div className="relative w-[72px] h-[72px] rounded-lg overflow-hidden bg-gray-100">
                    {item.goods.thumbnail_url && (
                      <Image src={item.goods.thumbnail_url} alt={item.goods.name} fill className="object-cover" />
                    )}
                  </div>
                </Link>
                <div className="flex-1">
                  {/* 가격 */}
                  <div className="mb-2">
                    <div className="flex items-baseline gap-1.5">
                      <span className="text-[16px] font-bold" style={{ color: '#333' }}>
                        {formatPrice(unitPrice)}
                      </span>
                      {hasDiscount && (
                        <span className="text-[12px] line-through" style={{ color: '#b5b5b5' }}>
                          {formatPrice(item.goods.price)}
                        </span>
                      )}
                    </div>
                  </div>
                  {/* 수량 */}
                  <div className="inline-flex items-center rounded-lg" style={{ border: '1px solid #e5e5e5' }}>
                    <button onClick={() => updateQty(item.id, item.qty - 1)}
                      className="w-8 h-8 flex items-center justify-center"
                      style={{ color: item.qty <= 1 ? '#ddd' : '#333' }}>
                      <Minus className="w-3.5 h-3.5" />
                    </button>
                    <span className="w-8 text-center text-[14px] font-medium" style={{ color: '#333' }}>{item.qty}</span>
                    <button onClick={() => updateQty(item.id, item.qty + 1)}
                      disabled={item.qty >= item.goods.stock}
                      className="w-8 h-8 flex items-center justify-center disabled:opacity-30"
                      style={{ color: '#333' }}>
                      <Plus className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>

              {item.option_name && (
                <p className="text-[12px] mt-2 ml-9 px-2 py-1 rounded inline-block" style={{ color: '#888', backgroundColor: '#f5f5f5' }}>
                  {item.option_name}
                </p>
              )}
            </div>
          )
        })}
      </div>

      {/* ── 무료배송 프로그레스 ── */}
      <div className="mt-2 bg-white rounded-xl mx-2 px-4 py-4">
        <div className="h-[6px] rounded-full overflow-hidden" style={{ backgroundColor: '#f0f0f0' }}>
          <div className="h-full rounded-full transition-all" style={{ width: `${progress}%`, backgroundColor: '#968774' }} />
        </div>
        <p className="text-[13px] mt-2" style={{ color: '#666' }}>
          {remaining > 0 ? (
            <><span className="font-bold" style={{ color: '#968774' }}>{formatPrice(remaining)}</span> 더 담으면 무료 배송!</>
          ) : (
            <span className="font-bold" style={{ color: '#968774' }}>무료 배송 조건을 충족했습니다!</span>
          )}
        </p>
      </div>

      {/* ── 결제 요약 ── */}
      <div className="mt-2 bg-white px-4 pt-5 pb-4 flex-1">
        <div className="space-y-3 text-[14px]">
          <div className="flex justify-between">
            <span style={{ color: '#333' }}>상품 금액</span>
            <span style={{ color: '#333' }}>{formatPrice(originalTotal)}</span>
          </div>
          {discount > 0 && (
            <div className="flex justify-between">
              <span style={{ color: '#333' }}>상품 할인 금액</span>
              <span style={{ color: '#e84a3b' }}>-{formatPrice(discount)}</span>
            </div>
          )}
          <div className="flex justify-between">
            <span style={{ color: '#333', fontWeight: 600 }}>배송비</span>
            <span style={{ color: '#333' }}>{formatPrice(shippingFee)}</span>
          </div>
        </div>

        {/* 결제예정금액 */}
        <div className="flex justify-between items-center mt-4 pt-4 border-t" style={{ borderColor: '#f0f0f0' }}>
          <span className="text-[14px] font-bold" style={{ color: '#333' }}>결제예정금액</span>
          <span className="text-[20px] font-bold" style={{ color: '#333' }}>{formatPrice(total)}</span>
        </div>

        {/* 적립금 안내 */}
        <div className="mt-4 py-3 rounded-lg text-center" style={{ backgroundColor: '#f5f5f5' }}>
          <p className="text-[13px]" style={{ color: '#999' }}>포인트는 주문서에서 적용할 수 있어요</p>
        </div>
      </div>

      {/* ── 하단 고정 주문 버튼 ── */}
      <div className="sticky bottom-0 z-40 bg-white px-4 pt-3 pb-4 border-t mt-auto" style={{ borderColor: '#f0f0f0' }}>
        {/* 쿠폰 배너 */}
        <div className="flex items-center justify-between px-3 py-2.5 mb-3 rounded-lg border" style={{ borderColor: '#968774', backgroundColor: '#faf8f6' }}>
          <div className="flex items-center gap-2">
            <span className="text-[12px] font-bold px-1.5 py-0.5 rounded" style={{ backgroundColor: '#968774', color: '#fff' }}>%</span>
            <span className="text-[13px]" style={{ color: '#333' }}>바로 적용되는 <strong style={{ color: '#968774' }}>추가 상품 쿠폰</strong>이 있어요</span>
          </div>
        </div>

        {/* 주문 금액 요약 */}
        {goodsTotal > 0 && shippingFee > 0 && (
          <p className="text-center text-[12px] mb-2" style={{ color: '#999' }}>
            배송비 {formatPrice(shippingFee)} 포함
          </p>
        )}

        {/* 주문 버튼 */}
        <Link href="/order"
          className="block w-full text-center py-4 rounded-xl font-bold text-[16px] text-white"
          style={{ backgroundColor: selected.size > 0 ? '#968774' : '#ccc', pointerEvents: selected.size > 0 ? 'auto' : 'none' }}>
          {formatPrice(total)} 주문하기
        </Link>
      </div>
    </div>
  )
}
