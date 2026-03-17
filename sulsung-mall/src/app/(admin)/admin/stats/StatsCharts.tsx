'use client'

import { useState } from 'react'
import { formatPrice } from '@/utils/format'
import { ORDER_STATUS_LABEL } from '@/utils/format'

interface Props {
  dailySales: { date: string; amount: number; count: number }[]
  dailyMembers: { date: string; count: number }[]
  statusCounts: Record<string, number>
  categorySales: { name: string; amount: number }[]
}

const STATUS_COLORS: Record<string, string> = {
  pending_payment: '#f59e0b',
  paid: '#3b82f6',
  preparing: '#7c3aed',
  shipped: '#ec4899',
  delivered: '#059669',
  confirmed: '#10b981',
  cancel_requested: '#f97316',
  cancelled: '#ef4444',
  return_requested: '#f97316',
  returning: '#f97316',
  returned: '#6b7280',
}

const CAT_COLORS = ['#968774', '#e84a3b', '#3b82f6', '#10b981', '#f59e0b', '#7c3aed', '#ec4899', '#6b7280']

export default function StatsCharts({ dailySales, dailyMembers, statusCounts, categorySales }: Props) {
  const [salesView, setSalesView] = useState<'amount' | 'count'>('amount')

  const maxSalesAmount = Math.max(...dailySales.map(d => d.amount), 1)
  const maxSalesCount = Math.max(...dailySales.map(d => d.count), 1)
  const maxMemberCount = Math.max(...dailyMembers.map(d => d.count), 1)
  const totalStatusOrders = Object.values(statusCounts).reduce((s, c) => s + c, 0) || 1
  const maxCatSales = Math.max(...categorySales.map(c => c.amount), 1)

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* 일별 매출 */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-gray-900 text-sm">일별 매출 (30일)</h3>
          <div className="flex gap-1">
            <button onClick={() => setSalesView('amount')}
              className={`text-xs px-2.5 py-1 rounded ${salesView === 'amount' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-400'}`}>
              금액
            </button>
            <button onClick={() => setSalesView('count')}
              className={`text-xs px-2.5 py-1 rounded ${salesView === 'count' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-400'}`}>
              건수
            </button>
          </div>
        </div>
        <div className="flex items-end gap-[2px] h-40">
          {dailySales.map((d, i) => {
            const value = salesView === 'amount' ? d.amount : d.count
            const max = salesView === 'amount' ? maxSalesAmount : maxSalesCount
            const height = max > 0 ? (value / max) * 100 : 0
            return (
              <div key={i} className="flex-1 group relative">
                <div className="w-full rounded-t" style={{ height: `${Math.max(height, 2)}%`, backgroundColor: '#968774', opacity: 0.7 }} />
                {/* 툴팁 */}
                <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10">
                  <div className="bg-gray-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap">
                    {d.date}: {salesView === 'amount' ? formatPrice(d.amount) : `${d.count}건`}
                  </div>
                </div>
              </div>
            )
          })}
        </div>
        <div className="flex justify-between mt-2 text-[10px] text-gray-400">
          <span>{dailySales[0]?.date}</span>
          <span>{dailySales[dailySales.length - 1]?.date}</span>
        </div>
      </div>

      {/* 일별 신규 회원 */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="font-semibold text-gray-900 text-sm mb-4">신규 회원 추이 (30일)</h3>
        <div className="flex items-end gap-[2px] h-40">
          {dailyMembers.map((d, i) => {
            const height = maxMemberCount > 0 ? (d.count / maxMemberCount) * 100 : 0
            return (
              <div key={i} className="flex-1 group relative">
                <div className="w-full rounded-t" style={{ height: `${Math.max(height, 2)}%`, backgroundColor: '#7c3aed', opacity: 0.6 }} />
                <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10">
                  <div className="bg-gray-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap">
                    {d.date}: {d.count}명
                  </div>
                </div>
              </div>
            )
          })}
        </div>
        <div className="flex justify-between mt-2 text-[10px] text-gray-400">
          <span>{dailyMembers[0]?.date}</span>
          <span>{dailyMembers[dailyMembers.length - 1]?.date}</span>
        </div>
      </div>

      {/* 주문 상태 분포 */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="font-semibold text-gray-900 text-sm mb-4">이번달 주문 상태 분포</h3>
        {Object.keys(statusCounts).length === 0 ? (
          <p className="text-sm text-gray-400 text-center py-8">데이터가 없습니다</p>
        ) : (
          <div className="space-y-3">
            {Object.entries(statusCounts)
              .sort((a, b) => b[1] - a[1])
              .map(([status, count]) => (
                <div key={status}>
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xs text-gray-600">{ORDER_STATUS_LABEL[status] ?? status}</span>
                    <span className="text-xs font-medium text-gray-900">{count}건 ({Math.round((count / totalStatusOrders) * 100)}%)</span>
                  </div>
                  <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div className="h-full rounded-full" style={{
                      width: `${(count / totalStatusOrders) * 100}%`,
                      backgroundColor: STATUS_COLORS[status] ?? '#6b7280',
                    }} />
                  </div>
                </div>
              ))}
          </div>
        )}
      </div>

      {/* 카테고리별 매출 */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="font-semibold text-gray-900 text-sm mb-4">카테고리별 매출</h3>
        {categorySales.length === 0 ? (
          <p className="text-sm text-gray-400 text-center py-8">데이터가 없습니다</p>
        ) : (
          <div className="space-y-3">
            {categorySales.map((cat, i) => (
              <div key={cat.name}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs text-gray-600">{cat.name}</span>
                  <span className="text-xs font-medium text-gray-900">{formatPrice(cat.amount)}</span>
                </div>
                <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div className="h-full rounded-full" style={{
                    width: `${(cat.amount / maxCatSales) * 100}%`,
                    backgroundColor: CAT_COLORS[i % CAT_COLORS.length],
                  }} />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
