'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

const BULK_TRANSITIONS: { label: string; value: string; color: string }[] = [
  { label: '배송준비중', value: 'preparing', color: 'bg-blue-600 hover:bg-blue-500' },
  { label: '배송중', value: 'shipped', color: 'bg-indigo-600 hover:bg-indigo-500' },
  { label: '배송완료', value: 'delivered', color: 'bg-green-600 hover:bg-green-500' },
  { label: '취소완료', value: 'cancelled', color: 'bg-red-600 hover:bg-red-500' },
  { label: '반품완료', value: 'returned', color: 'bg-orange-600 hover:bg-orange-500' },
]

export default function OrdersBulkActionBar({ selectedIds }: { selectedIds: string[] }) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)

  if (selectedIds.length === 0) return null

  const bulkAction = async (toStatus: string) => {
    if (!confirm(`선택한 ${selectedIds.length}건의 주문을 "${BULK_TRANSITIONS.find(t => t.value === toStatus)?.label}"(으)로 변경하시겠습니까?`)) return
    setLoading(true)
    await fetch('/api/v1/admin/bulk', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: 'orders', action: 'status_change', ids: selectedIds, value: toStatus }),
    })
    setLoading(false)
    router.refresh()
  }

  return (
    <div className="fixed bottom-0 left-64 right-0 z-30 bg-gray-900 text-white px-6 py-3 flex items-center justify-between shadow-2xl">
      <span className="text-sm font-medium">{selectedIds.length}건 선택됨</span>
      <div className="flex gap-2">
        {BULK_TRANSITIONS.map(t => (
          <button key={t.value} onClick={() => bulkAction(t.value)} disabled={loading}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium disabled:opacity-50 ${t.color}`}>
            {t.label}
          </button>
        ))}
      </div>
    </div>
  )
}
