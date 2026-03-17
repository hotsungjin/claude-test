'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Plus, Trash2, Search } from 'lucide-react'

interface Supplier { id: number; name: string; lead_days: number }
interface Goods { id: string; name: string; stock: number; thumbnail_url: string | null; cost_price: number | null }
interface POItem { goods_id: string; name: string; qty: number; unit_price: number; stock: number }

export default function PurchaseFormClient({ suppliers, goods }: { suppliers: Supplier[]; goods: Goods[] }) {
  const router = useRouter()
  const [supplierId, setSupplierId] = useState('')
  const [memo, setMemo] = useState('')
  const [items, setItems] = useState<POItem[]>([])
  const [search, setSearch] = useState('')
  const [showSearch, setShowSearch] = useState(false)
  const [saving, setSaving] = useState(false)

  const filtered = search.trim()
    ? goods.filter(g => g.name.toLowerCase().includes(search.toLowerCase())).slice(0, 20)
    : []

  function addItem(g: Goods) {
    if (items.some(i => i.goods_id === g.id)) return
    setItems(prev => [...prev, { goods_id: g.id, name: g.name, qty: 1, unit_price: g.cost_price ?? 0, stock: g.stock }])
    setSearch('')
    setShowSearch(false)
  }

  function updateItem(idx: number, field: 'qty' | 'unit_price', value: number) {
    setItems(prev => prev.map((item, i) => i === idx ? { ...item, [field]: value } : item))
  }

  function removeItem(idx: number) {
    setItems(prev => prev.filter((_, i) => i !== idx))
  }

  const totalAmount = items.reduce((s, i) => s + i.qty * i.unit_price, 0)

  async function handleSubmit() {
    if (items.length === 0) return alert('상품을 추가해주세요')
    setSaving(true)
    try {
      const res = await fetch('/api/v1/admin/purchases', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          supplier_id: supplierId ? Number(supplierId) : null,
          memo,
          items: items.map(i => ({ goods_id: i.goods_id, qty: i.qty, unit_price: i.unit_price })),
        }),
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.error)
      router.push('/admin/purchases')
    } catch (e: any) {
      alert(e.message || '저장 실패')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="space-y-6">
      {/* 기본 정보 */}
      <div className="bg-white rounded-xl p-6 shadow-sm">
        <h2 className="text-sm font-bold text-gray-800 mb-4">기본 정보</h2>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-xs text-gray-500 mb-1">공급처</label>
            <select value={supplierId} onChange={e => setSupplierId(e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500">
              <option value="">공급처 선택 (선택사항)</option>
              {suppliers.map(s => <option key={s.id} value={s.id}>{s.name} (리드타임 {s.lead_days}일)</option>)}
            </select>
          </div>
          <div>
            <label className="block text-xs text-gray-500 mb-1">메모</label>
            <input value={memo} onChange={e => setMemo(e.target.value)} placeholder="발주 메모"
              className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" />
          </div>
        </div>
      </div>

      {/* 상품 추가 */}
      <div className="bg-white rounded-xl p-6 shadow-sm">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-sm font-bold text-gray-800">발주 상품</h2>
          <button onClick={() => setShowSearch(!showSearch)}
            className="flex items-center gap-1.5 bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-medium hover:bg-blue-700">
            <Plus className="w-3.5 h-3.5" /> 상품 추가
          </button>
        </div>

        {/* 검색 */}
        {showSearch && (
          <div className="mb-4 relative">
            <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
              <Search className="w-4 h-4 text-gray-400 ml-3" />
              <input value={search} onChange={e => setSearch(e.target.value)} placeholder="상품명 검색..."
                autoFocus
                className="flex-1 px-3 py-2.5 text-sm focus:outline-none" />
            </div>
            {filtered.length > 0 && (
              <div className="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-10">
                {filtered.map(g => (
                  <button key={g.id} onClick={() => addItem(g)}
                    disabled={items.some(i => i.goods_id === g.id)}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    {g.thumbnail_url
                      ? <img src={g.thumbnail_url} alt="" className="w-8 h-8 rounded object-cover" />
                      : <div className="w-8 h-8 bg-gray-100 rounded" />}
                    <div className="flex-1 min-w-0">
                      <p className="text-sm text-gray-900 truncate">{g.name}</p>
                      <p className="text-xs text-gray-400">재고 {g.stock} · 원가 {(g.cost_price ?? 0).toLocaleString()}원</p>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>
        )}

        {/* 상품 리스트 */}
        {items.length === 0 ? (
          <div className="py-8 text-center text-gray-400 text-sm">상품을 추가해주세요</div>
        ) : (
          <table className="w-full text-sm">
            <thead className="border-b border-gray-100">
              <tr>
                <th className="py-2 text-left text-xs text-gray-500 font-medium">상품명</th>
                <th className="py-2 text-right text-xs text-gray-500 font-medium w-20">현재재고</th>
                <th className="py-2 text-right text-xs text-gray-500 font-medium w-28">발주수량</th>
                <th className="py-2 text-right text-xs text-gray-500 font-medium w-32">단가 (원)</th>
                <th className="py-2 text-right text-xs text-gray-500 font-medium w-28">소계</th>
                <th className="py-2 w-10"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {items.map((item, idx) => (
                <tr key={item.goods_id}>
                  <td className="py-3 text-gray-900 font-medium">{item.name}</td>
                  <td className="py-3 text-right text-gray-500">{item.stock}</td>
                  <td className="py-3 text-right">
                    <input type="number" min={1} value={item.qty}
                      onChange={e => updateItem(idx, 'qty', Math.max(1, Number(e.target.value)))}
                      className="w-20 text-right border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-500" />
                  </td>
                  <td className="py-3 text-right">
                    <input type="number" min={0} value={item.unit_price}
                      onChange={e => updateItem(idx, 'unit_price', Math.max(0, Number(e.target.value)))}
                      className="w-28 text-right border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-500" />
                  </td>
                  <td className="py-3 text-right font-medium text-gray-700">{(item.qty * item.unit_price).toLocaleString()}원</td>
                  <td className="py-3 text-center">
                    <button onClick={() => removeItem(idx)} className="text-gray-400 hover:text-red-500">
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {items.length > 0 && (
          <div className="flex justify-end mt-4 pt-4 border-t border-gray-100">
            <div className="text-right">
              <p className="text-xs text-gray-500">총 {items.length}개 상품</p>
              <p className="text-lg font-bold text-gray-900">{totalAmount.toLocaleString()}원</p>
            </div>
          </div>
        )}
      </div>

      {/* 저장 */}
      <div className="flex justify-end gap-3">
        <button onClick={() => router.back()}
          className="px-6 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
          취소
        </button>
        <button onClick={handleSubmit} disabled={saving || items.length === 0}
          className="px-6 py-2.5 rounded-xl bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800 disabled:opacity-50">
          {saving ? '저장 중...' : '발주서 저장'}
        </button>
      </div>
    </div>
  )
}
