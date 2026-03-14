'use client'

export const dynamic = 'force-dynamic'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { SITE_NAME } from '@/constants'

type Step = 'agree' | 'phone' | 'info'

export default function SignupPage() {
  const router = useRouter()

  const [step, setStep] = useState<Step>('agree')
  const [agree, setAgree] = useState({ terms: false, privacy: false, marketing: false })

  // 폰 인증
  const [phone, setPhone] = useState('')
  const [codeSent, setCodeSent] = useState(false)
  const [code, setCode] = useState('')
  const [verified, setVerified] = useState(false)
  const [verificationId, setVerificationId] = useState<number | null>(null)
  const [timer, setTimer] = useState(0)
  const [timerRef, setTimerRef] = useState<ReturnType<typeof setInterval> | null>(null)

  // 회원정보
  const [name, setName] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirm, setPasswordConfirm] = useState('')

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
    setTimer(180) // 3분
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

  // 1단계: 약관 동의
  function handleAgreeNext() {
    if (!agree.terms || !agree.privacy) {
      setError('필수 약관에 동의해주세요.')
      return
    }
    setError('')
    setStep('phone')
  }

  // 2단계: 인증번호 발송
  async function handleSendCode() {
    const cleanPhone = getCleanPhone(phone)
    if (cleanPhone.length < 10) { setError('올바른 휴대폰 번호를 입력해주세요.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/send-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: cleanPhone, purpose: 'signup' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setCodeSent(true)
    startTimer()
  }

  // 2단계: 인증번호 확인
  async function handleVerifyCode() {
    if (code.length !== 6) { setError('6자리 인증번호를 입력해주세요.'); return }

    setLoading(true); setError('')
    const res = await fetch('/api/v1/auth/verify-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: getCleanPhone(phone), code, purpose: 'signup' }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    setVerified(true)
    setVerificationId(data.verificationId)
    if (timerRef) clearInterval(timerRef)
    setStep('info')
  }

  // 3단계: 회원가입 완료
  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (password !== passwordConfirm) { setError('비밀번호가 일치하지 않습니다.'); return }
    if (password.length < 8) { setError('비밀번호는 8자 이상이어야 합니다.'); return }
    if (!name.trim()) { setError('이름을 입력해주세요.'); return }

    setLoading(true); setError('')

    const res = await fetch('/api/v1/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        phone: getCleanPhone(phone),
        password,
        name,
        verificationId,
        marketingSms: agree.marketing,
        marketingKakao: agree.marketing,
      }),
    })
    const data = await res.json()
    setLoading(false)

    if (!res.ok) { setError(data.error); return }
    router.push('/auth/signup/complete')
  }

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#f7f4f1' }}>
      <div className="flex-1 flex flex-col justify-center px-6 py-10">
        <div className="text-center mb-6">
          <Link href="/" className="text-[24px] font-bold" style={{ color: '#968774' }}>{SITE_NAME}</Link>
          <p className="text-[13px] mt-1" style={{ color: '#aaa' }}>회원가입</p>
        </div>

        {/* 스텝 인디케이터 */}
        <div className="flex items-center justify-center gap-2 mb-6">
          {(['약관동의', '본인인증', '정보입력'] as const).map((label, i) => {
            const steps: Step[] = ['agree', 'phone', 'info']
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
          {error && (
            <div className="text-[13px] px-4 py-3 rounded-xl mb-4" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
              {error}
            </div>
          )}

          {/* Step 1: 약관 동의 */}
          {step === 'agree' && (
            <div>
              <div className="border rounded-xl p-4 space-y-3" style={{ borderColor: '#f0ece8' }}>
                <label className="flex items-center gap-3 cursor-pointer">
                  <input type="checkbox"
                    checked={agree.terms && agree.privacy && agree.marketing}
                    onChange={e => setAgree({ terms: e.target.checked, privacy: e.target.checked, marketing: e.target.checked })}
                    className="w-4 h-4" style={{ accentColor: '#968774' }}
                  />
                  <span className="text-[14px] font-semibold" style={{ color: '#333' }}>전체 동의</span>
                </label>
                <hr style={{ borderColor: '#f0ece8' }} />
                {[
                  { key: 'terms', label: '이용약관 동의', required: true, href: '/policy/terms' },
                  { key: 'privacy', label: '개인정보 처리방침 동의', required: true, href: '/policy/privacy' },
                  { key: 'marketing', label: '마케팅 정보 수신 동의', required: false, href: undefined },
                ].map(({ key, label, required, href }) => (
                  <label key={key} className="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                      checked={(agree as any)[key]}
                      onChange={e => setAgree(prev => ({ ...prev, [key]: e.target.checked }))}
                      className="w-4 h-4" style={{ accentColor: '#968774' }}
                    />
                    <span className="text-[13px]" style={{ color: '#666' }}>
                      <span style={{ color: required ? '#e84a3b' : '#aaa' }}>{required ? '[필수] ' : '[선택] '}</span>
                      {href ? <Link href={href} className="underline">{label}</Link> : label}
                    </span>
                  </label>
                ))}
              </div>
              <button onClick={handleAgreeNext}
                className="w-full mt-4 py-3.5 rounded-xl font-semibold text-[15px] text-white"
                style={{ backgroundColor: '#968774' }}>
                다음
              </button>
            </div>
          )}

          {/* Step 2: 휴대폰 인증 */}
          {step === 'phone' && (
            <div className="space-y-3">
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>휴대폰 번호</label>
                <div className="flex gap-2">
                  <input type="tel" value={phone}
                    onChange={e => setPhone(formatPhone(e.target.value))}
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

              {verified && (
                <div className="flex items-center gap-2 px-4 py-3 rounded-xl" style={{ backgroundColor: '#f0fdf4' }}>
                  <svg className="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                  <span className="text-[13px] text-green-700">인증이 완료되었습니다.</span>
                </div>
              )}

              <button onClick={() => setStep('agree')}
                className="w-full py-2 text-[13px]" style={{ color: '#aaa' }}>
                이전
              </button>
            </div>
          )}

          {/* Step 3: 회원정보 입력 */}
          {step === 'info' && (
            <form onSubmit={handleSubmit} className="space-y-3">
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>휴대폰 번호</label>
                <input type="tel" value={phone} disabled
                  className="w-full border rounded-xl px-4 py-3 text-[14px] bg-gray-50"
                  style={inputStyle} />
              </div>
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>이름</label>
                <input type="text" value={name} onChange={e => setName(e.target.value)}
                  placeholder="홍길동" required
                  className="w-full border rounded-xl px-4 py-3 text-[14px] focus:outline-none"
                  style={inputStyle} />
              </div>
              <div>
                <label className="block text-[12px] font-medium mb-1" style={{ color: '#888' }}>비밀번호 (8자 이상)</label>
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
                {loading ? '가입 중...' : '회원가입 완료'}
              </button>
              <button type="button" onClick={() => setStep('phone')}
                className="w-full py-2 text-[13px]" style={{ color: '#aaa' }}>
                이전
              </button>
            </form>
          )}

          <p className="mt-5 text-center text-[13px]" style={{ color: '#aaa' }}>
            이미 계정이 있으신가요?{' '}
            <Link href="/auth/login" className="font-semibold" style={{ color: '#968774' }}>로그인</Link>
          </p>
        </div>
      </div>
    </div>
  )
}
