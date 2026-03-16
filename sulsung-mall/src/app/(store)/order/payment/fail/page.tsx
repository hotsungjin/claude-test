'use client'

import { useSearchParams } from 'next/navigation'
import Link from 'next/link'

export default function PaymentFailPage() {
  const searchParams = useSearchParams()
  const code = searchParams.get('code')
  const message = searchParams.get('message')

  return (
    <div className="flex flex-col items-center justify-center min-h-screen px-5">
      <div className="w-16 h-16 rounded-full flex items-center justify-center mb-5"
        style={{ backgroundColor: '#fef0f0' }}>
        <span className="text-[28px]" style={{ color: '#e85050' }}>!</span>
      </div>
      <h1 className="text-[20px] font-bold mb-2" style={{ color: '#333' }}>결제에 실패했습니다</h1>
      <p className="text-[14px] mb-1" style={{ color: '#888' }}>{message ?? '알 수 없는 오류가 발생했습니다.'}</p>
      {code && <p className="text-[12px] mb-6" style={{ color: '#aaa' }}>오류 코드: {code}</p>}
      <div className="flex gap-3">
        <Link href="/cart"
          className="px-6 py-3 rounded-xl font-bold text-[15px] text-white"
          style={{ backgroundColor: '#968774' }}>
          장바구니로 돌아가기
        </Link>
      </div>
    </div>
  )
}
