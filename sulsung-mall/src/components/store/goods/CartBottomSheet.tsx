'use client'

import { useState, useLayoutEffect, useRef, useEffect } from 'react'
import { createPortal } from 'react-dom'
import Image from 'next/image'
import Link from 'next/link'
import { ShoppingCart, Minus, Plus, Check } from 'lucide-react'
import { FREE_SHIPPING_THRESHOLD } from '@/constants'

interface CartSheetGoods {
  id: string
  name: string
  slug: string
  price: number
  sale_price: number | null
  thumbnail_url: string | null
  stock?: number
}

interface Props {
  goods: CartSheetGoods
  onClose: () => void
}

export default function CartBottomSheet({ goods, onClose }: Props) {
  const [step, setStep] = useState<'select' | 'confirm'>('select')
  const [qty, setQty] = useState(1)
  const [adding, setAdding] = useState(false)
  const [cartTotal, setCartTotal] = useState(0)
  const wrapperRef = useRef<HTMLDivElement>(null)
  const basePrice = goods.sale_price ?? goods.price
  const totalPrice = basePrice * qty

  const [wrapperRect, setWrapperRect] = useState<{ top: number; left: number; width: number; height: number } | null>(null)
  const [mounted, setMounted] = useState(false)

  useEffect(() => setMounted(true), [])

  useLayoutEffect(() => {
    const wrapper = document.querySelector('.app-scroll-wrapper') as HTMLElement
    if (!wrapper) return
    const rect = wrapper.getBoundingClientRect()
    setWrapperRect({ top: rect.top, left: rect.left, width: rect.width, height: rect.height })
    const prevOverflow = wrapper.style.overflow
    wrapper.style.overflow = 'hidden'
    return () => { wrapper.style.overflow = prevOverflow }
  }, [])

  function handleAdd() {
    const addQty = qty
    const addPrice = basePrice * addQty
    // 낙관적 UI: 즉시 confirm 화면 전환
    setCartTotal(addPrice)
    setStep('confirm')
    setQty(1)
    // 백그라운드로 API 호출
    fetch('/api/v1/cart', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ goodsId: goods.id, qty: addQty }),
    }).then(res => {
      if (!res.ok) {
        res.json().catch(() => ({})).then(j => {
          alert(j.error ?? '장바구니 담기에 실패했습니다.')
          setStep('select')
        })
        return
      }
      window.dispatchEvent(new Event('cart-updated'))
      // 실제 장바구니 총액으로 갱신 + 캐시 저장
      fetch('/api/v1/cart').then(r => r.json()).then(d => {
        const items = d.items ?? []
        const sum = items.reduce((s: number, i: any) => s + (i.goods?.sale_price ?? i.goods?.price ?? 0) * i.qty, 0)
        setCartTotal(sum);
        (window as any).__cartCache = { items, ts: Date.now() }
      }).catch(() => {})
    }).catch(() => {
      alert('장바구니 담기에 실패했습니다.')
      setStep('select')
    })
  }

  if (!mounted) return null

  const content = (
    <div ref={wrapperRef}
      style={{
        position: 'fixed',
        top: wrapperRect?.top ?? 0,
        left: wrapperRect?.left ?? 0,
        width: wrapperRect?.width ?? '100%',
        height: wrapperRect?.height ?? '100%',
        zIndex: 100,
      }}
      onClick={onClose}>
      <div style={{ position: 'absolute', inset: 0, backgroundColor: 'rgba(0,0,0,0.4)' }} />
      <div style={{
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
          backgroundColor: 'white',
          borderRadius: '16px 16px 0 0',
          animation: 'cart-slide-up 0.3s ease-out',
        }}
          onClick={e => e.stopPropagation()}>
          <div style={{ display: 'flex', justifyContent: 'center', paddingTop: 12, paddingBottom: 8 }}>
            <div style={{ width: 40, height: 4, borderRadius: 2, backgroundColor: '#d1d5db' }} />
          </div>

          {step === 'select' && (
            <>
              <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '0 20px 16px', borderBottom: '1px solid #f0f0f0' }}>
                <div style={{ width: 56, height: 56, borderRadius: 8, overflow: 'hidden', flexShrink: 0, backgroundColor: '#f5f5f5' }}>
                  {goods.thumbnail_url && (
                    <Image src={goods.thumbnail_url} alt={goods.name} width={56} height={56} className="object-cover w-full h-full" />
                  )}
                </div>
                <p style={{ fontSize: 15, fontWeight: 500, color: '#333', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                  {goods.name}
                </p>
              </div>

              <div style={{ padding: '20px 20px' }}>
                <p style={{ fontSize: 14, color: '#333', marginBottom: 16 }}>{goods.name}</p>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <p style={{ fontSize: 18, fontWeight: 700, color: '#333' }}>
                    {basePrice.toLocaleString()}<span style={{ fontSize: 14 }}>원</span>
                  </p>
                  <div style={{ display: 'flex', alignItems: 'center', border: '1px solid #e5e5e5', borderRadius: 8 }}>
                    <button onClick={() => setQty(q => Math.max(1, q - 1))}
                      style={{ width: 40, height: 40, display: 'flex', alignItems: 'center', justifyContent: 'center', color: qty <= 1 ? '#ddd' : '#333' }}>
                      <Minus style={{ width: 16, height: 16 }} />
                    </button>
                    <span style={{ width: 40, textAlign: 'center', fontSize: 15, fontWeight: 500, color: '#333' }}>{qty}</span>
                    <button onClick={() => setQty(q => Math.min(goods.stock ?? 99, q + 1))}
                      style={{ width: 40, height: 40, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#333' }}>
                      <Plus style={{ width: 16, height: 16 }} />
                    </button>
                  </div>
                </div>
              </div>

              <div style={{ padding: '0 20px 24px' }}>
                <button onClick={handleAdd}
                  style={{ width: '100%', height: 54, borderRadius: 12, fontWeight: 700, fontSize: 16, color: 'white', backgroundColor: '#968774', border: 'none', cursor: 'pointer' }}>
                  {`총 ${totalPrice.toLocaleString()}원 장바구니 담기`}
                </button>
              </div>
            </>
          )}

          {step === 'confirm' && (() => {
            const remain = FREE_SHIPPING_THRESHOLD - cartTotal
            const progress = Math.min(100, (cartTotal / FREE_SHIPPING_THRESHOLD) * 100)
            return (
            <>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '16px 20px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <div style={{ width: 24, height: 24, borderRadius: 12, display: 'flex', alignItems: 'center', justifyContent: 'center', backgroundColor: '#968774' }}>
                    <Check style={{ width: 16, height: 16, color: 'white' }} />
                  </div>
                  <p style={{ fontSize: 16, fontWeight: 700, color: '#333' }}>장바구니에 담았습니다.</p>
                </div>
                <Link href="/cart" onClick={onClose}
                  style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '8px 16px', borderRadius: 20, border: '1px solid #e5e5e5', fontSize: 13, fontWeight: 500, color: '#333', textDecoration: 'none' }}>
                  <ShoppingCart style={{ width: 16, height: 16 }} />
                  바로가기
                </Link>
              </div>

              {/* 무료배송 프로그레스 바 */}
              {cartTotal > 0 && (
                <div style={{ padding: '0 20px 16px' }}>
                  <div style={{ height: 6, borderRadius: 3, backgroundColor: '#f0f0f0', overflow: 'hidden' }}>
                    <div style={{ height: '100%', borderRadius: 3, backgroundColor: '#968774', width: `${progress}%`, transition: 'width 0.3s' }} />
                  </div>
                  <p style={{ fontSize: 13, marginTop: 8, color: '#666' }}>
                    {remain > 0 ? (
                      <><span style={{ fontWeight: 700, color: '#968774' }}>{remain.toLocaleString()}원</span> 더 담으면 무료 배송!</>
                    ) : (
                      <span style={{ fontWeight: 700, color: '#968774' }}>무료 배송 조건을 충족했습니다!</span>
                    )}
                  </p>
                </div>
              )}

              <div style={{ padding: '0 20px 24px' }}>
                <button onClick={onClose}
                  style={{ width: '100%', height: 50, borderRadius: 12, fontSize: 15, fontWeight: 500, border: '1px solid #e5e5e5', color: '#333', backgroundColor: 'white', cursor: 'pointer' }}>
                  쇼핑 계속하기
                </button>
              </div>
            </>
            )
          })()}
        </div>

      <style>{`
        @keyframes cart-slide-up {
          from { transform: translateY(100%); }
          to { transform: translateY(0); }
        }
      `}</style>
    </div>
  )

  return createPortal(content, document.body)
}
