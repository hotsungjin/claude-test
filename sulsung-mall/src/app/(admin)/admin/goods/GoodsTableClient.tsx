'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Download } from 'lucide-react'
import { formatPrice } from '@/utils/format'
import { downloadExcel } from '@/utils/exportExcel'
import GoodsBulkActionBar from './BulkActionBar'

const STATUS_LABEL: Record<string, string> = { active: '판매중', inactive: '비공개', soldout: '품절', deleted: '삭제' }
const STATUS_COLOR: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-gray-100 text-gray-500',
  soldout: 'bg-red-100 text-red-600',
  deleted: 'bg-gray-200 text-gray-400',
}

export default function GoodsTableClient({ goods, totalCount, page, pageSize }: { goods: any[]; totalCount: number; page: number; pageSize: number }) {
  const [selected, setSelected] = useState<Set<string>>(new Set())

  const toggleAll = () => {
    if (selected.size === goods.length) {
      setSelected(new Set())
    } else {
      setSelected(new Set(goods.map((g: any) => g.id)))
    }
  }

  const toggle = (id: string) => {
    const next = new Set(selected)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    setSelected(next)
  }

  function handleDownload() {
    const target = selected.size > 0
      ? goods.filter(g => selected.has(g.id))
      : goods

    const headers = ['번호', '상품코드', '상품명', '카테고리', '판매가', '할인가', '재고', '판매수', '상태']
    const rows = target.map((item: any, i: number) => [
      totalCount - (page - 1) * pageSize - i,
      item.godomall_code ?? '',
      item.name,
      item.categories?.name ?? '',
      item.price,
      item.sale_price ?? '',
      item.stock,
      item.sale_count ?? 0,
      STATUS_LABEL[item.status] ?? item.status,
    ])

    downloadExcel(headers, rows, `상품목록_${new Date().toISOString().slice(0, 10)}`)
  }

  return (
    <>
      <div className="px-6 py-3 flex items-center justify-between">
        <span className="text-sm text-gray-500">
          {selected.size > 0 && <span className="text-green-600">{selected.size}개 선택</span>}
        </span>
        <button
          onClick={handleDownload}
          className="inline-flex items-center gap-1.5 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 transition-colors"
        >
          <Download className="w-3.5 h-3.5" />
          엑셀 다운로드{selected.size > 0 ? ` (${selected.size}개)` : ''}
        </button>
      </div>
      <table className="w-full text-sm min-w-[900px]">
        <thead className="border-b border-gray-100">
          <tr>
            <th className="px-4 py-3 w-10">
              <input type="checkbox" checked={selected.size === goods.length && goods.length > 0}
                onChange={toggleAll} className="w-4 h-4 accent-green-600" />
            </th>
            <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-16">번호</th>
            <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-20">상품코드</th>
            <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-16">이미지</th>
            <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">상품명</th>
            <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">카테고리</th>
            <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">판매가</th>
            <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">재고</th>
            <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">판매수</th>
            <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
            <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-50">
          {goods.map((item: any, index: number) => (
            <tr key={item.id} className={`hover:bg-gray-50 ${selected.has(item.id) ? 'bg-green-50' : ''}`}>
              <td className="px-4 py-3">
                <input type="checkbox" checked={selected.has(item.id)}
                  onChange={() => toggle(item.id)} className="w-4 h-4 accent-green-600" />
              </td>
              <td className="px-4 py-3 text-center text-xs text-gray-400">{totalCount - (page - 1) * pageSize - index}</td>
              <td className="px-4 py-3 text-center text-xs text-gray-500 font-mono">{item.godomall_code ?? '-'}</td>
              <td className="px-4 py-3">
                {item.thumbnail_url
                  ? <img src={item.thumbnail_url} alt="" className="w-12 h-12 rounded-lg object-cover aspect-square flex-shrink-0" />
                  : <div className="w-12 h-12 bg-gray-100 rounded-lg flex-shrink-0" />}
              </td>
              <td className="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">{item.name}</td>
              <td className="px-4 py-3 text-gray-500">{item.categories?.name ?? '-'}</td>
              <td className="px-4 py-3 text-right">
                {item.sale_price ? (
                  <span className="text-red-600 font-medium">{formatPrice(item.sale_price)}</span>
                ) : formatPrice(item.price)}
              </td>
              <td className={`px-4 py-3 text-right font-medium ${item.stock <= 5 ? 'text-red-500' : 'text-gray-700'}`}>
                {item.stock.toLocaleString()}
              </td>
              <td className="px-4 py-3 text-right text-gray-500">{(item.sale_count ?? 0).toLocaleString()}</td>
              <td className="px-4 py-3 text-center">
                <span className={`text-xs px-2 py-1 rounded-full font-medium ${STATUS_COLOR[item.status]}`}>
                  {STATUS_LABEL[item.status]}
                </span>
              </td>
              <td className="px-4 py-3 text-center">
                <Link href={`/admin/goods/${item.id}`} className="text-xs text-blue-600 hover:underline">수정</Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <GoodsBulkActionBar selectedIds={Array.from(selected)} />
    </>
  )
}
