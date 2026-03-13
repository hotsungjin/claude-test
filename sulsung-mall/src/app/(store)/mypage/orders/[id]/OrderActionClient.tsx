'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

export default function OrderActionClient({ orderId, status }: { orderId: string; status: string }) {
  const router = useRouter()
  const [showModal, setShowModal] = useState<'cancel' | 'return' | null>(null)
  const [reason, setReason] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const canCancel = ['pending_payment', 'paid', 'preparing'].includes(status)
  const canReturn = status === 'delivered'

  if (!canCancel && !canReturn) return null

  const handleSubmit = async () => {
    if (!reason.trim()) {
      setError('사유를 입력해주세요.')
      return
    }
    setLoading(true)
    setError('')

    const endpoint = showModal === 'cancel'
      ? `/api/v1/orders/${orderId}/cancel`
      : `/api/v1/orders/${orderId}/return`

    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason }),
    })
    const json = await res.json()

    if (res.ok) {
      setShowModal(null)
      setReason('')
      router.refresh()
    } else {
      setError(json.error ?? '요청에 실패했습니다.')
    }
    setLoading(false)
  }

  return (
    <>
      <div className="px-4 mt-3 flex gap-2">
        {canCancel && (
          <button onClick={() => setShowModal('cancel')}
            className="flex-1 py-2.5 rounded-xl text-[13px] font-medium border"
            style={{ borderColor: '#e5e5e5', color: '#888' }}>
            주문 취소
          </button>
        )}
        {canReturn && (
          <button onClick={() => setShowModal('return')}
            className="flex-1 py-2.5 rounded-xl text-[13px] font-medium border"
            style={{ borderColor: '#e5e5e5', color: '#888' }}>
            반품 요청
          </button>
        )}
      </div>

      {/* 모달 */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40" onClick={() => setShowModal(null)}>
          <div className="w-full max-w-md bg-white rounded-t-2xl p-5 pb-8" onClick={e => e.stopPropagation()}>
            <h3 className="text-[16px] font-bold mb-4" style={{ color: '#333' }}>
              {showModal === 'cancel' ? '주문 취소' : '반품 요청'}
            </h3>

            <p className="text-[13px] mb-2" style={{ color: '#888' }}>
              {showModal === 'cancel' ? '취소 사유를 입력해주세요.' : '반품 사유를 입력해주세요.'}
            </p>

            <textarea
              value={reason}
              onChange={e => setReason(e.target.value)}
              rows={3}
              placeholder={showModal === 'cancel' ? '예: 단순 변심' : '예: 상품 불량, 오배송 등'}
              className="w-full border rounded-xl px-3 py-2.5 text-[13px] resize-none focus:outline-none"
              style={{ borderColor: '#e5e5e5' }}
            />

            {error && <p className="text-[12px] text-red-500 mt-2">{error}</p>}

            <div className="flex gap-2 mt-4">
              <button onClick={() => { setShowModal(null); setReason(''); setError('') }}
                className="flex-1 py-2.5 rounded-xl text-[13px] font-medium border"
                style={{ borderColor: '#e5e5e5', color: '#888' }}>
                닫기
              </button>
              <button onClick={handleSubmit} disabled={loading}
                className="flex-1 py-2.5 rounded-xl text-[13px] font-medium text-white disabled:opacity-50"
                style={{ backgroundColor: showModal === 'cancel' ? '#ef4444' : '#f59e0b' }}>
                {loading ? '처리중...' : showModal === 'cancel' ? '취소 요청' : '반품 요청'}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
