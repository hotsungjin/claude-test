'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import MypageHeader from '@/components/store/mypage/MypageHeader'

const CATEGORIES = ['주문/결제', '배송', '상품', '취소/반품/교환', '회원/포인트', '기타']

export default function InquiryNewPage() {
  const router = useRouter()
  const [form, setForm] = useState({ category: CATEGORIES[0], title: '', content: '', is_secret: false })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.title.trim() || !form.content.trim()) {
      setError('제목과 내용을 입력해주세요.')
      return
    }
    setLoading(true)
    const res = await fetch('/api/v1/inquiries', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })
    if (res.ok) {
      router.push('/mypage/inquiries')
    } else {
      const json = await res.json()
      setError(json.error ?? '오류가 발생했습니다.')
    }
    setLoading(false)
  }

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="문의하기" />

      {error && <div className="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-4">{error}</div>}

      <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">문의 유형</label>
          <select value={form.category} onChange={e => setForm(f => ({ ...f, category: e.target.value }))}
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
            style={{ ['--tw-ring-color' as any]: '#968774' }}
            onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
            onBlur={e => (e.currentTarget.style.borderColor = '')}>
            {CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">제목</label>
          <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))}
            placeholder="문의 제목을 입력하세요" required
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
            onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
            onBlur={e => (e.currentTarget.style.borderColor = '')} />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">내용</label>
          <textarea value={form.content} onChange={e => setForm(f => ({ ...f, content: e.target.value }))}
            placeholder="문의 내용을 자세히 입력해주세요" required rows={8}
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none resize-y"
            onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
            onBlur={e => (e.currentTarget.style.borderColor = '')} />
        </div>
        <label className="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" checked={form.is_secret} onChange={e => setForm(f => ({ ...f, is_secret: e.target.checked }))}
            className="w-4 h-4" style={{ accentColor: '#968774' }} />
          <span className="text-sm text-gray-700">비밀글로 등록</span>
        </label>
        <div className="flex gap-3 pt-2">
          <button type="button" onClick={() => router.back()}
            className="flex-1 py-3 border border-gray-300 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50">
            취소
          </button>
          <button type="submit" disabled={loading}
            className="flex-1 py-3 text-white text-sm font-semibold rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
            style={{ backgroundColor: '#968774' }}>
            {loading ? '등록 중...' : '문의 등록'}
          </button>
        </div>
      </form>
    </div>
  )
}
