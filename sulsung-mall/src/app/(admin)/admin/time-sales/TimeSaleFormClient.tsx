'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

export default function TimeSaleFormClient({ initialData }: { initialData?: any }) {
  const router = useRouter()
  const isEdit = !!initialData?.id
  const [form, setForm] = useState({
    name: initialData?.name ?? '',
    goods_id: initialData?.goods_id ?? '',
    discount_type: initialData?.discount_type ?? 'rate',
    discount_value: initialData?.discount_value ?? '',
    starts_at: initialData?.starts_at?.slice(0, 16) ?? '',
    ends_at: initialData?.ends_at?.slice(0, 16) ?? '',
    max_qty: initialData?.max_qty ?? '',
    is_active: initialData?.is_active ?? true,
  })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const set = (k: string, v: any) => setForm(f => ({ ...f, [k]: v }))

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')

    const payload: any = {
      name: form.name,
      goods_id: form.goods_id,
      discount_type: form.discount_type,
      discount_value: Number(form.discount_value),
      starts_at: new Date(form.starts_at).toISOString(),
      ends_at: new Date(form.ends_at).toISOString(),
      max_qty: form.max_qty ? Number(form.max_qty) : null,
      is_active: form.is_active,
    }

    if (isEdit) payload.id = initialData.id

    const res = await fetch('/api/v1/admin/time-sales', {
      method: isEdit ? 'PATCH' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const json = await res.json()
    if (!res.ok) { setError(json.error ?? '저장 실패'); setLoading(false); return }
    router.push('/admin/time-sales')
    router.refresh()
  }

  const handleDelete = async () => {
    if (!confirm('삭제하시겠습니까?')) return
    await fetch('/api/v1/admin/time-sales', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: initialData.id }),
    })
    router.push('/admin/time-sales')
    router.refresh()
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-6">
      {error && <div className="bg-red-50 text-red-700 text-sm px-4 py-3 rounded-xl">{error}</div>}

      <section className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">세일명 *</label>
          <input value={form.name} onChange={e => set('name', e.target.value)} required
            placeholder="예: 설날 한우 특가"
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">상품 ID (UUID) *</label>
          <input value={form.goods_id} onChange={e => set('goods_id', e.target.value)} required
            placeholder="상품 UUID"
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-green-500" />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">할인 유형</label>
            <select value={form.discount_type} onChange={e => set('discount_type', e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500">
              <option value="rate">정률 (%)</option>
              <option value="amount">정액 (원)</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">할인값 *</label>
            <input type="number" value={form.discount_value} onChange={e => set('discount_value', e.target.value)} required min={1}
              placeholder={form.discount_type === 'rate' ? '10' : '5000'}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">시작 *</label>
            <input type="datetime-local" value={form.starts_at} onChange={e => set('starts_at', e.target.value)} required
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">종료 *</label>
            <input type="datetime-local" value={form.ends_at} onChange={e => set('ends_at', e.target.value)} required
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">한정 수량 (선택)</label>
            <input type="number" value={form.max_qty} onChange={e => set('max_qty', e.target.value)} min={1}
              placeholder="비워두면 무제한"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
          <div className="flex items-end pb-2">
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)}
                className="w-4 h-4 accent-green-600" />
              <span className="text-sm font-medium text-gray-700">활성화</span>
            </label>
          </div>
        </div>
      </section>

      <div className="flex items-center justify-between">
        {isEdit && (
          <button type="button" onClick={handleDelete} className="text-sm text-red-500 hover:text-red-700 underline">
            삭제
          </button>
        )}
        <div className="flex gap-3 ml-auto">
          <button type="button" onClick={() => router.back()}
            className="px-5 py-2.5 text-sm border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50">취소</button>
          <button type="submit" disabled={loading}
            className="px-6 py-2.5 bg-green-700 text-white text-sm font-semibold rounded-xl hover:bg-green-800 disabled:opacity-50">
            {loading ? '저장 중...' : isEdit ? '수정' : '등록'}
          </button>
        </div>
      </div>
    </form>
  )
}
