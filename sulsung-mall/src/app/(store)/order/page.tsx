'use client'

import { useEffect, useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { ChevronLeft, ChevronDown, X } from 'lucide-react'
import Link from 'next/link'
import Image from 'next/image'
import { formatPrice } from '@/utils/format'
import { BASE_SHIPPING_FEE, FREE_SHIPPING_THRESHOLD } from '@/constants'

declare global { interface Window { TossPayments: any; daum: any } }

interface CartItem {
  id: string; qty: number
  goods: { id: string; name: string; price: number; sale_price: number | null; thumbnail_url: string | null; stock: number }
  goods_options: { id: number; name: string; price_delta: number } | null
}

interface Address {
  id: string; name: string; phone: string; zipcode: string; address1: string; address2: string; label: string
}

export default function OrderPage() {
  const router = useRouter()
  const [items, setItems] = useState<CartItem[]>([])
  const [member, setMember] = useState<any>(null)
  const [defaultAddress, setDefaultAddress] = useState<Address | null>(null)
  const [editingAddress, setEditingAddress] = useState(false)
  const [form, setForm] = useState({ recipient: '', phone: '', zipcode: '', address1: '', address2: '' })
  const [deliveryPlace, setDeliveryPlace] = useState('문 앞')
  const [deliveryPlaceEtc, setDeliveryPlaceEtc] = useState('')
  const [entranceMethod, setEntranceMethod] = useState('비밀번호')
  const [entrancePassword, setEntrancePassword] = useState('')
  const [entranceEtc, setEntranceEtc] = useState('')
  const [showOrdererInfo, setShowOrdererInfo] = useState(false)
  const [showDeliverySheet, setShowDeliverySheet] = useState(false)
  const [showOrderItems, setShowOrderItems] = useState(true)
  const [mileageUse, setMileageUse] = useState(0)
  const [coupons, setCoupons] = useState<any[]>([])
  const [selectedCoupon, setSelectedCoupon] = useState<any>(null)
  const [maxDiscountApply, setMaxDiscountApply] = useState(true)
  const [paymentMethod, setPaymentMethod] = useState('bank')
  const [selectedCard, setSelectedCard] = useState('')
  const [showCardSelect, setShowCardSelect] = useState(false)
  const [loading, setLoading] = useState(false)
  const [pageLoading, setPageLoading] = useState(true)
  const tossRef = useRef<any>(null)

  useEffect(() => {
    // cart+주소를 단일 API로, 회원정보는 병렬로 호출 (2개 API만)
    const cartFullP = fetch('/api/v1/cart?full=true').then(r => r.json())
    const memberP = fetch('/api/v1/members/me').then(r => r.json())
    // 쿠폰은 비동기로 나중에 도착해도 OK
    fetch('/api/v1/members/me/coupons').then(r => r.json()).then(d => setCoupons(d.coupons ?? [])).catch(() => {})

    Promise.all([cartFullP, memberP]).then(([cartData, memberData]) => {
      setItems(cartData.items ?? [])
      setMember(memberData.member)
      if (cartData.defaultAddress) {
        const addr = cartData.defaultAddress
        setDefaultAddress(addr)
        setForm({ recipient: addr.name, phone: addr.phone, zipcode: addr.zipcode, address1: addr.address1, address2: addr.address2 ?? '' })
      } else if (memberData.member) {
        setForm(f => ({ ...f, recipient: memberData.member.name ?? '', phone: memberData.member.phone ?? '' }))
        setEditingAddress(true)
      }
      setPageLoading(false)
    })

    // 외부 스크립트는 즉시 로드 시작
    const tossScript = document.createElement('script')
    tossScript.src = 'https://js.tosspayments.com/v2/standard'
    tossScript.onload = () => { tossRef.current = window.TossPayments(process.env.NEXT_PUBLIC_TOSS_CLIENT_KEY!) }
    document.head.appendChild(tossScript)

    const daumScript = document.createElement('script')
    daumScript.src = '//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js'
    document.head.appendChild(daumScript)
  }, [])

  function openPostcode() {
    new window.daum.Postcode({
      oncomplete(data: any) {
        setForm(f => ({
          ...f,
          zipcode: data.zonecode,
          address1: data.roadAddress || data.jibunAddress,
        }))
      },
    }).open()
  }

  const memo = [
    `받으실 장소: ${deliveryPlace === '기타 장소' ? deliveryPlaceEtc || '기타' : deliveryPlace}`,
    `출입방법: ${entranceMethod === '비밀번호' ? `비밀번호 ${entrancePassword}` : entranceMethod === '기타' ? entranceEtc || '기타' : entranceMethod}`,
  ].join(' / ')

  const goodsTotal = items.reduce((s, i) => s + ((i.goods.sale_price ?? i.goods.price) + (i.goods_options?.price_delta ?? 0)) * i.qty, 0)
  const shippingFee = goodsTotal >= FREE_SHIPPING_THRESHOLD ? 0 : items.length > 0 ? BASE_SHIPPING_FEE : 0

  // 쿠폰 할인 계산
  const couponDiscount = (() => {
    if (!selectedCoupon) return 0
    const c = selectedCoupon
    if (c.min_order_amount && goodsTotal < c.min_order_amount) return 0
    if (c.type === 'amount') return c.discount_amount
    if (c.type === 'rate') {
      const d = Math.floor(goodsTotal * c.discount_rate / 100)
      return c.max_discount ? Math.min(d, c.max_discount) : d
    }
    return 0
  })()

  // 최대 할인 자동 적용
  const bestCoupon = coupons.length > 0 ? coupons.reduce((best: any, c: any) => {
    if (c.min_order_amount && goodsTotal < c.min_order_amount) return best
    let d = 0
    if (c.type === 'amount') d = c.discount_amount
    else if (c.type === 'rate') {
      d = Math.floor(goodsTotal * c.discount_rate / 100)
      if (c.max_discount) d = Math.min(d, c.max_discount)
    }
    if (!best || d > best._discount) return { ...c, _discount: d }
    return best
  }, null) : null

  const effectiveCouponDiscount = maxDiscountApply && bestCoupon ? (bestCoupon._discount ?? 0) : couponDiscount
  const totalAmount = goodsTotal + shippingFee - mileageUse - effectiveCouponDiscount

  async function handleOrder() {
    if (!form.recipient || !form.phone || !form.address1) { alert('배송 정보를 입력해주세요.'); return }
    if (paymentMethod === 'card' && !selectedCard) { alert('카드를 선택해주세요.'); return }
    setLoading(true)
    try {
      // 1. 주문 생성
      const orderRes = await fetch('/api/v1/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: items.map(i => ({ goodsId: i.goods.id, optionId: i.goods_options?.id, qty: i.qty })),
          recipient: form.recipient, phone: form.phone, zipcode: form.zipcode,
          address1: form.address1, address2: form.address2, deliveryMemo: memo,
          mileageUse,
          couponId: maxDiscountApply ? bestCoupon?.id : selectedCoupon?.id,
          couponDiscount: effectiveCouponDiscount,
        }),
      })
      const orderData = await orderRes.json()

      if (!orderData.orderId) {
        alert(orderData.error ?? '주문 생성에 실패했습니다.')
        setLoading(false)
        return
      }

      // 2. 토스페이먼츠 결제 요청
      if (!tossRef.current) {
        alert('결제 모듈을 불러오는 중입니다. 잠시 후 다시 시도해주세요.')
        setLoading(false)
        return
      }

      const firstItemName = items[0]?.goods.name ?? '상품'
      const orderName = items.length > 1
        ? `${firstItemName} 외 ${items.length - 1}건`
        : firstItemName

      // 결제 수단 매핑 (V2 Standard SDK)
      const methodMap: Record<string, string> = {
        bank: 'VIRTUAL_ACCOUNT',
        card: 'CARD',
        transfer: 'TRANSFER',
        phone: 'MOBILE_PHONE',
      }

      const method = methodMap[paymentMethod] ?? 'CARD'

      const customerKey = member?.id ?? '_ANONYMOUS'
      const payment = tossRef.current.payment({ customerKey })

      await payment.requestPayment({
        method,
        amount: { currency: 'KRW', value: totalAmount },
        orderId: orderData.orderId,
        orderName,
        customerName: form.recipient,
        successUrl: `${window.location.origin}/order/payment/success`,
        failUrl: `${window.location.origin}/order/payment/fail`,
      })
    } catch (err: any) {
      // 사용자가 결제창을 닫은 경우
      if (err?.code === 'USER_CANCEL' || err?.code === 'PAY_PROCESS_CANCELED') {
        setLoading(false)
        return
      }
      console.error('[TossPayments Error]', JSON.stringify(err, null, 2))
      alert(`결제 오류: ${err?.code ?? 'UNKNOWN'} - ${err?.message ?? '알 수 없는 에러'}`)
      setLoading(false)
    }
  }

  const inputClass = "w-full border rounded-xl px-3.5 py-3 text-[14px] focus:outline-none"
  const inputStyle = { borderColor: '#e0dbd5', color: '#333' }

  if (pageLoading) {
    return (
      <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
        <div className="sticky top-0 z-50 bg-white flex items-center h-[48px] px-4" style={{ borderBottom: '1px solid #ebebeb' }}>
          <Link href="/cart" className="mr-3">
            <ChevronLeft className="w-6 h-6" style={{ color: '#333' }} />
          </Link>
          <h1 className="text-[18px] font-bold" style={{ color: '#222' }}>주문서</h1>
        </div>
        <div className="flex items-center justify-center" style={{ height: '60vh' }}>
          <div className="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin" style={{ borderColor: '#968774', borderTopColor: 'transparent' }} />
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col" style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      {/* 헤더 */}
      <div className="sticky top-0 z-50 bg-white flex items-center h-[48px] px-4">
        <Link href="/cart" className="mr-3">
          <ChevronLeft className="w-6 h-6" style={{ color: '#333' }} />
        </Link>
        <h1 className="text-[18px] font-bold" style={{ color: '#222' }}>주문서</h1>
      </div>

      <div className="flex-1">

      {/* 주문자 정보 */}
      {member && (
        <div className="bg-white">
          <button
            onClick={() => setShowOrdererInfo(!showOrdererInfo)}
            className="w-full flex items-center justify-between px-5 py-4"
          >
            <span className="text-[18px] font-bold" style={{ color: '#333' }}>주문자 정보</span>
            <span className="flex items-center gap-1">
              <span className="text-[14px]" style={{ color: '#333' }}>{member.name}, {member.phone}</span>
              <ChevronDown className={`w-4 h-4 transition-transform ${showOrdererInfo ? 'rotate-180' : ''}`} style={{ color: '#999' }} />
            </span>
          </button>
          {showOrdererInfo && (
            <div className="px-5 pb-4 space-y-1.5 text-[14px]" style={{ color: '#333' }}>
              <div className="flex">
                <span className="w-[60px] flex-shrink-0" style={{ color: '#888' }}>이름</span>
                <span>{member.name}</span>
              </div>
              <div className="flex">
                <span className="w-[60px] flex-shrink-0" style={{ color: '#888' }}>연락처</span>
                <span>{member.phone}</span>
              </div>
              <div className="flex">
                <span className="w-[60px] flex-shrink-0" style={{ color: '#888' }}>이메일</span>
                <span className="truncate">{member.email}</span>
              </div>
            </div>
          )}
        </div>
      )}

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 배송지 */}
      <div className="bg-white px-5 py-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-[18px] font-bold" style={{ color: '#333' }}>배송지</h2>
        </div>

        {!editingAddress && defaultAddress ? (
          <div>
            {defaultAddress.label && (
              <span className="inline-block text-[12px] px-2 py-0.5 rounded mb-2" style={{ backgroundColor: '#f0ece7', color: '#968774' }}>
                {defaultAddress.label}
              </span>
            )}
            {!defaultAddress.label && (
              <span className="inline-block text-[12px] px-2 py-0.5 rounded mb-2" style={{ backgroundColor: '#f0ece7', color: '#968774' }}>
                기본배송지
              </span>
            )}
            <div className="flex items-start justify-between">
              <p className="text-[14px] leading-relaxed flex-1 pr-3" style={{ color: '#333' }}>
                {defaultAddress.address1} {defaultAddress.address2}
              </p>
              <button
                onClick={() => router.push('/mypage/addresses?select=true')}
                className="flex-shrink-0 text-[13px] px-3 py-1.5 rounded-lg border"
                style={{ borderColor: '#ddd', color: '#555' }}
              >
                변경
              </button>
            </div>
          </div>
        ) : (
          <div className="space-y-2.5">
            <input type="text" placeholder="받는 분 성함" value={form.recipient}
              onChange={e => setForm(f => ({ ...f, recipient: e.target.value }))}
              className={inputClass} style={inputStyle} />
            <input type="tel" placeholder="연락처 (010-0000-0000)" value={form.phone}
              onChange={e => setForm(f => ({ ...f, phone: e.target.value }))}
              className={inputClass} style={inputStyle} />
            <div className="flex gap-2">
              <input type="text" placeholder="우편번호" value={form.zipcode}
                onChange={e => setForm(f => ({ ...f, zipcode: e.target.value }))}
                className="flex-1 border rounded-xl px-3.5 py-3 text-[14px] focus:outline-none" style={inputStyle} />
              <button type="button" onClick={openPostcode} className="px-4 py-3 rounded-xl text-[13px] font-medium border"
                style={{ borderColor: '#968774', color: '#968774' }}>
                검색
              </button>
            </div>
            <input type="text" placeholder="도로명 주소" value={form.address1}
              onChange={e => setForm(f => ({ ...f, address1: e.target.value }))}
              className={inputClass} style={inputStyle} />
            <input type="text" placeholder="상세주소 (동/호수 등)" value={form.address2}
              onChange={e => setForm(f => ({ ...f, address2: e.target.value }))}
              className={inputClass} style={inputStyle} />
          </div>
        )}
      </div>

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 배송 요청사항 (요약) */}
      <div className="bg-white px-5 py-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-[18px] font-bold" style={{ color: '#333' }}>배송 요청사항</h2>
        </div>
        <div className="flex items-start justify-between">
          <div className="flex-1 pr-3">
            <p className="text-[16px] font-bold leading-relaxed" style={{ color: '#333' }}>
              <span>{deliveryPlace}</span>
              {' | '}
              <span>
                {entranceMethod === '기타' ? '기타' : entranceMethod}
              </span>
              {entranceMethod === '비밀번호' && entrancePassword && (
                <span style={{ color: '#968774' }}> ({entrancePassword})</span>
              )}
              {entranceMethod === '기타' && entranceEtc && (
                <span style={{ color: '#968774' }}> ({entranceEtc})</span>
              )}
            </p>
          </div>
          <button
            onClick={() => setShowDeliverySheet(true)}
            className="flex-shrink-0 text-[13px] px-3 py-1.5 rounded-lg border"
            style={{ borderColor: '#ddd', color: '#555' }}
          >
            수정
          </button>
        </div>
      </div>

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 주문 상품 */}
      <div className="bg-white">
        <button
          onClick={() => setShowOrderItems(!showOrderItems)}
          className="w-full flex items-center justify-between px-5 py-4"
        >
          <span className="text-[18px] font-bold" style={{ color: '#333' }}>주문상품</span>
          <ChevronDown className={`w-5 h-5 transition-transform ${showOrderItems ? 'rotate-180' : ''}`} style={{ color: '#999' }} />
        </button>
        {showOrderItems && (
          <div className="mx-5 mb-4 rounded-xl border" style={{ borderColor: '#eee' }}>
            <div>
              {items.map(item => {
                const salePrice = (item.goods.sale_price ?? item.goods.price) + (item.goods_options?.price_delta ?? 0)
                const originalPrice = item.goods.price + (item.goods_options?.price_delta ?? 0)
                const hasDiscount = item.goods.sale_price !== null && item.goods.sale_price < item.goods.price
                return (
                  <div key={item.id} className="flex gap-3 p-4">
                    <div className="w-[60px] h-[60px] rounded-lg overflow-hidden flex-shrink-0 relative" style={{ backgroundColor: '#f5f5f5' }}>
                      {item.goods.thumbnail_url && (
                        <Image src={item.goods.thumbnail_url} alt={item.goods.name} fill className="object-cover" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-[15px] font-bold leading-snug mb-1" style={{ color: '#333' }}>
                        {item.goods.name}
                      </p>
                      {item.goods_options && (
                        <p className="text-[13px] mb-1" style={{ color: '#888' }}>
                          {item.goods_options.name}
                        </p>
                      )}
                      <div className="flex items-center gap-1.5">
                        <span className="text-[14px] font-bold" style={{ color: '#333' }}>
                          {formatPrice(salePrice * item.qty)}
                        </span>
                        {hasDiscount && (
                          <span className="text-[12px] line-through" style={{ color: '#aaa' }}>
                            {formatPrice(originalPrice * item.qty)}
                          </span>
                        )}
                        <span className="text-[12px]" style={{ color: '#888' }}>| {item.qty}개</span>
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        )}
      </div>

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 쿠폰 */}
      <div className="bg-white px-5 py-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-[18px] font-bold" style={{ color: '#333' }}>쿠폰</h2>
          <div className="flex items-center gap-2">
            <span className="text-[13px]" style={{ color: '#333' }}>최대 할인 적용</span>
            <button
              onClick={() => {
                setMaxDiscountApply(!maxDiscountApply)
                if (!maxDiscountApply && bestCoupon) setSelectedCoupon(bestCoupon)
                else setSelectedCoupon(null)
              }}
              className="w-[44px] h-[24px] rounded-full relative transition-colors"
              style={{ backgroundColor: maxDiscountApply ? '#968774' : '#ddd' }}
            >
              <div
                className="w-[20px] h-[20px] rounded-full bg-white absolute top-[2px] transition-all"
                style={{ left: maxDiscountApply ? '22px' : '2px' }}
              />
            </button>
          </div>
        </div>
        <div className="border rounded-xl px-4 py-3 flex items-center justify-between" style={{ borderColor: '#eee' }}>
          <span className="text-[14px]" style={{ color: '#333' }}>쿠폰 할인</span>
          <div className="flex items-center gap-2">
            {effectiveCouponDiscount > 0 && (
              <span className="text-[11px] px-2 py-0.5 rounded-full" style={{ backgroundColor: '#fef0f0', color: '#e85050' }}>
                {maxDiscountApply ? '최대적용중' : '적용중'}
              </span>
            )}
            <span className="text-[16px] font-bold" style={{ color: effectiveCouponDiscount > 0 ? '#e85050' : '#333' }}>
              {effectiveCouponDiscount > 0 ? formatPrice(effectiveCouponDiscount) : '0원'}
            </span>
            <ChevronDown className="w-4 h-4 -rotate-90" style={{ color: '#999' }} />
          </div>
        </div>
        {coupons.length === 0 && (
          <p className="text-[13px] mt-2" style={{ color: '#aaa' }}>사용 가능한 쿠폰이 없습니다.</p>
        )}
      </div>

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 포인트 */}
      {member && (
        <>
          <div className="bg-white px-5 py-4">
            <h2 className="text-[18px] font-bold mb-3" style={{ color: '#333' }}>포인트</h2>
            <div className="flex items-center justify-between mb-2">
              <span className="text-[14px]" style={{ color: '#555' }}>잔액</span>
              <span className="text-[14px]" style={{ color: '#333' }}>{(member.mileage ?? 0).toLocaleString()} 원</span>
            </div>
            <div className="flex gap-2 mt-3">
              <input type="number" min={0} max={Math.min(member.mileage ?? 0, goodsTotal + shippingFee)}
                value={mileageUse} onChange={e => setMileageUse(Number(e.target.value))}
                className="flex-1 border rounded-lg px-4 py-3 text-[14px] focus:outline-none" style={{ borderColor: '#eee', color: '#333' }} />
              <button onClick={() => setMileageUse(Math.min(member.mileage ?? 0, goodsTotal + shippingFee))}
                className="flex-shrink-0 text-[14px] px-4 py-3 rounded-lg border"
                style={{ borderColor: '#eee', color: '#555' }}>
                모두사용
              </button>
            </div>
            <p className="text-[12px] mt-3" style={{ color: '#aaa' }}>· 포인트는 1원 단위로 사용 가능합니다.</p>
          </div>
          <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />
        </>
      )}

      {/* 결제금액 */}
      <div className="bg-white px-5 py-5">
        <h2 className="text-[18px] font-bold mb-4" style={{ color: '#333' }}>결제금액</h2>

        {/* 주문금액 */}
        <div className="flex justify-between mb-1">
          <span className="text-[16px] font-bold" style={{ color: '#333' }}>주문금액</span>
          <span className="text-[16px] font-bold" style={{ color: '#333' }}>
            {formatPrice(goodsTotal)} 원
          </span>
        </div>
        {(() => {
          const originalTotal = items.reduce((s, i) => s + (i.goods.price + (i.goods_options?.price_delta ?? 0)) * i.qty, 0)
          const discountAmount = originalTotal - goodsTotal
          return (
            <>
              <div className="flex justify-between ml-3 mb-0.5">
                <span className="text-[15px] flex items-center gap-1" style={{ color: '#888' }}>
                  <span style={{ color: '#ccc' }}>└</span> 상품금액
                </span>
                <span className="text-[15px]" style={{ color: '#888' }}>{originalTotal.toLocaleString()} 원</span>
              </div>
              {discountAmount > 0 && (
                <div className="flex justify-between ml-3 mb-0.5">
                  <span className="text-[15px] flex items-center gap-1" style={{ color: '#888' }}>
                    <span style={{ color: '#ccc' }}>└</span> 상품할인금액
                  </span>
                  <span className="text-[15px]" style={{ color: '#888' }}>-{discountAmount.toLocaleString()} 원</span>
                </div>
              )}
            </>
          )
        })()}

        {/* 배송비 */}
        <div className="flex justify-between mt-3 mb-1">
          <span className="text-[16px] font-bold" style={{ color: '#333' }}>배송비</span>
          <span className="text-[16px] font-bold" style={{ color: '#333' }}>
            {shippingFee === 0 ? '0' : shippingFee.toLocaleString()} 원
          </span>
        </div>

        {/* 쿠폰할인 */}
        <div className="flex justify-between mt-3 mb-1">
          <span className="text-[16px] font-bold" style={{ color: '#333' }}>쿠폰할인</span>
          <span className="text-[16px] font-bold" style={{ color: effectiveCouponDiscount > 0 ? '#e85050' : '#333' }}>
            {effectiveCouponDiscount > 0 ? `-${effectiveCouponDiscount.toLocaleString()}` : '0'} 원
          </span>
        </div>

        {/* 포인트 */}
        <div className="flex justify-between mt-3 mb-1">
          <span className="text-[16px] font-bold" style={{ color: '#333' }}>포인트</span>
          <span className="text-[16px] font-bold" style={{ color: mileageUse > 0 ? '#e85050' : '#333' }}>
            {mileageUse > 0 ? `-${mileageUse.toLocaleString()}` : '0'} 원
          </span>
        </div>

        {/* 최종 결제금액 */}
        <div className="flex justify-between items-center mt-5 pt-4 border-t" style={{ borderColor: '#eee' }}>
          <span className="text-[18px] font-bold" style={{ color: '#333' }}>최종 결제금액</span>
          <span className="text-[20px] font-bold" style={{ color: '#333' }}>{totalAmount.toLocaleString()} 원</span>
        </div>
      </div>

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 결제수단 */}
      <div className="bg-white px-5 py-5">
        <h2 className="text-[18px] font-bold mb-4" style={{ color: '#333' }}>결제수단</h2>

        <div className="space-y-0">
          {([
            { key: 'bank', label: '무통장입금', logo: '' },
            { key: 'card', label: '신용카드', logo: '' },
            { key: 'phone', label: '휴대폰', logo: '' },
          ] as { key: string; label: string; logo: string }[]).map(opt => (
            <div key={opt.key}>
              <button
                onClick={() => { setPaymentMethod(opt.key); if (opt.key !== 'card') setShowCardSelect(false) }}
                className="w-full flex items-center gap-3 py-3"
              >
                <div
                  className="w-[22px] h-[22px] rounded-full border-2 flex items-center justify-center flex-shrink-0"
                  style={{ borderColor: paymentMethod === opt.key ? '#333' : '#ccc' }}
                >
                  {paymentMethod === opt.key && (
                    <div className="w-[12px] h-[12px] rounded-full" style={{ backgroundColor: '#333' }} />
                  )}
                </div>
                {opt.logo ? (
                  <img src={opt.logo} alt={opt.label} style={{ height: 22 }} />
                ) : (
                  <span className="text-[15px]" style={{ color: '#333' }}>{opt.label}</span>
                )}
              </button>

              {/* 신용카드 선택 시 카드 셀렉트 */}
              {opt.key === 'card' && paymentMethod === 'card' && (
                <div className="pl-9 pb-3 pt-1">
                  <button
                    onClick={() => setShowCardSelect(!showCardSelect)}
                    className="w-full flex items-center justify-between px-4 py-3 rounded-xl border"
                    style={{ borderColor: '#eee' }}
                  >
                    <span className="text-[14px]" style={{ color: selectedCard ? '#333' : '#aaa' }}>
                      {selectedCard || '카드를 선택해 주세요'}
                    </span>
                    <ChevronDown className={`w-4 h-4 transition-transform ${showCardSelect ? 'rotate-180' : ''}`} style={{ color: '#999' }} />
                  </button>
                  {showCardSelect && (
                    <div className="mt-2 border rounded-xl overflow-hidden" style={{ borderColor: '#eee' }}>
                      {['삼성카드', '현대카드', '국민카드', '신한카드', '롯데카드', '하나카드', 'BC카드', 'NH농협카드', '우리카드'].map(card => (
                        <button
                          key={card}
                          onClick={() => { setSelectedCard(card); setShowCardSelect(false) }}
                          className="w-full text-left px-4 py-3 text-[14px] border-b"
                          style={{
                            borderColor: '#f5f5f5',
                            color: selectedCard === card ? '#968774' : '#333',
                            backgroundColor: selectedCard === card ? '#faf8f5' : '#fff',
                          }}
                        >
                          {card}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      </div>

      {/* 하단 결제 버튼 */}
      <div className="sticky bottom-0 z-40 bg-white px-5 py-3">
        <button onClick={handleOrder} disabled={loading || items.length === 0}
          className="w-full py-4 rounded-xl font-bold text-[20px] text-white disabled:opacity-50"
          style={{ backgroundColor: '#968774' }}>
          {loading ? '처리 중...' : `${formatPrice(totalAmount)} 결제하기`}
        </button>
      </div>

      {/* 배송 요청사항 바텀시트 */}
      {showDeliverySheet && (
        <div className="fixed inset-0 z-[100] flex flex-col mx-auto" style={{ backgroundColor: '#fff', maxWidth: '480px', overflow: 'hidden' }}>
          {/* 헤더 */}
          <div className="flex items-center justify-between h-[48px] px-4">
            <button onClick={() => setShowDeliverySheet(false)} className="p-1 -ml-1">
              <X className="w-6 h-6" style={{ color: '#333' }} />
            </button>
            <h2 className="text-[17px] font-bold" style={{ color: '#222' }}>배송 요청사항</h2>
            <div className="w-6" />
          </div>

          <div className="flex-1 overflow-y-auto px-5 pt-5 pb-28">
            {/* 받으실 장소 */}
            <div className="mb-7">
              <p className="text-[15px] font-bold mb-3" style={{ color: '#333' }}>받으실 장소</p>
              <div className="flex gap-6">
                {['문 앞', '기타 장소'].map(opt => (
                  <label key={opt} className="flex items-center gap-2.5 cursor-pointer">
                    <div className="w-[20px] h-[20px] rounded-full flex-shrink-0"
                      style={deliveryPlace === opt
                        ? { border: '6px solid #968774' }
                        : { border: '2px solid #ccc' }
                      } />
                    <span className="text-[14px]" style={{ color: '#333' }}>{opt}</span>
                    <input type="radio" name="sheetDeliveryPlace" className="hidden"
                      checked={deliveryPlace === opt} onChange={() => setDeliveryPlace(opt)} />
                  </label>
                ))}
              </div>
              {deliveryPlace === '기타 장소' && (
                <input value={deliveryPlaceEtc} onChange={e => setDeliveryPlaceEtc(e.target.value)}
                  placeholder="장소를 입력해 주세요"
                  className="w-full border rounded-xl px-3.5 py-3 text-[14px] focus:outline-none mt-3"
                  style={{ borderColor: '#e0dbd5', color: '#333' }} />
              )}
              {deliveryPlace === '문 앞' && (
                <div className="mt-3 px-3.5 py-3 rounded-xl text-[13px]" style={{ backgroundColor: '#f7f5f2', color: '#888' }}>
                  경비실과 무인택배함 배송이 종료되었어요.
                </div>
              )}
            </div>

            {/* 공동현관 출입방법 */}
            <div className="mb-7">
              <p className="text-[15px] font-bold mb-3" style={{ color: '#333' }}>공동현관 출입방법</p>
              <div className="space-y-3">
                {['공동현관 비밀번호', '자유 출입 가능', '경비실 호출', '기타'].map(opt => {
                  const val = opt === '공동현관 비밀번호' ? '비밀번호' : opt
                  return (
                    <label key={opt} className="flex items-center gap-2.5 cursor-pointer">
                      <div className="w-[20px] h-[20px] rounded-full flex-shrink-0"
                        style={entranceMethod === val
                          ? { border: '6px solid #968774' }
                          : { border: '2px solid #ccc' }
                        } />
                      <span className="text-[14px]" style={{ color: '#333' }}>{opt}</span>
                      <input type="radio" name="sheetEntranceMethod" className="hidden"
                        checked={entranceMethod === val} onChange={() => setEntranceMethod(val)} />
                    </label>
                  )
                })}
              </div>
              {entranceMethod === '비밀번호' && (
                <input value={entrancePassword} onChange={e => setEntrancePassword(e.target.value)}
                  placeholder="비밀번호를 입력해 주세요 (예: #1234*)"
                  className="w-full border rounded-xl px-3.5 py-3 text-[14px] focus:outline-none mt-3"
                  style={{ borderColor: '#e0dbd5', color: '#333' }} />
              )}
              {entranceMethod === '기타' && (
                <textarea value={entranceEtc} onChange={e => setEntranceEtc(e.target.value)}
                  placeholder="출입방법을 입력해 주세요"
                  rows={3}
                  className="w-full border rounded-xl px-3.5 py-3 text-[14px] focus:outline-none mt-3 resize-none"
                  style={{ borderColor: '#e0dbd5', color: '#333' }} />
              )}
            </div>

            {/* 안내 */}
            <div className="px-3.5 py-3 rounded-xl text-[13px] leading-relaxed" style={{ backgroundColor: '#f7f5f2', color: '#888' }}>
              <p className="font-medium mb-1" style={{ color: '#968774' }}>확인해주세요!</p>
              <p>• 요청하신 방법으로 출입이 어려운 경우, 부득이하게 1층 공동현관 앞에 배송될 수 있습니다.</p>
              <p>• 배송 받으실 시간은 별도로 지정하실 수 없습니다.</p>
            </div>
          </div>

          {/* 저장 버튼 */}
          <div className="sticky bottom-0 bg-white px-5 py-3 border-t" style={{ borderColor: '#ebebeb' }}>
            <button
              onClick={() => setShowDeliverySheet(false)}
              className="w-full py-3.5 rounded-xl font-bold text-[15px] text-white"
              style={{ backgroundColor: '#968774' }}>
              저장
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
