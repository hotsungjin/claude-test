'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { SITE_NAME } from '@/constants'

type Step = 'phone' | 'verify' | 'reset' | 'done'

export default function ForgotPasswordPage() {
  const [step, setStep] = useState<Step>('phone')
  const [phone, setPhone] = useState('')
  const [code, setCode] = useState('')
  const [verificationId, setVerificationId] = useState<number | null>(null)
  const [newPassword, setNewPassword] = useState('')
  const [newPasswordConfirm, setNewPasswordConfirm] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [countdown, setCountdown] = useState(0)
  const [codeSent, setCodeSent] = useState(false)

  const inputStyle = { borderColor: '#e0dbd5', color: '#333' }

  useEffect(() => {
    if (countdown <= 0) return
    const t = setTimeout(() => setCountdown(c => c - 1), 1000)
    return () => clearTimeout(t)
  }, [countdown])

  function formatPhone(value: string) {
    const nums = value.replace(/[^0-9]/g, '').slice(0, 11)
    if (nums.length <= 3) return nums
    if (nums.length <= 7) return `${nums.slice(0, 3)}-${nums.slice(3)}`
    return `${nums.slice(0, 3)}-${nums.slice(3, 7)}-${nums.slice(7)}`
  }

  function getCleanPhone(value: string) {
    return value.replace(/[^0-9]/g, '')
  }

  async function handleSendCode() {
    const cleanPhone = getCleanPhone(phone)
    if (cleanPhone.length < 10) { setError('올바른 휴대폰 번호를 입력해주세요.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/send-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: cleanPhone, purpose: 'reset_password' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setCodeSent(true)
    setCountdown(180)
    setStep('verify')
  }

  async function handleResendCode() {
    const cleanPhone = getCleanPhone(phone)
    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/send-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: cleanPhone, purpose: 'reset_password' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setCode('')
    setCountdown(180)
  }

  async function handleVerifyCode() {
    if (code.length !== 6) { setError('인증번호 6자리를 입력해주세요.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/verify-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: getCleanPhone(phone), code, purpose: 'reset_password' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setVerificationId(data.verificationId)
    setStep('reset')
  }

  async function handleResetPassword(e: React.FormEvent) {
    e.preventDefault()
    if (newPassword.length < 8) { setError('비밀번호는 8자 이상이어야 합니다.'); return }
    if (newPassword !== newPasswordConfirm) { setError('비밀번호가 일치하지 않습니다.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/reset-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        phone: getCleanPhone(phone),
        password: newPassword,
        verificationId,
      }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setStep('done')
  }

  const mm = String(Math.floor(countdown / 60)).padStart(2, '0')
  const ss = String(countdown % 60).padStart(2, '0')

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#f7f4f1' }}>
      <div className="flex-1 flex flex-col justify-center px-6 py-10">
        <div className="text-center mb-8">
          <Link href="/" className="text-[26px] font-bold" style={{ color: '#968774' }}>{SITE_NAME}</Link>
        </div>

        <div className="bg-white rounded-2xl p-6 shadow-sm max-w-md mx-auto w-full">
          {step === 'done' ? (
            <div className="text-center py-4">
              <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style={{ backgroundColor: '#f0ebe4' }}>
                <svg className="w-8 h-8" style={{ color: '#968774' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h2 className="text-lg font-bold text-gray-900 mb-2">비밀번호가 변경되었습니다</h2>
              <p className="text-sm text-gray-500 mb-6">새 비밀번호로 로그인해주세요.</p>
              <Link href="/auth/login"
                className="inline-block px-6 py-2.5 rounded-xl text-sm font-semibold text-white"
                style={{ backgroundColor: '#968774' }}>
                로그인하기
              </Link>
            </div>
          ) : (
            <>
              <h2 className="text-lg font-bold text-gray-900 mb-2">비밀번호 재설정</h2>
              <p className="text-sm text-gray-500 mb-6">
                {step === 'phone' && '가입하신 휴대폰 번호를 입력해주세요.'}
                {step === 'verify' && '인증번호를 입력해주세요.'}
                {step === 'reset' && '새 비밀번호를 입력해주세요.'}
              </p>

              {error && (
                <div className="text-[13px] px-4 py-3 rounded-xl mb-4" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
                  {error}
                </div>
              )}

              {/* Step 1: 휴대폰 번호 입력 */}
              {step === 'phone' && (
                <div className="space-y-3">
                  <input type="tel" value={phone}
                    onChange={e => setPhone(formatPhone(e.target.value))}
                    placeholder="가입하신 휴대폰 번호"
                    className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                    style={inputStyle} />
                  <button onClick={handleSendCode}
                    disabled={getCleanPhone(phone).length < 10 || loading}
                    className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
                    style={{ backgroundColor: '#968774' }}>
                    {loading ? '발송 중...' : '인증번호 받기'}
                  </button>
                </div>
              )}

              {/* Step 2: 인증번호 입력 */}
              {step === 'verify' && (
                <div className="space-y-3">
                  <div className="relative">
                    <input type="text" inputMode="numeric" maxLength={6} value={code}
                      onChange={e => setCode(e.target.value.replace(/[^0-9]/g, '').slice(0, 6))}
                      placeholder="인증번호 6자리"
                      className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none pr-20"
                      style={inputStyle} />
                    {countdown > 0 && (
                      <span className="absolute right-4 top-1/2 -translate-y-1/2 text-[13px] font-medium" style={{ color: '#e84a3b' }}>
                        {mm}:{ss}
                      </span>
                    )}
                  </div>
                  <div className="flex gap-2">
                    <button onClick={handleResendCode}
                      disabled={loading || countdown > 150}
                      className="flex-1 py-3 rounded-xl font-semibold text-[14px] border disabled:opacity-40"
                      style={{ borderColor: '#e0dbd5', color: '#968774' }}>
                      재발송
                    </button>
                    <button onClick={handleVerifyCode}
                      disabled={code.length !== 6 || loading || countdown <= 0}
                      className="flex-1 py-3 rounded-xl font-semibold text-[14px] text-white disabled:opacity-50"
                      style={{ backgroundColor: '#968774' }}>
                      {loading ? '확인 중...' : '확인'}
                    </button>
                  </div>
                  {countdown <= 0 && codeSent && (
                    <p className="text-[12px] text-center" style={{ color: '#e84a3b' }}>
                      인증시간이 만료되었습니다. 재발송 해주세요.
                    </p>
                  )}
                </div>
              )}

              {/* Step 3: 새 비밀번호 입력 */}
              {step === 'reset' && (
                <form onSubmit={handleResetPassword} className="space-y-3">
                  <div>
                    <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>새 비밀번호 (8자 이상)</label>
                    <input type="password" value={newPassword}
                      onChange={e => setNewPassword(e.target.value)}
                      required
                      className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                      style={inputStyle} />
                  </div>
                  <div>
                    <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>비밀번호 확인</label>
                    <input type="password" value={newPasswordConfirm}
                      onChange={e => setNewPasswordConfirm(e.target.value)}
                      required
                      className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                      style={inputStyle} />
                  </div>
                  <button type="submit" disabled={loading}
                    className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50"
                    style={{ backgroundColor: '#968774' }}>
                    {loading ? '변경 중...' : '비밀번호 변경'}
                  </button>
                </form>
              )}

              <div className="mt-4 text-center">
                <Link href="/auth/login" className="text-[13px]" style={{ color: '#968774' }}>
                  로그인으로 돌아가기
                </Link>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
