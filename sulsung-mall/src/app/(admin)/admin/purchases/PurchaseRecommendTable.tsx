'use client'

import { useState } from 'react'

interface RecommendItem {
  id: string
  name: string
  thumbnail_url: string | null
  stock: number
  avgDaily: number
  totalSold90: number
  reorderPoint: number
  optimalStock: number
  recommendQty: number
  daysUntilOut: number
  needsOrder: boolean
  isLow: boolean
  categories?: { name: string } | null
}

export default function PurchaseRecommendTable({ items }: { items: RecommendItem[] }) {
  const [filter, setFilter] = useState<'all' | 'urgent' | 'low'>('urgent')
  const [showCount, setShowCount] = useState(30)

  const filtered = items.filter(i => {
    if (filter === 'urgent') return i.needsOrder
    if (filter === 'low') return i.isLow
    return true
  })

  const displayed = filtered.slice(0, showCount)

  return (
    <div>
      {/* 탭 */}
      <div className="px-6 py-3 flex gap-2 border-b border-gray-50">
        {[
          { key: 'urgent', label: `발주 필요 (${items.filter(i => i.needsOrder).length})` },
          { key: 'low', label: `재고 부족 (${items.filter(i => i.isLow).length})` },
          { key: 'all', label: '전체' },
        ].map(tab => (
          <button key={tab.key} onClick={() => { setFilter(tab.key as any); setShowCount(30) }}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition ${filter === tab.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
            {tab.label}
          </button>
        ))}
      </div>

      {displayed.length === 0 ? (
        <div className="py-12 text-center text-gray-400 text-sm">
          {filter === 'urgent' ? '발주가 필요한 상품이 없습니다' : '해당 상품이 없습니다'}
        </div>
      ) : (
        <>
          <table className="w-full text-sm">
            <thead className="border-b border-gray-100">
              <tr>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-10">이미지</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">상품명</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">카테고리</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">현재재고</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">일평균판매</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">90일판매</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">예상소진</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">발주점</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">적정재고</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">추천발주량</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {displayed.map((item) => (
                <tr key={item.id} className={`hover:bg-gray-50 ${item.needsOrder ? 'bg-red-50/50' : ''}`}>
                  <td className="px-4 py-3">
                    {item.thumbnail_url
                      ? <img src={item.thumbnail_url} alt="" className="w-9 h-9 rounded object-cover" />
                      : <div className="w-9 h-9 bg-gray-100 rounded" />}
                  </td>
                  <td className="px-4 py-3 font-medium text-gray-900 max-w-[200px] truncate">{item.name}</td>
                  <td className="px-4 py-3 text-gray-500 text-xs">{item.categories?.name ?? '-'}</td>
                  <td className={`px-4 py-3 text-right font-medium ${item.isLow ? 'text-red-600' : 'text-gray-700'}`}>
                    {item.stock.toLocaleString()}
                  </td>
                  <td className="px-4 py-3 text-right text-gray-700">{item.avgDaily}</td>
                  <td className="px-4 py-3 text-right text-gray-500">{item.totalSold90.toLocaleString()}</td>
                  <td className="px-4 py-3 text-right">
                    {item.daysUntilOut >= 999 ? (
                      <span className="text-gray-400">-</span>
                    ) : (
                      <span className={item.daysUntilOut <= 7 ? 'text-red-600 font-bold' : item.daysUntilOut <= 14 ? 'text-orange-500 font-medium' : 'text-gray-700'}>
                        {item.daysUntilOut}일
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right text-gray-500">{item.reorderPoint}</td>
                  <td className="px-4 py-3 text-right text-gray-500">{item.optimalStock}</td>
                  <td className="px-4 py-3 text-right">
                    {item.recommendQty > 0 ? (
                      <span className="inline-flex items-center gap-1 text-red-600 font-bold">
                        {item.recommendQty.toLocaleString()}
                      </span>
                    ) : (
                      <span className="text-blue-600">충분</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {filtered.length > showCount && (
            <div className="py-4 text-center border-t border-gray-50">
              <button onClick={() => setShowCount(s => s + 30)}
                className="text-sm text-blue-600 hover:underline">
                더보기 ({filtered.length - showCount}개 남음)
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
