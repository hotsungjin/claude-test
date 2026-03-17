'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

export default function OrderBulkActionBar({ selectedIds }: { selectedIds: string[] }) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)

  if (selectedIds.length === 0) return null

  const bulkAction = async (value: string) => {
    if (!confirm(`선택한 ${selectedIds.length}건의 주문 상태를 변경하시겠습니까?`)) return
    setLoading(true)
    await fetch('/api/v1/admin/bulk', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: 'orders', action: 'status_change', ids: selectedIds, value }),
    })
    setLoading(false)
    router.refresh()
  }

  return (
    <div className="fixed bottom-0 left-64 right-0 z-30 bg-gray-900 text-white px-6 py-3 flex items-center justify-between shadow-2xl">
      <span className="text-sm font-medium">{selectedIds.length}건 선택됨</span>
      <div className="flex gap-2">
        <button onClick={() => bulkAction('preparing')} disabled={loading}
          className="px-3 py-1.5 bg-indigo-600 rounded-lg text-xs font-medium hover:bg-indigo-500 disabled:opacity-50">
          배송준비
        </button>
        <button onClick={() => bulkAction('shipped')} disabled={loading}
          className="px-3 py-1.5 bg-purple-600 rounded-lg text-xs font-medium hover:bg-purple-500 disabled:opacity-50">
          배송중
        </button>
        <button onClick={() => bulkAction('delivered')} disabled={loading}
          className="px-3 py-1.5 bg-blue-600 rounded-lg text-xs font-medium hover:bg-blue-500 disabled:opacity-50">
          배송완료
        </button>
        <button onClick={() => bulkAction('cancelled')} disabled={loading}
          className="px-3 py-1.5 bg-red-600 rounded-lg text-xs font-medium hover:bg-red-500 disabled:opacity-50">
          취소처리
        </button>
      </div>
    </div>
  )
}
