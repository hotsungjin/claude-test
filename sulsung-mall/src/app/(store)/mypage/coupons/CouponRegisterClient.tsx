'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Tag } from 'lucide-react'

export default function CouponRegisterClient() {
  const router = useRouter()
  const [code, setCode] = useState('')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null)

  const register = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!code.trim()) return
    setLoading(true)
    setMessage(null)
    const res = await fetch('/api/v1/coupons/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code: code.trim().toUpperCase() }),
    })
    const json = await res.json()
    if (res.ok) {
      setMessage({ type: 'success', text: '쿠폰이 등록되었습니다!' })
      setCode('')
      router.refresh()
    } else {
      setMessage({ type: 'error', text: json.error ?? '쿠폰 등록 실패' })
    }
    setLoading(false)
  }

  return (
    <div className="rounded-xl p-4 border" style={{ backgroundColor: '#f0ebe4', borderColor: '#e0dbd5' }}>
      <form onSubmit={register} className="flex gap-2">
        <div className="relative flex-1">
          <Tag className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: '#968774' }} />
          <input value={code} onChange={e => setCode(e.target.value.toUpperCase())}
            placeholder="쿠폰 코드 입력"
            className="w-full pl-9 pr-3 py-2 border rounded-lg text-sm bg-white focus:outline-none font-mono uppercase"
            style={{ borderColor: '#c4b9a7' }}
            onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
            onBlur={e => (e.currentTarget.style.borderColor = '#c4b9a7')} />
        </div>
        <button type="submit" disabled={loading || !code.trim()}
          className="px-4 py-2 text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
          style={{ backgroundColor: '#968774' }}>
          등록
        </button>
      </form>
      {message && (
        <p className="text-xs mt-2 font-medium"
          style={{ color: message.type === 'success' ? '#968774' : '#dc2626' }}>
          {message.text}
        </p>
      )}
    </div>
  )
}
