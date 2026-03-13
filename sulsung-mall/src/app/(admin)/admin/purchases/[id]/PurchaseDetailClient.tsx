'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ArrowLeft, Truck, CheckCircle, XCircle } from 'lucide-react'

const STATUS_LABEL: Record<string, string> = {
  draft: '작성중', ordered: '발주완료', receiving: '입고중', completed: '입고완료', cancelled: '취소',
}
const STATUS_COLOR: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600',
  ordered: 'bg-blue-100 text-blue-700',
  receiving: 'bg-yellow-100 text-yellow-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-600',
}

interface POItem {
  id: number; goods_id: string; qty: number; unit_price: number; received_qty: number
  goods: { id: string; name: string; thumbnail_url: string | null; stock: number }
}

interface PurchaseOrder {
  id: number; status: string; memo: string | null; total_amount: number
  ordered_at: string | null; expected_at: string | null; completed_at: string | null; created_at: string
  suppliers: { name: string; lead_days: number } | null
  purchase_order_items: POItem[]
}

export default function PurchaseDetailClient({ order }: { order: PurchaseOrder }) {
  const router = useRouter()
  const [receiveQtys, setReceiveQtys] = useState<Record<number, number>>(
    Object.fromEntries(order.purchase_order_items.map(i => [i.id, i.qty - i.received_qty]))
  )
  const [loading, setLoading] = useState(false)

  async function updateStatus(status: string) {
    if (!confirm(`상태를 "${STATUS_LABEL[status]}"(으)로 변경하시겠습니까?`)) return
    setLoading(true)
    await fetch(`/api/v1/admin/purchases/${order.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status }),
    })
    router.refresh()
    setLoading(false)
  }

  async function handleReceive() {
    const items = order.purchase_order_items
      .filter(i => (receiveQtys[i.id] ?? 0) > 0)
      .map(i => ({ id: i.id, received_qty: i.received_qty + (receiveQtys[i.id] ?? 0) }))

    if (items.length === 0) return alert('입고 수량을 입력해주세요')
    if (!confirm('입고 처리하시겠습니까? 재고가 자동으로 증가합니다.')) return

    setLoading(true)
    await fetch(`/api/v1/admin/purchases/${order.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'receive', items }),
    })
    router.refresh()
    setLoading(false)
  }

  async function handleCancel() {
    if (!confirm('발주서를 취소하시겠습니까?')) return
    setLoading(true)
    await fetch(`/api/v1/admin/purchases/${order.id}`, { method: 'DELETE' })
    router.push('/admin/purchases')
  }

  const isEditable = order.status === 'draft' || order.status === 'ordered'
  const canReceive = order.status === 'ordered' || order.status === 'receiving'

  return (
    <div className="space-y-6">
      {/* 헤더 */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Link href="/admin/purchases" className="text-gray-400 hover:text-gray-600">
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <h1 className="text-2xl font-bold text-gray-900">PO-{String(order.id).padStart(4, '0')}</h1>
          <span className={`text-xs px-3 py-1 rounded-full font-medium ${STATUS_COLOR[order.status]}`}>
            {STATUS_LABEL[order.status]}
          </span>
        </div>
        <div className="flex gap-2">
          {order.status === 'draft' && (
            <button onClick={() => updateStatus('ordered')} disabled={loading}
              className="flex items-center gap-1.5 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
              <Truck className="w-4 h-4" /> 발주 확정
            </button>
          )}
          {canReceive && (
            <button onClick={handleReceive} disabled={loading}
              className="flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50">
              <CheckCircle className="w-4 h-4" /> 입고 처리
            </button>
          )}
          {isEditable && (
            <button onClick={handleCancel} disabled={loading}
              className="flex items-center gap-1.5 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-50 disabled:opacity-50">
              <XCircle className="w-4 h-4" /> 취소
            </button>
          )}
        </div>
      </div>

      {/* 기본 정보 */}
      <div className="bg-white rounded-xl p-6 shadow-sm">
        <h2 className="text-sm font-bold text-gray-800 mb-4">기본 정보</h2>
        <div className="grid grid-cols-4 gap-4 text-sm">
          <div>
            <p className="text-xs text-gray-400">공급처</p>
            <p className="font-medium text-gray-900">{order.suppliers?.name ?? '미지정'}</p>
          </div>
          <div>
            <p className="text-xs text-gray-400">작성일</p>
            <p className="font-medium text-gray-900">{new Date(order.created_at).toLocaleDateString('ko-KR')}</p>
          </div>
          <div>
            <p className="text-xs text-gray-400">발주일</p>
            <p className="font-medium text-gray-900">{order.ordered_at ? new Date(order.ordered_at).toLocaleDateString('ko-KR') : '-'}</p>
          </div>
          <div>
            <p className="text-xs text-gray-400">총 금액</p>
            <p className="font-bold text-gray-900">{order.total_amount.toLocaleString()}원</p>
          </div>
        </div>
        {order.memo && <p className="mt-3 text-sm text-gray-500 bg-gray-50 rounded-lg px-3 py-2">{order.memo}</p>}
      </div>

      {/* 발주 상품 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-100">
          <h2 className="text-sm font-bold text-gray-800">발주 상품 ({order.purchase_order_items.length}개)</h2>
        </div>
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100 bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-10">이미지</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">상품명</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">현재재고</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">발주수량</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">단가</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">소계</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">입고완료</th>
              {canReceive && <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">이번 입고</th>}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {order.purchase_order_items.map((item) => {
              const remaining = item.qty - item.received_qty
              return (
                <tr key={item.id} className={remaining <= 0 ? 'bg-green-50/50' : ''}>
                  <td className="px-4 py-3">
                    {item.goods.thumbnail_url
                      ? <img src={item.goods.thumbnail_url} alt="" className="w-9 h-9 rounded object-cover" />
                      : <div className="w-9 h-9 bg-gray-100 rounded" />}
                  </td>
                  <td className="px-4 py-3 font-medium text-gray-900">{item.goods.name}</td>
                  <td className="px-4 py-3 text-right text-gray-500">{item.goods.stock}</td>
                  <td className="px-4 py-3 text-right text-gray-700 font-medium">{item.qty}</td>
                  <td className="px-4 py-3 text-right text-gray-500">{item.unit_price.toLocaleString()}원</td>
                  <td className="px-4 py-3 text-right text-gray-700 font-medium">{(item.qty * item.unit_price).toLocaleString()}원</td>
                  <td className="px-4 py-3 text-right">
                    <span className={remaining <= 0 ? 'text-green-600 font-medium' : 'text-gray-500'}>
                      {item.received_qty}/{item.qty}
                    </span>
                  </td>
                  {canReceive && (
                    <td className="px-4 py-3 text-right">
                      {remaining > 0 ? (
                        <input type="number" min={0} max={remaining}
                          value={receiveQtys[item.id] ?? 0}
                          onChange={e => setReceiveQtys(prev => ({ ...prev, [item.id]: Math.min(remaining, Math.max(0, Number(e.target.value))) }))}
                          className="w-20 text-right border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-green-500" />
                      ) : (
                        <span className="text-green-600 text-xs">완료</span>
                      )}
                    </td>
                  )}
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </div>
  )
}
