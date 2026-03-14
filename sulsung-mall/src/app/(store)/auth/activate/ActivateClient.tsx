'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { createClient } from '@/lib/supabase/client'
import { SITE_NAME } from '@/constants'

type Step = 'phone' | 'info' | 'done'

export default function ActivateClient() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const supabase = createClient()

  const [step, setStep] = useState<Step>('phone')

  const [phone, setPhone] = useState(searchParams.get('phone') ?? '')
  const [codeSent, setCodeSent] = useState(false)
  const [code, setCode] = useState('')
  const [verified, setVerified] = useState(false)
  const [verificationId, setVerificationId] = useState<number | null>(null)
  const [timer, setTimer] = useState(0)
  const [timerRef, setTimerRef] = useState<ReturnType<typeof setInterval> | null>(null)

  const [password, setPassword] = useState('')
  const [passwordConfirm, setPasswordConfirm] = useState('')
  const [memberName, setMemberName] = useState('')

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const inputStyle = { borderColor: '#e0dbd5', color: '#333' }

  function formatPhone(value: string) {
    const nums = value.replace(/[^0-9]/g, '').slice(0, 11)
    if (nums.length <= 3) return nums
    if (nums.length <= 7) return `${nums.slice(0, 3)}-${nums.slice(3)}`
    return `${nums.slice(0, 3)}-${nums.slice(3, 7)}-${nums.slice(7)}`
  }

  function getCleanPhone(value: string) {
    return value.replace(/[^0-9]/g, '')
  }

  function startTimer() {
    if (timerRef) clearInterval(timerRef)
    setTimer(180)
    const ref = setInterval(() => {
      setTimer(prev => {
        if (prev <= 1) { clearInterval(ref); return 0 }
        return prev - 1
      })
    }, 1000)
    setTimerRef(ref)
  }

  function formatTimer(sec: number) {
    return `${Math.floor(sec / 60)}:${(sec % 60).toString().padStart(2, '0')}`
  }

  async function handleSendCode() {
    const cleanPhone = getCleanPhone(phone)
    if (cleanPhone.length < 10) { setError('올바른 휴대폰 번호를 입력해주세요.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/send-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: cleanPhone, purpose: 'activate' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setCodeSent(true)
    startTimer()
  }

  async function handleVerifyCode() {
    if (code.length !== 6) { setError('6자리 인증번호를 입력해주세요.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/verify-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: getCleanPhone(phone), code, purpose: 'activate' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setVerified(true)
    setVerificationId(data.verificationId)
    if (timerRef) clearInterval(timerRef)
    setStep('info')
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (password !== passwordConfirm) { setError('비밀번호가 일치하지 않습니다.'); return }
    if (password.length < 8) { setError('비밀번호는 8자 이상이어야 합니다.'); return }

    setLoading(true); setError('')

    const res = await fetch('/api/v1/auth/activate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        phone: getCleanPhone(phone),
        password,
        verificationId,
      }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setMemberName(data.name || '')
    setStep('done')
  }

  async function handleLoginNow() {
    setLoading(true)
    const { error } = await supabase.auth.signInWithPassword({
      email: `${getCleanPhone(phone)}@sulsung.internal`,
      password,
    })
    if (error) {
      router.push('/auth/login')
    } else {
      window.location.href = '/'
    }
  }

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#f7f4f1' }}>
      <div className="flex-1 flex flex-col justify-center px-6 py-10">
        <div className="text-center mb-6">
          <Link href="/" className="text-[24px] font-bold" style={{ color: '#968774' }}>{SITE_NAME}</Link>
          <p className="text-[13px] mt-1" style={{ color: '#aaa' }}>기존 회원 비밀번호 설정</p>
        </div>

        {/* 스텝 인디케이터 */}
        <div className="flex items-center justify-center gap-2 mb-6">
          {(['본인인증', '비밀번호 설정', '완료'] as const).map((label, i) => {
            const steps: Step[] = ['phone', 'info', 'done']
            const isActive = steps.indexOf(step) >= i
            return (
              <div key={label} className="flex items-center gap-2">
                {i > 0 && <div className="w-6 h-px" style={{ backgroundColor: isActive ? '#968774' : '#ddd' }} />}
                <div className="flex items-center gap-1.5">
                  <div className="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
                    style={{ backgroundColor: isActive ? '#968774' : '#ddd' }}>
                    {i + 1}
                  </div>
                  <span className="text-[12px]" style={{ color: isActive ? '#968774' : '#bbb' }}>{label}</span>
                </div>
              </div>
            )
          })}
        </div>

        <div className="bg-white rounded-2xl p-6 shadow-sm">
          {step !== 'done' && (
            <div className="mb-4 p-4 rounded-xl" style={{ backgroundColor: '#fef9f0', border: '1px solid #f0e4cc' }}>
              <p className="text-[13px]" style={{ color: '#a09080' }}>
                기존 설성목장 회원님의 정보가 새 쇼핑몰로 이전되었습니다.
                본인인증 후 비밀번호를 설정하시면 바로 이용하실 수 있습니다.
              </p>
            </div>
          )}

          {error && (
            <div className="text-[13px] px-4 py-3 rounded-xl mb-4" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
              {error}
            </div>
          )}

          {/* Step 1: 본인인증 */}
          {step === 'phone' && (
            <div className="space-y-3">
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>휴대폰 번호</label>
                <div className="flex gap-2">
                  <input type="tel" value={formatPhone(phone)}
                    onChange={e => setPhone(getCleanPhone(e.target.value))}
                    placeholder="010-1234-5678"
                    disabled={verified}
                    className="flex-1 border rounded-xl px-4 py-3 text-[14px] focus:outline-none disabled:bg-gray-50"
                    style={inputStyle} />
                  <button onClick={handleSendCode}
                    disabled={loading || verified || getCleanPhone(phone).length < 10}
                    className="px-4 py-3 rounded-xl text-[13px] font-medium text-white disabled:opacity-50 whitespace-nowrap"
                    style={{ backgroundColor: '#968774' }}>
                    {loading ? '발송중...' : codeSent ? '재발송' : '인증요청'}
                  </button>
                </div>
              </div>

              {codeSent && !verified && (
                <div>
                  <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>
                    인증번호 {timer > 0 && <span style={{ color: '#e84a3b' }}>({formatTimer(timer)})</span>}
                  </label>
                  <div className="flex gap-2">
                    <input type="text" value={code}
                      onChange={e => setCode(e.target.value.replace(/[^0-9]/g, '').slice(0, 6))}
                      placeholder="6자리 입력" maxLength={6}
                      className="flex-1 border rounded-xl px-4 py-3 text-[14px] focus:outline-none tracking-[0.3em]"
                      style={inputStyle} />
                    <button onClick={handleVerifyCode}
                      disabled={loading || code.length !== 6 || timer === 0}
                      className="px-4 py-3 rounded-xl text-[13px] font-medium text-white disabled:opacity-50 whitespace-nowrap"
                      style={{ backgroundColor: '#968774' }}>
                      {loading ? '확인중...' : '확인'}
                    </button>
                  </div>
                </div>
              )}

              <div className="pt-2">
                <Link href="/auth/login" className="block text-center py-2 text-[13px]" style={{ color: '#aaa' }}>
                  로그인으로 돌아가기
                </Link>
              </div>
            </div>
          )}

          {/* Step 2: 비밀번호 설정 */}
          {step === 'info' && (
            <form onSubmit={handleSubmit} className="space-y-3">
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>휴대폰 번호</label>
                <input type="tel" value={formatPhone(phone)} disabled
                  className="w-full border rounded-xl px-4 py-3 text-[14px] bg-gray-50"
                  style={inputStyle} />
              </div>
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>새 비밀번호 (8자 이상)</label>
                <input type="password" value={password} onChange={e => setPassword(e.target.value)}
                  required
                  className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                  style={inputStyle} />
              </div>
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>비밀번호 확인</label>
                <input type="password" value={passwordConfirm} onChange={e => setPasswordConfirm(e.target.value)}
                  required
                  className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                  style={inputStyle} />
              </div>
              <button type="submit" disabled={loading}
                className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
                style={{ backgroundColor: '#968774' }}>
                {loading ? '설정 중...' : '비밀번호 설정 완료'}
              </button>
              <button type="button" onClick={() => setStep('phone')}
                className="w-full py-2 text-[13px]" style={{ color: '#aaa' }}>
                이전
              </button>
            </form>
          )}

          {/* Step 3: 완료 */}
          {step === 'done' && (
            <div className="text-center py-4">
              <div className="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style={{ backgroundColor: '#f0fdf4' }}>
                <svg className="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h2 className="text-[18px] font-bold mb-1" style={{ color: '#333' }}>
                {memberName ? `${memberName}님, ` : ''}환영합니다!
              </h2>
              <p className="text-[13px] mb-6" style={{ color: '#999' }}>
                비밀번호가 설정되었습니다. 이제 로그인하실 수 있습니다.
              </p>
              <button onClick={handleLoginNow} disabled={loading}
                className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
                style={{ backgroundColor: '#968774' }}>
                {loading ? '로그인 중...' : '바로 로그인하기'}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
