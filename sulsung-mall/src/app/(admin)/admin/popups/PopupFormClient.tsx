'use client'

import { useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { Upload, X } from 'lucide-react'

const PAGE_OPTIONS = [
  { value: '/', label: '메인 (홈)' },
  { value: '/goods', label: '상품 목록' },
  { value: '/categories', label: '카테고리' },
  { value: '/auth/signup', label: '회원가입' },
  { value: '/mypage', label: '마이페이지' },
  { value: 'all', label: '전체 페이지' },
]

export default function PopupFormClient({ initialData }: { initialData?: any }) {
  const router = useRouter()
  const isEdit = !!initialData?.id
  const [form, setForm] = useState({
    name: initialData?.name ?? '',
    image_url: initialData?.image_url ?? '',
    link_url: initialData?.link_url ?? '',
    target_pages: initialData?.target_pages ?? ['/'],
    starts_at: initialData?.starts_at?.slice(0, 16) ?? '',
    ends_at: initialData?.ends_at?.slice(0, 16) ?? '',
    hide_duration: initialData?.hide_duration ?? 24,
    sort_order: initialData?.sort_order ?? 0,
    is_active: initialData?.is_active ?? true,
  })
  const [loading, setLoading] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')
  const fileInputRef = useRef<HTMLInputElement>(null)

  const set = (k: string, v: any) => setForm(f => ({ ...f, [k]: v }))

  async function uploadImage(file: File): Promise<string | null> {
    const fd = new FormData()
    fd.append('file', file)
    const res = await fetch('/api/v1/admin/upload', { method: 'POST', body: fd })
    const data = await res.json()
    if (data.error) { alert(data.error); return null }
    return data.url
  }

  async function handleUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setUploading(true)
    const url = await uploadImage(file)
    setUploading(false)
    if (url) set('image_url', url)
    e.target.value = ''
  }

  const togglePage = (val: string) => {
    if (val === 'all') {
      set('target_pages', form.target_pages.includes('all') ? ['/'] : ['all'])
      return
    }
    const pages = form.target_pages.filter((p: string) => p !== 'all')
    if (pages.includes(val)) {
      const next = pages.filter((p: string) => p !== val)
      set('target_pages', next.length > 0 ? next : ['/'])
    } else {
      set('target_pages', [...pages, val])
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.image_url) { alert('이미지를 등록하세요'); return }
    setLoading(true)
    setError('')

    const payload: any = {
      ...form,
      hide_duration: Number(form.hide_duration),
      sort_order: Number(form.sort_order),
      link_url: form.link_url || null,
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

  const inputClass = "w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"

  return (
    <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
      {error && <div className="bg-red-50 text-red-700 text-sm px-4 py-3 rounded-xl">{error}</div>}

      {/* 기본 정보 */}
      <section className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 className="font-semibold text-gray-800">기본 정보</h2>
        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">팝업명 *</label>
          <input value={form.name} onChange={e => set('name', e.target.value)} required className={inputClass} />
        </div>
        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">링크 URL</label>
          <input value={form.link_url} onChange={e => set('link_url', e.target.value)}
            placeholder="클릭 시 이동할 URL (예: /goods/hanwoo-set)" className={inputClass} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">시작일시</label>
            <input type="datetime-local" value={form.starts_at} onChange={e => set('starts_at', e.target.value)} className={inputClass} />
          </div>
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">종료일시</label>
            <input type="datetime-local" value={form.ends_at} onChange={e => set('ends_at', e.target.value)} className={inputClass} />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">다시 안보기 (시간)</label>
            <input type="number" value={form.hide_duration} onChange={e => set('hide_duration', e.target.value)} min={1} className={inputClass} />
          </div>
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">정렬순서</label>
            <input type="number" value={form.sort_order} onChange={e => set('sort_order', e.target.value)} className={inputClass} />
          </div>
        </div>
        <label className="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)}
            className="w-4 h-4 accent-blue-600" />
          <span className="text-sm">활성화</span>
        </label>
      </section>

      {/* 이미지 */}
      <section className="bg-white rounded-xl shadow-sm p-6 space-y-5">
        <h2 className="font-semibold text-gray-800">이미지</h2>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-2 block">팝업 이미지 * <span className="text-gray-400">(권장: 750×900px)</span></label>
          <div className="flex items-start gap-4">
            <button type="button" onClick={() => fileInputRef.current?.click()} disabled={uploading}
              className="flex-shrink-0 w-32 h-20 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center gap-1 hover:border-blue-400 hover:bg-blue-50 disabled:opacity-50">
              <Upload className="w-5 h-5 text-gray-400" />
              <span className="text-xs text-gray-400">업로드</span>
            </button>
            <input ref={fileInputRef} type="file" accept="image/*" className="hidden" onChange={handleUpload} />
            {form.image_url ? (
              <div className="relative flex-1">
                <img src={form.image_url} alt="팝업 이미지" className="w-full max-h-48 object-cover rounded-xl border border-gray-200" />
                <button type="button" onClick={() => set('image_url', '')}
                  className="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center">
                  <X className="w-3 h-3" />
                </button>
              </div>
            ) : (
              <input value={form.image_url} onChange={e => set('image_url', e.target.value)}
                placeholder="또는 URL 직접 입력" className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none" />
            )}
          </div>
        </div>
        {uploading && <p className="text-xs text-blue-600">업로드 중...</p>}
      </section>

      {/* 표시 페이지 */}
      <section className="bg-white rounded-xl shadow-sm p-6 space-y-3">
        <h2 className="font-semibold text-gray-800">표시 페이지</h2>
        <div className="flex flex-wrap gap-2">
          {PAGE_OPTIONS.map(opt => {
            const checked = form.target_pages.includes(opt.value)
            return (
              <button
                key={opt.value}
                type="button"
                onClick={() => togglePage(opt.value)}
                className={`px-3 py-1.5 rounded-full text-sm border transition-colors ${
                  checked
                    ? 'bg-blue-700 text-white border-blue-700'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-blue-500'
                }`}
              >
                {opt.label}
              </button>
            )
          })}
        </div>
        <p className="text-xs text-gray-400">팝업이 표시될 페이지를 선택하세요</p>
      </section>

      {/* 버튼 */}
      <div className="flex gap-3">
        <button type="submit" disabled={loading}
          className="flex-1 bg-blue-700 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-600 disabled:opacity-50">
          {loading ? '저장 중...' : isEdit ? '수정 완료' : '팝업 등록'}
        </button>
        <button type="button" onClick={() => router.back()}
          className="px-6 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
          취소
        </button>
        {isEdit && (
          <button type="button" onClick={handleDelete}
            className="px-6 py-2.5 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100">
            삭제
          </button>
        )}
      </div>
    </form>
  )
}
