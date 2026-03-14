'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { createClient } from '@/lib/supabase/client'
import { SITE_NAME } from '@/constants'

export default function LoginClient() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const redirect = searchParams.get('redirect') ?? '/'
  const supabase = createClient()

  const [phone, setPhone] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [needActivate, setNeedActivate] = useState(false)

  function formatPhone(value: string) {
    const nums = value.replace(/[^0-9]/g, '').slice(0, 11)
    if (nums.length <= 3) return nums
    if (nums.length <= 7) return `${nums.slice(0, 3)}-${nums.slice(3)}`
    return `${nums.slice(0, 3)}-${nums.slice(3, 7)}-${nums.slice(7)}`
  }

  function getCleanPhone(value: string) {
    return value.replace(/[^0-9]/g, '')
  }

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError('')
    setNeedActivate(false)

    try {
      const cleanPhone = getCleanPhone(phone)
      const { error: authError } = await supabase.auth.signInWithPassword({
        email: `${cleanPhone}@sulsung.internal`,
        password,
      })

      if (authError) {
        // 로그인 실패 시 마이그레이션 회원인지 확인
        const res = await fetch('/api/v1/auth/check-migrate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ phone: cleanPhone }),
        })
        const data = await res.json()

        if (data.needActivate) {
          setNeedActivate(true)
          setError('')
        } else {
          setError('휴대폰 번호 또는 비밀번호가 올바르지 않습니다.')
        }
        setLoading(false)
        return
      }
      window.location.href = redirect
    } catch {
      setError('로그인 중 오류가 발생했습니다. 다시 시도해주세요.')
      setLoading(false)
    }
  }

  async function handleSocial(provider: 'kakao' | 'google') {
    await supabase.auth.signInWithOAuth({
      provider,
      options: { redirectTo: `${window.location.origin}/api/v1/auth/callback?redirect=${redirect}` },
    })
  }

  const inputStyle = {
    borderColor: '#e0dbd5',
    color: '#333',
  }

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#f7f4f1' }}>
      {/* 로고 영역 */}
      <div className="flex-1 flex flex-col justify-center px-6 py-10">
        <div className="text-center mb-8">
          <Link href="/" className="text-[26px] font-bold" style={{ color: '#968774' }}>{SITE_NAME}</Link>
          <p className="text-[13px] mt-2" style={{ color: '#aaa' }}>신선함을 담은 설성목장의 이야기</p>
        </div>

        <div className="bg-white rounded-2xl p-6 shadow-sm">
          {/* 소셜 로그인 */}
          <div className="space-y-2.5 mb-5">
            <button onClick={() => handleSocial('kakao')}
              className="w-full flex items-center justify-center gap-3 py-3 rounded-xl font-medium text-[14px]"
              style={{ backgroundColor: '#FEE500', color: '#3C1E1E' }}>
              <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 3C6.48 3 2 6.48 2 10.8c0 2.74 1.6 5.14 4 6.6l-.83 3.06c-.07.27.19.5.44.37L9.5 18.8c.82.13 1.66.2 2.5.2 5.52 0 10-3.48 10-7.8S17.52 3 12 3z"/>
              </svg>
              카카오로 시작하기
            </button>
            <button onClick={() => handleSocial('google')}
              className="w-full flex items-center justify-center gap-3 py-3 rounded-xl font-medium text-[14px] border"
              style={{ borderColor: '#e0dbd5', color: '#555', backgroundColor: '#fff' }}>
              <svg className="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              구글로 시작하기
            </button>
          </div>

          <div className="relative mb-5">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t" style={{ borderColor: '#f0ece8' }} />
            </div>
            <div className="relative flex justify-center text-[12px]" style={{ color: '#bbb' }}>
              <span className="bg-white px-3">또는 휴대폰 번호로 로그인</span>
            </div>
          </div>

          {/* 마이그레이션 회원 안내 */}
          {needActivate && (
            <div className="mb-4 p-4 rounded-xl" style={{ backgroundColor: '#fef9f0', border: '1px solid #f0e4cc' }}>
              <p className="text-[14px] font-semibold mb-1" style={{ color: '#968774' }}>
                기존 설성목장 회원이시군요!
              </p>
              <p className="text-[12px] mb-3" style={{ color: '#a09080' }}>
                새로운 쇼핑몰로 변경되어 본인인증 후 비밀번호를 새로 설정해주셔야 합니다.
              </p>
              <Link
                href={`/auth/activate?phone=${encodeURIComponent(getCleanPhone(phone))}`}
                className="inline-block px-4 py-2.5 rounded-xl text-[13px] font-semibold text-white"
                style={{ backgroundColor: '#968774' }}>
                비밀번호 설정하기
              </Link>
            </div>
          )}

          <form onSubmit={handleLogin} className="space-y-3">
            {error && (
              <div className="text-[13px] px-4 py-3 rounded-xl" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
                {error}
              </div>
            )}
            <input type="tel" value={phone}
              onChange={e => setPhone(formatPhone(e.target.value))}
              placeholder="휴대폰 번호" required
              className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
              style={inputStyle} />
            <input type="password" value={password} onChange={e => setPassword(e.target.value)}
              placeholder="비밀번호" required
              className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
              style={inputStyle} />
            <button type="submit" disabled={loading}
              className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
              style={{ backgroundColor: '#968774' }}>
              {loading ? '로그인 중...' : '로그인'}
            </button>
          </form>

          <div className="mt-4 text-center">
            <Link href="/auth/forgot-password" className="text-[12px]" style={{ color: '#bbb' }}>
              비밀번호를 잊으셨나요?
            </Link>
          </div>
          <div className="mt-3 text-center text-[13px]" style={{ color: '#aaa' }}>
            아직 회원이 아니신가요?{' '}
            <Link href="/auth/signup" className="font-semibold" style={{ color: '#968774' }}>회원가입</Link>
          </div>
        </div>
      </div>
    </div>
  )
}
