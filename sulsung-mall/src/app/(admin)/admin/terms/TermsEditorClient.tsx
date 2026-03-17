'use client'

import { useState } from 'react'
import { FileText, Save, Eye } from 'lucide-react'

interface Term {
  id: number
  slug: string
  title: string
  content: string
  updated_at: string
  updated_by: string | null
}

export default function TermsEditorClient({ terms }: { terms: Term[] }) {
  const [list, setList] = useState(terms)
  const [activeSlug, setActiveSlug] = useState(terms[0]?.slug ?? 'terms')
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')

  const active = list.find(t => t.slug === activeSlug)

  function handleContentChange(content: string) {
    setList(prev => prev.map(t => t.slug === activeSlug ? { ...t, content } : t))
  }

  async function handleSave() {
    if (!active) return
    setSaving(true)
    setMessage('')

    const res = await fetch('/api/v1/admin/terms', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slug: active.slug, content: active.content }),
    })

    if (res.ok) {
      const data = await res.json()
      setList(prev => prev.map(t => t.slug === activeSlug ? { ...t, updated_at: data.updated_at } : t))
      setMessage('저장되었습니다.')
    } else {
      const data = await res.json()
      setMessage(data.error || '저장 실패')
    }
    setSaving(false)
    setTimeout(() => setMessage(''), 3000)
  }

  if (!active) return null

  return (
    <div className="max-w-4xl space-y-4">
      {/* 탭 */}
      <div className="flex gap-2">
        {list.map(t => (
          <button
            key={t.slug}
            onClick={() => setActiveSlug(t.slug)}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              t.slug === activeSlug
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'
            }`}
          >
            <FileText className="w-3.5 h-3.5 inline mr-1.5" />
            {t.title}
          </button>
        ))}
      </div>

      {/* 에디터 */}
      <div className="bg-white rounded-xl shadow-sm">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 className="font-semibold text-gray-800">{active.title}</h2>
            {active.updated_at && (
              <p className="text-xs text-gray-400 mt-0.5">
                최종 수정: {new Date(active.updated_at).toLocaleString('ko-KR')}
                {active.updated_by && ` (${active.updated_by})`}
              </p>
            )}
          </div>
          <div className="flex items-center gap-2">
            <a
              href={`/policy/${active.slug}`}
              target="_blank"
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
            >
              <Eye className="w-3.5 h-3.5" />
              미리보기
            </a>
            <button
              onClick={handleSave}
              disabled={saving}
              className="inline-flex items-center gap-1.5 bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              <Save className="w-3.5 h-3.5" />
              {saving ? '저장 중...' : '저장'}
            </button>
          </div>
        </div>

        <div className="p-6">
          <textarea
            value={active.content}
            onChange={e => handleContentChange(e.target.value)}
            rows={25}
            className="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-800 focus:outline-none focus:border-blue-500 resize-y font-mono leading-relaxed"
            placeholder="약관 내용을 입력해주세요..."
          />
          <p className="text-xs text-gray-400 mt-2">
            HTML 태그를 사용할 수 있습니다. (예: &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;br&gt;)
          </p>
        </div>
      </div>

      {message && (
        <div className={`text-sm px-4 py-2 rounded-lg ${message.includes('실패') ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'}`}>
          {message}
        </div>
      )}
    </div>
  )
}
