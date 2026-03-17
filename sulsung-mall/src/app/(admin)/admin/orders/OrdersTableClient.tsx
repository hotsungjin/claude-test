'use client'

import { useState } from 'react'
import Link from 'next/link'
import dayjs from 'dayjs'
import { formatPrice, ORDER_STATUS_LABEL } from '@/utils/format'
import OrdersBulkActionBar from './OrdersBulkActionBar'

const STATUS_COLOR: Record<string, string> = {
  pending_payment: 'bg-yellow-100 text-yellow-700',
  paid: 'bg-blue-100 text-blue-700',
  preparing: 'bg-sky-100 text-sky-700',
  shipped: 'bg-indigo-100 text-indigo-700',
  delivered: 'bg-blue-100 text-blue-700',
  confirmed: 'bg-emerald-100 text-emerald-700',
  cancel_requested: 'bg-orange-100 text-orange-700',
  cancelled: 'bg-red-100 text-red-600',
  return_requested: 'bg-amber-100 text-amber-700',
  returning: 'bg-amber-100 text-amber-700',
  returned: 'bg-gray-100 text-gray-600',
  exchange_requested: 'bg-purple-100 text-purple-700',
  exchanged: 'bg-purple-100 text-purple-600',
}

export default function OrdersTableClient({ orders }: { orders: any[] }) {
  const [selected, setSelected] = useState<Set<string>>(new Set())

  const toggleAll = () => {
    if (selected.size === orders.length) {
      setSelected(new Set())
    } else {
      setSelected(new Set(orders.map((o: any) => o.id)))
    }
  }

  const toggle = (id: string) => {
    const next = new Set(selected)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    setSelected(next)
  }

  return (
    <>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 w-10">
                <input type="checkbox" checked={selected.size === orders.length && orders.length > 0}
                  onChange={toggleAll} className="w-4 h-4 accent-blue-600" />
              </th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">주문번호</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">주문자</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">수령인</th>
              <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">결제금액</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">주문일</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {orders.map((order: any) => (
              <tr key={order.id} className={`hover:bg-gray-50 ${selected.has(order.id) ? 'bg-blue-50' : ''}`}>
                <td className="px-4 py-3">
                  <input type="checkbox" checked={selected.has(order.id)}
                    onChange={() => toggle(order.id)} className="w-4 h-4 accent-blue-600" />
                </td>
                <td className="px-4 py-3 font-mono text-xs">
                  <Link href={`/admin/orders/${order.id}`} className="text-blue-600 hover:underline">{order.order_no}</Link>
                </td>
                <td className="px-4 py-3 text-gray-700">
                  {order.members?.name ?? '-'}
                  <span className="block text-xs text-gray-400">{order.members?.email}</span>
                </td>
                <td className="px-4 py-3 text-gray-700">
                  {order.recipient}
                  <span className="block text-xs text-gray-400">{order.phone}</span>
                </td>
                <td className="px-4 py-3 text-right font-medium text-gray-900">
                  {formatPrice(order.total_amount)}
                </td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs px-2 py-1 rounded-full font-medium ${STATUS_COLOR[order.status] ?? 'bg-gray-100 text-gray-500'}`}>
                    {ORDER_STATUS_LABEL[order.status] ?? order.status}
                  </span>
                </td>
                <td className="px-4 py-3 text-center text-xs text-gray-500">
                  {dayjs(order.created_at).format('MM.DD HH:mm')}
                </td>
                <td className="px-4 py-3 text-center">
                  <Link href={`/admin/orders/${order.id}`} className="text-xs text-blue-600 hover:underline">상세</Link>
                </td>
              </tr>
            ))}
            {orders.length === 0 && (
              <tr><td colSpan={8} className="px-4 py-12 text-center text-gray-400">주문이 없습니다</td></tr>
            )}
          </tbody>
        </table>
      </div>
      <OrdersBulkActionBar selectedIds={Array.from(selected)} />
    </>
  )
}
