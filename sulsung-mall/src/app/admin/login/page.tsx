'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { createClient } from '@/lib/supabase/client'

export default function AdminLoginPage() {
  const router = useRouter()
  const supabase = createClient()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError('')

    try {
      const { data, error: signInError } = await supabase.auth.signInWithPassword({
        email,
        password,
      })

      if (signInError) {
        setError('이메일 또는 비밀번호가 올바르지 않습니다.')
        setLoading(false)
        return
      }

      // admin_users 테이블에서 관리자 확인
      const { data: admin } = await supabase
        .from('admin_users')
        .select('id')
        .eq('auth_id', data.user.id)
        .single()

      if (!admin) {
        await supabase.auth.signOut()
        setError('관리자 권한이 없는 계정입니다.')
        setLoading(false)
        return
      }

      router.push('/admin/dashboard')
    } catch {
      setError('로그인 중 오류가 발생했습니다.')
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="bg-white rounded-2xl p-8 shadow-sm w-full max-w-sm">
        <h1 className="text-xl font-bold text-gray-900 text-center mb-1">관리자 로그인</h1>
        <p className="text-sm text-gray-400 text-center mb-6">설성목장몰 관리 시스템</p>

        {error && (
          <div className="text-[13px] px-4 py-3 rounded-xl mb-4" style={{ backgroundColor: '#fff0f0', color: '#e84a3b' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-3">
          <input
            type="email"
            value={email}
            onChange={e => setEmail(e.target.value)}
            placeholder="관리자 이메일"
            required
            className="w-full border border-gray-200 rounded-xl px-4 py-3 text-[14px] focus:outline-none focus:border-gray-400"
          />
          <input
            type="password"
            value={password}
            onChange={e => setPassword(e.target.value)}
            placeholder="비밀번호"
            required
            className="w-full border border-gray-200 rounded-xl px-4 py-3 text-[14px] focus:outline-none focus:border-gray-400"
          />
          <button
            type="submit"
            disabled={loading}
            className="w-full py-3.5 rounded-xl font-semibold text-[15px] text-white disabled:opacity-50 bg-gray-800 hover:bg-gray-700"
          >
            {loading ? '로그인 중...' : '로그인'}
          </button>
        </form>
      </div>
    </div>
  )
}
