'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { formatDateTime } from '@/utils/format'

export default function InquiryAnswerClient({ inquiryId, existingAnswer, existingStatus }: {
  inquiryId: string; existingAnswer: string | null; existingStatus: string
}) {
  const router = useRouter()
  const [answer, setAnswer] = useState(existingAnswer ?? '')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')

  const submit = async () => {
    if (!answer.trim()) { setMessage('답변 내용을 입력하세요.'); return }
    setLoading(true)
    const res = await fetch(`/api/v1/admin/inquiries/${inquiryId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ answer, status: 'answered' }),
    })
    setMessage(res.ok ? '답변이 등록되었습니다.' : '오류가 발생했습니다.')
    setLoading(false)
    router.refresh()
  }

  return (
    <div className="space-y-4">
      {existingAnswer && existingStatus === 'answered' && (
        <div className="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-wrap">
          {existingAnswer}
        </div>
      )}
      <textarea value={answer} onChange={e => setAnswer(e.target.value)} rows={8}
        placeholder="답변 내용을 입력하세요..."
        className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 resize-y" />
      {message && <p className="text-sm text-blue-700">{message}</p>}
      <div className="flex gap-3">
        <button onClick={submit} disabled={loading}
          className="px-6 py-2.5 bg-blue-700 text-white text-sm font-semibold rounded-xl hover:bg-blue-800 disabled:opacity-50">
          {loading ? '저장 중...' : existingAnswer ? '답변 수정' : '답변 등록'}
        </button>
        {existingStatus !== 'closed' && (
          <button onClick={async () => {
            await fetch(`/api/v1/admin/inquiries/${inquiryId}`, {
              method: 'PATCH',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ status: 'closed' }),
            })
            router.refresh()
          }} className="px-4 py-2.5 text-sm border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50">
            종료 처리
          </button>
        )}
      </div>
    </div>
  )
}
