'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

interface Faq {
  id: number
  category: string
  question: string
  answer: string
  sort_order: number
  is_active: boolean
}

const CATEGORIES = ['일반', '주문/결제', '배송', '취소/반품', '회원', '상품']

export default function FaqFormClient({ faq }: { faq?: Faq }) {
  const router = useRouter()
  const [category, setCategory] = useState(faq?.category ?? '일반')
  const [question, setQuestion] = useState(faq?.question ?? '')
  const [answer, setAnswer] = useState(faq?.answer ?? '')
  const [sortOrder, setSortOrder] = useState(faq?.sort_order ?? 0)
  const [isActive, setIsActive] = useState(faq?.is_active ?? true)
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')

  const handleSubmit = async () => {
    if (!question.trim() || !answer.trim()) {
      setMessage('질문과 답변을 모두 입력하세요.')
      return
    }
    setLoading(true)
    const payload = { category, question, answer, sort_order: sortOrder, is_active: isActive }

    const res = faq
      ? await fetch(`/api/v1/admin/faq/${faq.id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        })
      : await fetch('/api/v1/admin/faq', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        })

    if (res.ok) {
      router.push('/admin/faq')
      router.refresh()
    } else {
      const json = await res.json().catch(() => ({}))
      setMessage(json.error ?? '저장에 실패했습니다.')
    }
    setLoading(false)
  }

  const handleDelete = async () => {
    if (!faq || !confirm('삭제하시겠습니까?')) return
    await fetch(`/api/v1/admin/faq/${faq.id}`, { method: 'DELETE' })
    router.push('/admin/faq')
    router.refresh()
  }

  return (
    <div className="bg-white rounded-xl shadow-sm p-6 space-y-5">
      {message && <p className="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{message}</p>}

      <div>
        <label className="block text-xs font-medium text-gray-500 mb-1.5">카테고리</label>
        <select value={category} onChange={e => setCategory(e.target.value)}
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500">
          {CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
        </select>
      </div>

      <div>
        <label className="block text-xs font-medium text-gray-500 mb-1.5">질문</label>
        <input value={question} onChange={e => setQuestion(e.target.value)}
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500"
          placeholder="질문을 입력하세요" />
      </div>

      <div>
        <label className="block text-xs font-medium text-gray-500 mb-1.5">답변</label>
        <textarea value={answer} onChange={e => setAnswer(e.target.value)} rows={6}
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 resize-y"
          placeholder="답변을 입력하세요" />
      </div>

      <div className="flex gap-4">
        <div className="flex-1">
          <label className="block text-xs font-medium text-gray-500 mb-1.5">정렬 순서</label>
          <input type="number" value={sortOrder} onChange={e => setSortOrder(Number(e.target.value))}
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
        </div>
        <div className="flex items-end pb-1">
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" checked={isActive} onChange={e => setIsActive(e.target.checked)}
              className="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500" />
            <span className="text-sm text-gray-700">활성</span>
          </label>
        </div>
      </div>

      <div className="flex gap-3 pt-2">
        <button onClick={handleSubmit} disabled={loading}
          className="px-6 py-2.5 bg-green-700 text-white text-sm font-semibold rounded-xl hover:bg-green-800 disabled:opacity-50">
          {loading ? '저장 중...' : faq ? '수정' : '등록'}
        </button>
        {faq && (
          <button onClick={handleDelete}
            className="px-4 py-2.5 text-sm border border-red-300 text-red-600 rounded-xl hover:bg-red-50">
            삭제
          </button>
        )}
        <button onClick={() => router.push('/admin/faq')}
          className="px-4 py-2.5 text-sm border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50">
          취소
        </button>
      </div>
    </div>
  )
}
