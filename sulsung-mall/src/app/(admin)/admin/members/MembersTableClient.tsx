'use client'

import { useState } from 'react'
import Link from 'next/link'
import { UserCheck, Download } from 'lucide-react'
import { formatDateTime } from '@/utils/format'
import { downloadExcel } from '@/utils/exportExcel'

const GRADE_COLOR: Record<string, string> = {
  bronze: 'bg-gray-100 text-gray-600',
  silver: 'bg-blue-100 text-blue-700',
  gold:   'bg-yellow-100 text-yellow-700',
  vip:    'bg-purple-100 text-purple-700',
}

const GRADE_LABEL: Record<string, string> = {
  bronze: '브론즈',
  silver: '실버',
  gold:   '골드',
  vip:    'VIP',
}

interface Member {
  id: string
  member_no: number
  email: string | null
  name: string
  phone: string | null
  grade: string
  mileage: number
  is_active: boolean
  created_at: string
}

interface Props {
  members: Member[]
  count: number
  page: number
  pageSize: number
}

export default function MembersTableClient({ members, count, page, pageSize }: Props) {
  const [selected, setSelected] = useState<Set<string>>(new Set())

  const allChecked = members.length > 0 && members.every(m => selected.has(m.id))

  function toggleAll() {
    if (allChecked) {
      setSelected(new Set())
    } else {
      setSelected(new Set(members.map(m => m.id)))
    }
  }

  function toggleOne(id: string) {
    setSelected(prev => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  function handleDownload() {
    const target = selected.size > 0
      ? members.filter(m => selected.has(m.id))
      : members

    const headers = ['번호', '회원번호', '이름', '이메일', '연락처', '등급', '마일리지', '상태', '가입일']
    const rows = target.map((m, i) => [
      count - ((page - 1) * pageSize) - i,
      m.member_no,
      m.name,
      m.email ?? '',
      m.phone ?? '',
      GRADE_LABEL[m.grade] ?? m.grade,
      m.mileage,
      m.is_active ? '활성' : '비활성',
      formatDateTime(m.created_at),
    ])

    downloadExcel(headers, rows, `회원목록_${new Date().toISOString().slice(0, 10)}`)
  }

  return (
    <div className="bg-white rounded-xl shadow-sm overflow-hidden">
      <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
        <span className="text-sm text-gray-500">
          총 <strong>{count}</strong>명
          {selected.size > 0 && <span className="ml-2 text-green-600">({selected.size}명 선택)</span>}
        </span>
        <button
          onClick={handleDownload}
          className="inline-flex items-center gap-1.5 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 transition-colors"
        >
          <Download className="w-3.5 h-3.5" />
          엑셀 다운로드{selected.size > 0 ? ` (${selected.size}명)` : ''}
        </button>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm min-w-[1050px]">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-3 py-3 text-center w-10">
                <input
                  type="checkbox"
                  checked={allChecked}
                  onChange={toggleAll}
                  className="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                />
              </th>
              <th className="px-3 py-3 text-center text-xs text-gray-500 font-medium w-16">번호</th>
              <th className="px-3 py-3 text-center text-xs text-gray-500 font-medium w-24">회원번호</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이름</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이메일</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">연락처</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">등급</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">마일리지</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">가입일</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {members.map((m, i) => {
              const rowNum = count - ((page - 1) * pageSize) - i
              return (
                <tr key={m.id} className={`hover:bg-gray-50 ${selected.has(m.id) ? 'bg-green-50/50' : ''}`}>
                  <td className="px-3 py-3 text-center">
                    <input
                      type="checkbox"
                      checked={selected.has(m.id)}
                      onChange={() => toggleOne(m.id)}
                      className="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                    />
                  </td>
                  <td className="px-3 py-3 text-center text-xs text-gray-500">{rowNum}</td>
                  <td className="px-3 py-3 text-center text-xs text-gray-500">{m.member_no}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">{m.name}</td>
                  <td className="px-4 py-3 text-gray-600 text-xs">{m.email}</td>
                  <td className="px-4 py-3 text-gray-600 text-xs">{m.phone ?? '-'}</td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${GRADE_COLOR[m.grade] ?? 'bg-gray-100'}`}>
                      {GRADE_LABEL[m.grade] ?? m.grade}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right text-gray-700">{(m.mileage ?? 0).toLocaleString()}</td>
                  <td className="px-4 py-3 text-center">
                    {m.is_active
                      ? <span className="flex items-center justify-center gap-1 text-xs text-green-600"><UserCheck className="w-3 h-3" />활성</span>
                      : <span className="text-xs text-gray-400">비활성</span>}
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-500">{formatDateTime(m.created_at)}</td>
                  <td className="px-4 py-3 text-center">
                    <Link href={`/admin/members/${m.id}`} className="text-xs text-blue-600 hover:underline">상세</Link>
                  </td>
                </tr>
              )
            })}
            {members.length === 0 && (
              <tr>
                <td colSpan={11} className="px-4 py-12 text-center text-gray-400 text-sm">회원이 없습니다.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
