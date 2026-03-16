'use client'

import { useEffect, useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'

export default function PaymentSuccessPage() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const paymentKey = searchParams.get('paymentKey')
    const orderId = searchParams.get('orderId')
    const amount = searchParams.get('amount')

    if (!paymentKey || !orderId || !amount) {
      setError('결제 정보가 올바르지 않습니다.')
      return
    }

    fetch('/api/v1/payment/confirm', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        paymentKey,
        orderId,
        amount: Number(amount),
      }),
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          router.replace(`/order/complete?orderId=${orderId}`)
        } else {
          setError(data.error ?? '결제 승인에 실패했습니다.')
        }
      })
      .catch(() => setError('결제 승인 중 오류가 발생했습니다.'))
  }, [searchParams, router])

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen px-5">
        <p className="text-[16px] font-bold mb-4" style={{ color: '#e85050' }}>{error}</p>
        <button
          onClick={() => router.push('/cart')}
          className="px-6 py-3 rounded-xl text-white font-bold"
          style={{ backgroundColor: '#968774' }}
        >
          장바구니로 돌아가기
        </button>
      </div>
    )
  }

  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="text-center">
        <div className="w-8 h-8 rounded-full border-2 border-t-transparent animate-spin mx-auto mb-4"
          style={{ borderColor: '#968774', borderTopColor: 'transparent' }} />
        <p className="text-[15px]" style={{ color: '#666' }}>결제를 확인하고 있습니다...</p>
      </div>
    </div>
  )
}
