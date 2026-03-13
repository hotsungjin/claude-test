'use client'

import { useState } from 'react'
import { ChevronDown, ChevronUp } from 'lucide-react'

interface FaqItem { id: number; category: string; question: string; answer: string }

export default function FaqClient({ categories }: { categories: Record<string, FaqItem[]> }) {
  const catList = Object.keys(categories)
  const [activeTab, setActiveTab] = useState(catList[0] ?? '')
  const [openId, setOpenId] = useState<number | null>(null)

  const items = categories[activeTab] ?? []

  return (
    <div>
      {/* 카테고리 탭 */}
      {catList.length > 1 && (
        <div className="flex flex-wrap gap-2 mb-6">
          {catList.map(cat => (
            <button key={cat} onClick={() => { setActiveTab(cat); setOpenId(null) }}
              className={`px-4 py-2 rounded-full text-sm font-medium border transition-colors ${
                activeTab === cat
                  ? 'text-white'
                  : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
              }`}
              style={activeTab === cat ? { backgroundColor: '#968774', borderColor: '#968774' } : undefined}>
              {cat}
            </button>
          ))}
        </div>
      )}

      {/* FAQ 아코디언 */}
      <div className="space-y-2">
        {items.map(faq => (
          <div key={faq.id} className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <button onClick={() => setOpenId(openId === faq.id ? null : faq.id)}
              className="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50">
              <span className="font-medium text-gray-900 flex items-start gap-2">
                <span className="font-bold mt-0.5 flex-shrink-0" style={{ color: '#968774' }}>Q</span>
                {faq.question}
              </span>
              {openId === faq.id
                ? <ChevronUp className="w-4 h-4 text-gray-400 flex-shrink-0 ml-4" />
                : <ChevronDown className="w-4 h-4 text-gray-400 flex-shrink-0 ml-4" />}
            </button>
            {openId === faq.id && (
              <div className="px-5 pb-5 border-t border-gray-50">
                <div className="flex gap-2 mt-4">
                  <span className="text-blue-600 font-bold flex-shrink-0">A</span>
                  <p className="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{faq.answer}</p>
                </div>
              </div>
            )}
          </div>
        ))}
        {items.length === 0 && (
          <p className="text-center text-gray-400 text-sm py-12">FAQ가 없습니다.</p>
        )}
      </div>
    </div>
  )
}
