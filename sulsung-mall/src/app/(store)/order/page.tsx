'use client'

import { useEffect, useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { ChevronLeft, ChevronDown } from 'lucide-react'
import Link from 'next/link'
import { formatPrice } from '@/utils/format'
import { BASE_SHIPPING_FEE, FREE_SHIPPING_THRESHOLD } from '@/constants'

declare global { interface Window { TossPayments: any; daum: any } }

interface CartItem {
  id: string; qty: number
  goods: { id: string; name: string; price: number; sale_price: number | null; stock: number }
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
  const [memo, setMemo] = useState('')
  const [mileageUse, setMileageUse] = useState(0)
  const [loading, setLoading] = useState(false)
  const [pageLoading, setPageLoading] = useState(true)
  const [showOrdererInfo, setShowOrdererInfo] = useState(false)
  const tossRef = useRef<any>(null)

  useEffect(() => {
    Promise.all([
      fetch('/api/v1/cart').then(r => r.json()),
      fetch('/api/v1/members/me').then(r => r.json()),
      fetch('/api/v1/addresses/default').then(r => r.json()),
    ]).then(([cartData, memberData, addrData]) => {
      setItems(cartData.items ?? [])
      setMember(memberData.member)
      if (addrData.data) {
        const addr = addrData.data
        setDefaultAddress(addr)
        setForm({ recipient: addr.name, phone: addr.phone, zipcode: addr.zipcode, address1: addr.address1, address2: addr.address2 ?? '' })
      } else if (memberData.member) {
        setForm(f => ({ ...f, recipient: memberData.member.name ?? '', phone: memberData.member.phone ?? '' }))
        setEditingAddress(true)
      }
      setPageLoading(false)
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
          address1: form.address1, address2: form.address2, deliveryMemo: memo,
          mileageUse,
        }),
      })
      const orderData = await orderRes.json()

      if (orderData.orderId) {
        await fetch('/api/v1/orders/skip-payment', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orderId: orderData.orderId }),
        })
        router.push(`/order/complete?orderId=${orderData.orderId}`)
      } else {
        // 주문 생성 실패해도 기획용으로 완료 페이지 이동
        router.push('/order/complete')
      }
    } catch {
      router.push('/order/complete')
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
      </div>
    )
  }

  return (
    <div className="flex flex-col" style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      {/* 헤더 */}
      <div className="sticky top-0 z-50 bg-white flex items-center h-[48px] px-4" style={{ borderBottom: '1px solid #ebebeb' }}>
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
            className="w-full flex items-center justify-between px-4 py-4"
          >
            <span className="text-[15px] font-bold" style={{ color: '#333' }}>주문자 정보</span>
            <span className="flex items-center gap-1">
              <span className="text-[14px]" style={{ color: '#333' }}>{member.name}, {member.phone}</span>
              <ChevronDown className={`w-4 h-4 transition-transform ${showOrdererInfo ? 'rotate-180' : ''}`} style={{ color: '#999' }} />
            </span>
          </button>
          {showOrdererInfo && (
            <div className="px-4 pb-4 text-[13px]" style={{ color: '#666' }}>
              <p>이름: {member.name}</p>
              <p>연락처: {member.phone}</p>
              <p>이메일: {member.email}</p>
            </div>
          )}
        </div>
      )}

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 배송지 */}
      <div className="bg-white px-4 py-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-[15px] font-bold" style={{ color: '#333' }}>배송지</h2>
          {!editingAddress && (
            <Link href="/mypage/addresses" className="text-[13px]" style={{ color: '#888' }}>배송지 변경 안내 ⓘ</Link>
          )}
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
                onClick={() => router.push('/mypage/addresses')}
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

      {/* 배송 요청사항 */}
      <div className="bg-white px-4 py-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-[15px] font-bold" style={{ color: '#333' }}>배송 요청사항</h2>
          <Link href="/mypage/addresses" className="text-[13px]" style={{ color: '#968774' }}>수정</Link>
        </div>
        <select value={memo} onChange={e => setMemo(e.target.value)}
          className={inputClass} style={inputStyle}>
          <option value="">배송 시 요청사항을 선택해주세요</option>
          <option value="문 앞에 놓아주세요">문 앞에 놓아주세요</option>
          <option value="경비실에 맡겨주세요">경비실에 맡겨주세요</option>
          <option value="택배함에 넣어주세요">택배함에 넣어주세요</option>
          <option value="직접 받겠습니다">직접 받겠습니다</option>
        </select>
        {defaultAddress && (
          <p className="text-[13px] mt-2" style={{ color: '#555' }}>
            {defaultAddress.name}, {defaultAddress.phone}
          </p>
        )}
      </div>

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 주문 상품 */}
      <div className="bg-white px-4 py-4">
        <h2 className="text-[15px] font-bold mb-3" style={{ color: '#333' }}>주문상품</h2>
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

      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 마일리지 */}
      {member && member.mileage > 0 && (
        <>
          <div className="bg-white px-4 py-4">
            <h2 className="text-[15px] font-bold mb-3" style={{ color: '#333' }}>포인트 사용</h2>
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
          <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />
        </>
      )}

      {/* 결제 금액 요약 */}
      <div className="bg-white px-4 py-4">
        <h2 className="text-[15px] font-bold mb-3" style={{ color: '#333' }}>결제 금액</h2>
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
              <span>포인트 할인</span>
              <span>-{formatPrice(mileageUse)}</span>
            </div>
          )}
        </div>
        <div className="flex justify-between items-center mt-3 pt-3 border-t" style={{ borderColor: '#f0ece8' }}>
          <span className="text-[14px] font-bold" style={{ color: '#333' }}>최종 결제금액</span>
          <span className="text-[20px] font-bold" style={{ color: '#968774' }}>{formatPrice(totalAmount)}</span>
        </div>
      </div>

      </div>

      {/* 하단 결제 버튼 */}
      <div className="sticky bottom-0 z-40 bg-white px-4 py-3 border-t"
        style={{ borderColor: '#ebebeb' }}>
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
