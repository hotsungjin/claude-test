'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { ORDER_STATUS_LABEL } from '@/utils/format'

const NEXT_STATUS: Record<string, { label: string; next: string; color: string }[]> = {
  pending_payment:   [{ label: '결제확인', next: 'paid', color: 'blue' }, { label: '주문취소', next: 'cancelled', color: 'red' }],
  paid:              [{ label: '배송준비', next: 'preparing', color: 'indigo' }, { label: '주문취소', next: 'cancelled', color: 'red' }],
  preparing:         [{ label: '배송시작', next: 'shipped', color: 'purple' }],
  shipped:           [{ label: '배송완료', next: 'delivered', color: 'green' }],
  delivered:         [{ label: '구매확정', next: 'confirmed', color: 'green' }],
  cancel_requested:  [{ label: '취소처리', next: 'cancelled', color: 'red' }, { label: '취소거부', next: 'paid', color: 'gray' }],
  return_requested:  [{ label: '반품수거중', next: 'returning', color: 'orange' }, { label: '반품거부', next: 'delivered', color: 'gray' }],
  returning:         [{ label: '반품완료', next: 'returned', color: 'gray' }],
}

const BTN_COLOR: Record<string, string> = {
  blue:   'bg-blue-600 hover:bg-blue-700',
  indigo: 'bg-indigo-600 hover:bg-indigo-700',
  purple: 'bg-purple-600 hover:bg-purple-700',
  green:  'bg-green-600 hover:bg-green-700',
  red:    'bg-red-600 hover:bg-red-700',
  orange: 'bg-orange-500 hover:bg-orange-600',
  gray:   'bg-gray-500 hover:bg-gray-600',
}

interface DeliveryCompany { id: number; name: string }
interface DeliveryInfo { company_id: number; courier_name: string; tracking_no: string }

export default function OrderStatusClient({ orderId, currentStatus, adminMemo }: {
  orderId: string; currentStatus: string; adminMemo: string | null
}) {
  const router = useRouter()
  const [memo, setMemo] = useState(adminMemo ?? '')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')

  // 송장 입력 관련
  const [companies, setCompanies] = useState<DeliveryCompany[]>([])
  const [delivery, setDelivery] = useState<DeliveryInfo>({ company_id: 0, courier_name: '', tracking_no: '' })
  const showDeliveryForm = ['preparing', 'shipped'].includes(currentStatus)

  useEffect(() => {
    if (showDeliveryForm) {
      fetch(`/api/v1/admin/orders/${orderId}/delivery`)
        .then(r => r.json())
        .then(data => {
          setCompanies(data.companies ?? [])
          if (data.delivery) {
            setDelivery({
              company_id: data.delivery.company_id ?? 0,
              courier_name: data.delivery.courier_name ?? '',
              tracking_no: data.delivery.tracking_no ?? '',
            })
          } else if (data.companies?.length) {
            setDelivery(d => ({ ...d, company_id: data.companies[0].id, courier_name: data.companies[0].name }))
          }
        })
    }
  }, [showDeliveryForm, orderId])

  const actions = NEXT_STATUS[currentStatus] ?? []

  const changeStatus = async (next: string) => {
    // 배송시작 시 송장번호 필수
    if (next === 'shipped' && !delivery.tracking_no.trim()) {
      setMessage('송장번호를 먼저 입력해주세요.')
      return
    }

    setLoading(true)
    setMessage('')

    // 배송시작이면 송장 등록 먼저
    if (next === 'shipped') {
      const deliveryRes = await fetch(`/api/v1/admin/orders/${orderId}/delivery`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(delivery),
      })
      if (!deliveryRes.ok) {
        const err = await deliveryRes.json()
        setMessage(err.error ?? '송장 등록 실패')
        setLoading(false)
        return
      }
      // 송장 등록 API가 자동으로 shipped 전환하므로 별도 상태 변경 불필요
      setMessage('송장이 등록되고 배송중으로 변경되었습니다.')
      setLoading(false)
      router.refresh()
      return
    }

    const res = await fetch(`/api/v1/admin/orders/${orderId}/status`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: next, memo }),
    })
    const json = await res.json()
    if (res.ok) {
      setMessage(`${ORDER_STATUS_LABEL[next] ?? next}(으)로 변경되었습니다.`)
      router.refresh()
    } else {
      setMessage(json.error ?? '오류가 발생했습니다.')
    }
    setLoading(false)
  }

  const saveMemo = async () => {
    setLoading(true)
    await fetch(`/api/v1/admin/orders/${orderId}/status`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: currentStatus, memo }),
    })
    setMessage('메모가 저장되었습니다.')
    setLoading(false)
    router.refresh()
  }

  const saveDelivery = async () => {
    if (!delivery.tracking_no.trim()) {
      setMessage('송장번호를 입력해주세요.')
      return
    }
    setLoading(true)
    const res = await fetch(`/api/v1/admin/orders/${orderId}/delivery`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(delivery),
    })
    if (res.ok) {
      setMessage('송장정보가 저장되었습니다.')
      router.refresh()
    } else {
      const err = await res.json()
      setMessage(err.error ?? '송장 저장 실패')
    }
    setLoading(false)
  }

  const handleCompanyChange = (companyId: number) => {
    const company = companies.find(c => c.id === companyId)
    setDelivery(d => ({ ...d, company_id: companyId, courier_name: company?.name ?? '' }))
  }

  return (
    <div className="space-y-4">
      {/* 송장 입력 */}
      {showDeliveryForm && (
        <div>
          <p className="text-xs font-medium text-gray-500 mb-2">송장 정보</p>
          <div className="space-y-2">
            <select
              value={delivery.company_id}
              onChange={e => handleCompanyChange(Number(e.target.value))}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500"
            >
              <option value={0} disabled>택배사 선택</option>
              {companies.map(c => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <input
              type="text"
              value={delivery.tracking_no}
              onChange={e => setDelivery(d => ({ ...d, tracking_no: e.target.value }))}
              placeholder="송장번호 입력"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 font-mono"
            />
            {currentStatus === 'shipped' && (
              <button onClick={saveDelivery} disabled={loading}
                className="w-full px-4 py-2 bg-gray-800 text-white text-xs rounded-lg hover:bg-gray-700 disabled:opacity-50">
                송장정보 수정
              </button>
            )}
          </div>
        </div>
      )}

      {/* 상태 변경 버튼 */}
      {actions.length > 0 && (
        <div>
          <p className="text-xs font-medium text-gray-500 mb-2">상태 변경</p>
          <div className="flex flex-wrap gap-2">
            {actions.map(a => (
              <button key={a.next} onClick={() => changeStatus(a.next)} disabled={loading}
                className={`px-4 py-2 text-sm font-medium text-white rounded-lg ${BTN_COLOR[a.color]} disabled:opacity-50`}>
                {a.label}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* 관리자 메모 */}
      <div>
        <p className="text-xs font-medium text-gray-500 mb-2">관리자 메모</p>
        <textarea value={memo} onChange={e => setMemo(e.target.value)} rows={3}
          placeholder="내부 메모 (고객에게 보이지 않음)"
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 resize-none" />
        <button onClick={saveMemo} disabled={loading}
          className="mt-2 px-4 py-1.5 bg-gray-800 text-white text-xs rounded-lg hover:bg-gray-700 disabled:opacity-50">
          메모 저장
        </button>
      </div>

      {message && (
        <p className={`text-sm px-3 py-2 rounded-lg ${message.includes('실패') || message.includes('입력') ? 'text-red-700 bg-red-50' : 'text-green-700 bg-green-50'}`}>
          {message}
        </p>
      )}
    </div>
  )
}
