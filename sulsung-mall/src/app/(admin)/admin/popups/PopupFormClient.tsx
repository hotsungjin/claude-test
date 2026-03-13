'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

export default function PopupFormClient({ initialData }: { initialData?: any }) {
  const router = useRouter()
  const isEdit = !!initialData?.id
  const [form, setForm] = useState({
    name: initialData?.name ?? '',
    image_url: initialData?.image_url ?? '',
    mobile_image_url: initialData?.mobile_image_url ?? '',
    link_url: initialData?.link_url ?? '',
    position: initialData?.position ?? 'center',
    starts_at: initialData?.starts_at?.slice(0, 16) ?? '',
    ends_at: initialData?.ends_at?.slice(0, 16) ?? '',
    hide_duration: initialData?.hide_duration ?? 24,
    sort_order: initialData?.sort_order ?? 0,
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
      ...form,
      hide_duration: Number(form.hide_duration),
      sort_order: Number(form.sort_order),
      starts_at: form.starts_at ? new Date(form.starts_at).toISOString() : null,
      ends_at: form.ends_at ? new Date(form.ends_at).toISOString() : null,
    }
    if (isEdit) payload.id = initialData.id

    const res = await fetch('/api/v1/admin/popups', {
      method: isEdit ? 'PATCH' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const json = await res.json()
    if (!res.ok) { setError(json.error ?? '저장 실패'); setLoading(false); return }
    router.push('/admin/popups')
    router.refresh()
  }

  const handleDelete = async () => {
    if (!confirm('삭제하시겠습니까?')) return
    await fetch('/api/v1/admin/popups', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: initialData.id }),
    })
    router.push('/admin/popups')
    router.refresh()
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-6">
      {error && <div className="bg-red-50 text-red-700 text-sm px-4 py-3 rounded-xl">{error}</div>}

      <section className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">팝업명 *</label>
          <input value={form.name} onChange={e => set('name', e.target.value)} required
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">이미지 URL *</label>
          <input value={form.image_url} onChange={e => set('image_url', e.target.value)} required
            placeholder="https://..."
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">모바일 이미지 URL</label>
          <input value={form.mobile_image_url} onChange={e => set('mobile_image_url', e.target.value)}
            placeholder="비워두면 PC 이미지 사용"
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">링크 URL</label>
          <input value={form.link_url} onChange={e => set('link_url', e.target.value)}
            placeholder="클릭 시 이동할 URL"
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">위치</label>
            <select value={form.position} onChange={e => set('position', e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500">
              <option value="center">중앙</option>
              <option value="bottom">하단</option>
              <option value="fullscreen">전체화면</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">다시 안보기 (시간)</label>
            <input type="number" value={form.hide_duration} onChange={e => set('hide_duration', e.target.value)} min={1}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">시작</label>
            <input type="datetime-local" value={form.starts_at} onChange={e => set('starts_at', e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">종료</label>
            <input type="datetime-local" value={form.ends_at} onChange={e => set('ends_at', e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
          </div>
        </div>
        <div className="flex items-center gap-6">
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)}
              className="w-4 h-4 accent-green-600" />
            <span className="text-sm font-medium text-gray-700">활성화</span>
          </label>
          <div className="flex items-center gap-2">
            <label className="text-sm text-gray-700">정렬</label>
            <input type="number" value={form.sort_order} onChange={e => set('sort_order', e.target.value)}
              className="w-16 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-500" />
          </div>
        </div>
      </section>

      <div className="flex items-center justify-between">
        {isEdit && (
          <button type="button" onClick={handleDelete} className="text-sm text-red-500 hover:text-red-700 underline">삭제</button>
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
