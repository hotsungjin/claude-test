'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

export default function GoodsBulkActionBar({ selectedIds }: { selectedIds: string[] }) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)

  if (selectedIds.length === 0) return null

  const bulkAction = async (action: string, value?: string) => {
    if (!confirm(`선택한 ${selectedIds.length}개 상품에 대해 작업을 실행하시겠습니까?`)) return
    setLoading(true)
    await fetch('/api/v1/admin/bulk', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: 'goods', action, ids: selectedIds, value }),
    })
    setLoading(false)
    router.refresh()
  }

  return (
    <div className="fixed bottom-0 left-64 right-0 z-30 bg-gray-900 text-white px-6 py-3 flex items-center justify-between shadow-2xl">
      <span className="text-sm font-medium">{selectedIds.length}개 선택됨</span>
      <div className="flex gap-2">
        <button onClick={() => bulkAction('status_change', 'active')} disabled={loading}
          className="px-3 py-1.5 bg-blue-600 rounded-lg text-xs font-medium hover:bg-blue-500 disabled:opacity-50">
          판매중
        </button>
        <button onClick={() => bulkAction('status_change', 'inactive')} disabled={loading}
          className="px-3 py-1.5 bg-gray-600 rounded-lg text-xs font-medium hover:bg-gray-500 disabled:opacity-50">
          비공개
        </button>
        <button onClick={() => bulkAction('status_change', 'soldout')} disabled={loading}
          className="px-3 py-1.5 bg-orange-600 rounded-lg text-xs font-medium hover:bg-orange-500 disabled:opacity-50">
          품절
        </button>
        <button onClick={() => bulkAction('delete')} disabled={loading}
          className="px-3 py-1.5 bg-red-600 rounded-lg text-xs font-medium hover:bg-red-500 disabled:opacity-50">
          삭제
        </button>
      </div>
    </div>
  )
}
