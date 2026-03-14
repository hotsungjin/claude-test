'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { SITE_NAME } from '@/constants'

export default function DormantPage() {
  const router = useRouter()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const handleReactivate = async () => {
    setLoading(true)
    setError('')
    try {
      const res = await fetch('/api/v1/auth/reactivate', { method: 'POST' })
      const data = await res.json()
      if (res.ok) {
        router.push('/mypage')
        router.refresh()
      } else {
        setError(data.error ?? '재활성화에 실패했습니다.')
      }
    } catch {
      setError('오류가 발생했습니다. 다시 시도해주세요.')
    }
    setLoading(false)
  }

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#f7f4f1' }}>
      <div className="flex-1 flex flex-col justify-center px-6 py-10">
        <div className="text-center mb-8">
          <Link href="/" className="text-[26px] font-bold" style={{ color: '#968774' }}>{SITE_NAME}</Link>
        </div>

        <div className="bg-white rounded-2xl p-6 shadow-sm max-w-md mx-auto w-full text-center">
          <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style={{ backgroundColor: '#fef3cd' }}>
            <svg className="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>

          <h2 className="text-lg font-bold text-gray-900 mb-2">휴면 계정 안내</h2>
          <p className="text-sm text-gray-500 leading-relaxed mb-6">
            장기간 미접속으로 계정이 휴면 상태로 전환되었습니다.<br />
            아래 버튼을 눌러 계정을 다시 활성화해주세요.
          </p>

          {error && (
            <div className="text-[13px] px-4 py-3 rounded-xl mb-4" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
              {error}
            </div>
          )}

          <button
            onClick={handleReactivate}
            disabled={loading}
            className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50 mb-3"
            style={{ backgroundColor: '#968774' }}
          >
            {loading ? '처리 중...' : '계정 재활성화'}
          </button>

          <p className="text-xs text-gray-400">
            기존 포인트와 쿠폰은 유효기간 내 그대로 유지됩니다.
          </p>
        </div>
      </div>
    </div>
  )
}
