'use client'

import { useEffect, useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { ChevronLeft } from 'lucide-react'
import Link from 'next/link'
import { formatPrice } from '@/utils/format'
import { BASE_SHIPPING_FEE, FREE_SHIPPING_THRESHOLD } from '@/constants'

declare global { interface Window { TossPayments: any; daum: any } }

interface CartItem {
  id: string; qty: number
  goods: { id: string; name: string; price: number; sale_price: number | null; stock: number }
  goods_options: { id: number; name: string; price_delta: number } | null
}

export default function OrderPage() {
  const router = useRouter()
  const [items, setItems] = useState<CartItem[]>([])
  const [member, setMember] = useState<any>(null)
  const [form, setForm] = useState({ recipient: '', phone: '', zipcode: '', address1: '', address2: '', memo: '' })
  const [mileageUse, setMileageUse] = useState(0)
  const [loading, setLoading] = useState(false)
  const tossRef = useRef<any>(null)

  useEffect(() => {
    Promise.all([
      fetch('/api/v1/cart').then(r => r.json()),
      fetch('/api/v1/members/me').then(r => r.json()),
    ]).then(([cartData, memberData]) => {
      setItems(cartData.items ?? [])
      setMember(memberData.member)
      if (memberData.member) {
        setForm(f => ({ ...f, recipient: memberData.member.name ?? '', phone: memberData.member.phone ?? '' }))
      }
    })

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

  const goodsTotal = items.reduce((s, i) => s + ((i.goods.sale_price ?? i.goods.price) + (i.goods_options?.price_delta ?? 0)) * i.qty, 0)
  const shippingFee = goodsTotal >= FREE_SHIPPING_THRESHOLD ? 0 : items.length > 0 ? BASE_SHIPPING_FEE : 0
  const totalAmount = goodsTotal + shippingFee - mileageUse

  async function handleOrder() {
    if (!form.recipient || !form.phone || !form.address1) { alert('배송 정보를 입력해주세요.'); return }
    setLoading(true)
    try {
      const orderRes = await fetch('/api/v1/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: items.map(i => ({ goodsId: i.goods.id, optionId: i.goods_options?.id, qty: i.qty })),
          recipient: form.recipient, phone: form.phone, zipcode: form.zipcode,
          address1: form.address1, address2: form.address2, deliveryMemo: form.memo,
          mileageUse,
        }),
      })
      const { orderId, error } = await orderRes.json()
      if (error) { alert(error); setLoading(false); return }

      const checkout = tossRef.current.payment({ customerKey: member?.id ?? 'guest' })
      await checkout.requestPayment({
        method: 'CARD',
        amount: { currency: 'KRW', value: totalAmount },
        orderId,
        orderName: items[0]?.goods.name + (items.length > 1 ? ` 외 ${items.length - 1}건` : ''),
        successUrl: `${window.location.origin}/api/v1/payment/confirm?redirect=/order/complete`,
        failUrl: `${window.location.origin}/order/fail`,
        customerEmail: member?.email,
        customerName: form.recipient,
        customerMobilePhone: form.phone.replace(/-/g, ''),
      })
    } catch (err: any) {
      alert(err.message ?? '결제 오류')
      setLoading(false)
    }
  }

  const inputClass = "w-full border rounded-xl px-3.5 py-3 text-[14px] focus:outline-none"
  const inputStyle = { borderColor: '#e0dbd5', color: '#333' }

  return (
    <div style={{ backgroundColor: '#f7f4f1', minHeight: '100vh' }}>
      {/* 헤더 */}
      <div className="bg-white flex items-center px-4 py-3 border-b" style={{ borderColor: '#ebebeb' }}>
        <Link href="/cart" className="mr-3">
          <ChevronLeft className="w-5 h-5" style={{ color: '#333' }} />
        </Link>
        <h1 className="text-[16px] font-bold" style={{ color: '#222' }}>주문/결제</h1>
      </div>

      {/* 배송지 */}
      <div className="mt-2 bg-white px-4 py-4">
        <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>배송 정보</h2>
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
          <select value={form.memo} onChange={e => setForm(f => ({ ...f, memo: e.target.value }))}
            className={inputClass} style={inputStyle}>
            <option value="">배송 메모 선택</option>
            <option value="문 앞에 놓아주세요">문 앞에 놓아주세요</option>
            <option value="경비실에 맡겨주세요">경비실에 맡겨주세요</option>
            <option value="택배함에 넣어주세요">택배함에 넣어주세요</option>
            <option value="직접 받겠습니다">직접 받겠습니다</option>
          </select>
        </div>
      </div>

      {/* 주문 상품 */}
      <div className="mt-2 bg-white px-4 py-4">
        <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>주문 상품 ({items.length})</h2>
        <div className="space-y-2">
          {items.map(item => (
            <div key={item.id} className="flex justify-between text-[13px]">
              <span style={{ color: '#555' }} className="flex-1 pr-4 leading-snug">
                {item.goods.name} {item.goods_options && `(${item.goods_options.name})`} × {item.qty}
              </span>
              <span className="font-medium flex-shrink-0" style={{ color: '#333' }}>
                {formatPrice(((item.goods.sale_price ?? item.goods.price) + (item.goods_options?.price_delta ?? 0)) * item.qty)}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* 마일리지 */}
      {member && member.mileage > 0 && (
        <div className="mt-2 bg-white px-4 py-4">
          <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>마일리지 사용</h2>
          <div className="flex items-center gap-2">
            <span className="text-[12px] flex-shrink-0" style={{ color: '#888' }}>보유 {member.mileage.toLocaleString()}P</span>
            <input type="number" min={0} max={Math.min(member.mileage, goodsTotal + shippingFee)}
              value={mileageUse} onChange={e => setMileageUse(Number(e.target.value))}
              className="flex-1 border rounded-xl px-3 py-2.5 text-[14px] focus:outline-none" style={inputStyle} />
            <button onClick={() => setMileageUse(Math.min(member.mileage, goodsTotal + shippingFee))}
              className="flex-shrink-0 text-[12px] px-3 py-2.5 rounded-xl border"
              style={{ borderColor: '#968774', color: '#968774' }}>
              전액 사용
            </button>
          </div>
        </div>
      )}

      {/* 결제 금액 요약 */}
      <div className="mt-2 bg-white px-4 py-4">
        <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>결제 금액</h2>
        <div className="space-y-2 text-[13px]">
          <div className="flex justify-between">
            <span style={{ color: '#888' }}>상품금액</span>
            <span style={{ color: '#333' }}>{formatPrice(goodsTotal)}</span>
          </div>
          <div className="flex justify-between">
            <span style={{ color: '#888' }}>배송비</span>
            <span style={{ color: '#333' }}>{shippingFee === 0 ? '무료' : formatPrice(shippingFee)}</span>
          </div>
          {mileageUse > 0 && (
            <div className="flex justify-between" style={{ color: '#968774' }}>
              <span>마일리지 할인</span>
              <span>-{formatPrice(mileageUse)}</span>
            </div>
          )}
        </div>
        <div className="flex justify-between items-center mt-3 pt-3 border-t" style={{ borderColor: '#f0ece8' }}>
          <span className="text-[14px] font-bold" style={{ color: '#333' }}>최종 결제금액</span>
          <span className="text-[20px] font-bold" style={{ color: '#968774' }}>{formatPrice(totalAmount)}</span>
        </div>
      </div>

      <div style={{ height: '90px' }} />

      {/* 하단 결제 버튼 */}
      <div className="fixed bottom-14 left-0 right-0 z-40 bg-white px-4 py-3 border-t"
        style={{ borderColor: '#ebebeb', maxWidth: '640px', margin: '0 auto', left: '50%', transform: 'translateX(-50%)', width: '100%' }}>
        <button onClick={handleOrder} disabled={loading || items.length === 0}
          className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
          style={{ backgroundColor: '#968774' }}>
          {loading ? '처리 중...' : `${formatPrice(totalAmount)} 결제하기`}
        </button>
        <p className="text-center text-[11px] mt-1.5" style={{ color: '#bbb' }}>토스페이먼츠 안전 결제</p>
      </div>
    </div>
  )
}
