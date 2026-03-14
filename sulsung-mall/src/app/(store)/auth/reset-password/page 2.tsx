'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { createClient } from '@/lib/supabase/client'
import { SITE_NAME } from '@/constants'

export default function ResetPasswordPage() {
  const router = useRouter()
  const supabase = createClient()
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState(false)
  const [sessionReady, setSessionReady] = useState(false)

  useEffect(() => {
    // Supabase가 URL의 access_token으로 자동 세션 복구하길 기다림
    supabase.auth.onAuthStateChange((event) => {
      if (event === 'PASSWORD_RECOVERY') {
        setSessionReady(true)
      }
    })
  }, [supabase.auth])

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')

    if (password.length < 8) {
      setError('비밀번호는 8자 이상이어야 합니다.')
      return
    }
    if (password !== confirm) {
      setError('비밀번호가 일치하지 않습니다.')
      return
    }

    setLoading(true)
    const { error } = await supabase.auth.updateUser({ password })

    if (error) {
      setError('비밀번호 변경에 실패했습니다. 링크가 만료되었을 수 있습니다.')
      setLoading(false)
      return
    }

    setSuccess(true)
    setLoading(false)
    setTimeout(() => {
      router.push('/auth/login')
    }, 3000)
  }

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#f7f4f1' }}>
      <div className="flex-1 flex flex-col justify-center px-6 py-10">
        <div className="text-center mb-8">
          <Link href="/" className="text-[26px] font-bold" style={{ color: '#968774' }}>{SITE_NAME}</Link>
        </div>

        <div className="bg-white rounded-2xl p-6 shadow-sm max-w-md mx-auto w-full">
          {success ? (
            <div className="text-center py-4">
              <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style={{ backgroundColor: '#f0ebe4' }}>
                <svg className="w-8 h-8" style={{ color: '#968774' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h2 className="text-lg font-bold text-gray-900 mb-2">비밀번호가 변경되었습니다</h2>
              <p className="text-sm text-gray-500 mb-6">잠시 후 로그인 페이지로 이동합니다.</p>
              <Link href="/auth/login"
                className="inline-block px-6 py-2.5 rounded-xl text-sm font-semibold text-white"
                style={{ backgroundColor: '#968774' }}>
                로그인하기
              </Link>
            </div>
          ) : (
            <>
              <h2 className="text-lg font-bold text-gray-900 mb-2">새 비밀번호 설정</h2>
              <p className="text-sm text-gray-500 mb-6">새로운 비밀번호를 입력해주세요.</p>

              {!sessionReady && (
                <div className="text-[13px] px-4 py-3 rounded-xl mb-3" style={{ backgroundColor: '#fef9e7', color: '#b7950b' }}>
                  세션을 확인하고 있습니다...
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-3">
                {error && (
                  <div className="text-[13px] px-4 py-3 rounded-xl" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
                    {error}
                  </div>
                )}
                <div>
                  <label className="block text-xs text-gray-500 mb-1">새 비밀번호</label>
                  <input type="password" value={password} onChange={e => setPassword(e.target.value)}
                    placeholder="8자 이상 입력" required minLength={8}
                    className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                    style={{ borderColor: '#e0dbd5' }} />
                </div>
                <div>
                  <label className="block text-xs text-gray-500 mb-1">비밀번호 확인</label>
                  <input type="password" value={confirm} onChange={e => setConfirm(e.target.value)}
                    placeholder="비밀번호 재입력" required
                    className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                    style={{ borderColor: '#e0dbd5' }} />
                </div>
                <button type="submit" disabled={loading || !sessionReady}
                  className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
                  style={{ backgroundColor: '#968774' }}>
                  {loading ? '변경 중...' : '비밀번호 변경'}
                </button>
              </form>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
