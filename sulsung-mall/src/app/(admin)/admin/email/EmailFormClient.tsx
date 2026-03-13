'use client'

import { useState } from 'react'
import { Send, Users, UserCheck, UserX } from 'lucide-react'

const TARGETS = [
  { value: 'all', label: '전체 회원', desc: '이메일이 등록된 모든 회원', icon: Users },
  { value: 'active', label: '활성 회원', desc: '최근 30일 내 주문한 회원', icon: UserCheck },
  { value: 'dormant', label: '휴면 회원', desc: '90일 이상 주문 없는 회원', icon: UserX },
]

export default function EmailFormClient() {
  const [target, setTarget] = useState('all')
  const [subject, setSubject] = useState('')
  const [content, setContent] = useState('')
  const [sending, setSending] = useState(false)
  const [result, setResult] = useState<{ sent: number; failed: number; total: number } | null>(null)

  const handleSend = async () => {
    if (!subject.trim() || !content.trim()) {
      alert('제목과 내용을 입력해주세요.')
      return
    }
    if (!confirm(`"${TARGETS.find(t => t.value === target)?.label}" 대상으로 이메일을 발송하시겠습니까?`)) return

    setSending(true)
    setResult(null)

    try {
      const res = await fetch('/api/v1/admin/email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subject, content, target }),
      })
      const data = await res.json()
      if (res.ok) {
        setResult({ sent: data.sent, failed: data.failed, total: data.total })
      } else {
        alert(data.error ?? '발송 실패')
      }
    } catch {
      alert('발송 중 오류가 발생했습니다.')
    } finally {
      setSending(false)
    }
  }

  return (
    <div className="space-y-6">
      {/* 대상 선택 */}
      <div className="bg-white rounded-xl p-6 shadow-sm">
        <h2 className="text-sm font-semibold text-gray-700 mb-4">발송 대상</h2>
        <div className="grid grid-cols-3 gap-3">
          {TARGETS.map(t => (
            <button key={t.value} onClick={() => setTarget(t.value)}
              className={`p-4 rounded-xl border-2 text-left transition-colors ${
                target === t.value
                  ? 'border-green-600 bg-green-50'
                  : 'border-gray-100 hover:border-gray-200'
              }`}>
              <t.icon className={`w-5 h-5 mb-2 ${target === t.value ? 'text-green-600' : 'text-gray-400'}`} />
              <p className={`text-sm font-semibold ${target === t.value ? 'text-green-700' : 'text-gray-700'}`}>{t.label}</p>
              <p className="text-xs text-gray-400 mt-0.5">{t.desc}</p>
            </button>
          ))}
        </div>
      </div>

      {/* 이메일 작성 */}
      <div className="bg-white rounded-xl p-6 shadow-sm">
        <h2 className="text-sm font-semibold text-gray-700 mb-4">이메일 내용</h2>

        <div className="space-y-4">
          <div>
            <label className="block text-xs text-gray-500 mb-1">제목</label>
            <input value={subject} onChange={e => setSubject(e.target.value)}
              placeholder="[설성목장몰] 이메일 제목을 입력하세요"
              className="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-green-500" />
          </div>

          <div>
            <label className="block text-xs text-gray-500 mb-1">내용 (HTML 지원)</label>
            <textarea value={content} onChange={e => setContent(e.target.value)}
              rows={12} placeholder="이메일 본문을 입력하세요. HTML 태그를 사용할 수 있습니다."
              className="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-green-500 resize-y font-mono" />
          </div>
        </div>
      </div>

      {/* 미리보기 */}
      {content && (
        <div className="bg-white rounded-xl p-6 shadow-sm">
          <h2 className="text-sm font-semibold text-gray-700 mb-4">미리보기</h2>
          <div className="border border-gray-100 rounded-xl overflow-hidden">
            <div className="bg-[#968774] px-6 py-4">
              <p className="text-white font-bold text-lg">설성목장몰</p>
            </div>
            <div className="px-6 py-8">
              <div className="text-sm text-gray-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: content }} />
            </div>
            <div className="bg-gray-50 px-6 py-3 text-center text-xs text-gray-400">
              설성목장몰 | 경기도 이천시 설성면
            </div>
          </div>
        </div>
      )}

      {/* 결과 */}
      {result && (
        <div className="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
          <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
            <Send className="w-5 h-5 text-green-600" />
          </div>
          <div>
            <p className="text-sm font-semibold text-green-800">발송 완료</p>
            <p className="text-xs text-green-600">
              전체 {result.total}명 중 {result.sent}명 성공{result.failed > 0 && `, ${result.failed}명 실패`}
            </p>
          </div>
        </div>
      )}

      {/* 발송 버튼 */}
      <div className="flex justify-end">
        <button onClick={handleSend} disabled={sending || !subject.trim() || !content.trim()}
          className="flex items-center gap-2 bg-green-700 text-white px-6 py-3 rounded-xl text-sm font-semibold hover:bg-green-800 disabled:opacity-50 disabled:cursor-not-allowed">
          <Send className="w-4 h-4" />
          {sending ? '발송 중...' : '이메일 발송'}
        </button>
      </div>
    </div>
  )
}
