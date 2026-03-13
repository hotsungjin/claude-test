'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

interface Props {
  memberId: string
  currentGrade: string
  currentMileage: number
  isActive: boolean
}

const GRADES = [
  { value: 'bronze', label: '브론즈' },
  { value: 'silver', label: '실버' },
  { value: 'gold', label: '골드' },
  { value: 'vip', label: 'VIP' },
]

export default function MemberActionClient({ memberId, currentGrade, currentMileage, isActive }: Props) {
  const router = useRouter()
  const [grade, setGrade] = useState(currentGrade)
  const [mileageDelta, setMileageDelta] = useState('')
  const [mileageReason, setMileageReason] = useState('')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')

  const saveGrade = async () => {
    setLoading(true)
    const res = await fetch(`/api/v1/admin/members/${memberId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ grade }),
    })
    setMessage(res.ok ? '등급이 변경되었습니다.' : '오류가 발생했습니다.')
    setLoading(false)
    router.refresh()
  }

  const addMileage = async () => {
    if (!mileageDelta || !mileageReason) {
      setMessage('금액과 사유를 입력하세요.')
      return
    }
    setLoading(true)
    const res = await fetch(`/api/v1/admin/members/${memberId}/mileage`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ delta: Number(mileageDelta), reason: mileageReason }),
    })
    const json = await res.json()
    setMessage(res.ok ? `마일리지가 ${Number(mileageDelta) > 0 ? '지급' : '차감'}되었습니다.` : json.error ?? '오류')
    setMileageDelta('')
    setMileageReason('')
    setLoading(false)
    router.refresh()
  }

  const toggleActive = async () => {
    setLoading(true)
    await fetch(`/api/v1/admin/members/${memberId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ is_active: !isActive }),
    })
    setMessage(isActive ? '계정이 비활성화되었습니다.' : '계정이 활성화되었습니다.')
    setLoading(false)
    router.refresh()
  }

  return (
    <div className="space-y-6">
      {message && <p className="text-sm text-green-700 bg-green-50 px-3 py-2 rounded-lg">{message}</p>}

      {/* 등급 변경 */}
      <div>
        <p className="text-xs font-medium text-gray-500 mb-2">회원 등급 변경</p>
        <div className="flex gap-2">
          <select value={grade} onChange={e => setGrade(e.target.value)}
            className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500">
            {GRADES.map(g => <option key={g.value} value={g.value}>{g.label}</option>)}
          </select>
          <button onClick={saveGrade} disabled={loading || grade === currentGrade}
            className="px-4 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800 disabled:opacity-50">
            변경
          </button>
        </div>
      </div>

      {/* 마일리지 지급/차감 */}
      <div>
        <p className="text-xs font-medium text-gray-500 mb-2">마일리지 지급/차감 (현재: {currentMileage.toLocaleString()}P)</p>
        <input type="number" value={mileageDelta} onChange={e => setMileageDelta(e.target.value)}
          placeholder="양수=지급, 음수=차감"
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 mb-2" />
        <input value={mileageReason} onChange={e => setMileageReason(e.target.value)}
          placeholder="사유 (예: 이벤트 지급)"
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 mb-2" />
        <button onClick={addMileage} disabled={loading}
          className="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50">
          마일리지 적용
        </button>
      </div>

      {/* 계정 활성화/비활성화 */}
      <div className="border-t border-gray-100 pt-4">
        <button onClick={toggleActive} disabled={loading}
          className={`w-full px-4 py-2 text-sm rounded-lg border font-medium disabled:opacity-40 ${
            isActive
              ? 'border-red-300 text-red-600 hover:bg-red-50'
              : 'border-green-300 text-green-700 hover:bg-green-50'
          }`}>
          {isActive ? '계정 비활성화' : '계정 활성화'}
        </button>
      </div>
    </div>
  )
}
