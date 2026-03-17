'use client'

import { useState } from 'react'
import { Users } from 'lucide-react'

interface Props {
  couponId: string
  grades: string[]
}

export default function CouponIssueClient({ couponId, grades }: Props) {
  const [target, setTarget] = useState<'all' | 'grade'>('all')
  const [grade, setGrade] = useState('')
  const [loading, setLoading] = useState(false)
  const [result, setResult] = useState<{ issued: number; total: number } | null>(null)
  const [error, setError] = useState('')

  async function handleIssue() {
    if (target === 'grade' && !grade) { setError('등급을 선택하세요'); return }
    if (!confirm(target === 'all' ? '전체 회원에게 발급하시겠습니까?' : `${grade} 등급 회원에게 발급하시겠습니까?`)) return

    setLoading(true)
    setError('')
    setResult(null)

    const res = await fetch(`/api/v1/admin/coupons/${couponId}/issue`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ target, grade: target === 'grade' ? grade : undefined }),
    })
    const data = await res.json()
    setLoading(false)

    if (data.error) { setError(data.error); return }
    setResult(data)
  }

  return (
    <div className="bg-white rounded-xl shadow-sm p-6">
      <div className="flex items-center gap-2 mb-4">
        <Users className="w-4 h-4 text-blue-700" />
        <h3 className="font-semibold text-gray-800">회원 일괄 발급</h3>
      </div>

      <div className="space-y-3">
        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">발급 대상</label>
          <select value={target} onChange={e => setTarget(e.target.value as any)}
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <option value="all">전체 회원</option>
            <option value="grade">등급별 발급</option>
          </select>
        </div>

        {target === 'grade' && (
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">등급 선택</label>
            <select value={grade} onChange={e => setGrade(e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
              <option value="">등급 선택</option>
              {grades.map(g => <option key={g} value={g}>{g}</option>)}
            </select>
          </div>
        )}

        {error && <p className="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg">{error}</p>}

        {result && (
          <p className="text-xs text-blue-700 bg-blue-50 px-3 py-2 rounded-lg">
            완료: {result.total}명 중 {result.issued}명에게 신규 발급됨
            {result.total - result.issued > 0 && ` (${result.total - result.issued}명은 이미 보유)`}
          </p>
        )}

        <button onClick={handleIssue} disabled={loading}
          className="w-full bg-blue-700 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-600 disabled:opacity-50">
          {loading ? '발급 중...' : '발급하기'}
        </button>
      </div>
    </div>
  )
}
