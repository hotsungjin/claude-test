'use client'

import { useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { Upload, X } from 'lucide-react'

const POSITION_LABEL: Record<string, string> = {
  main_top: '메인 상단',
  main_middle: '메인 광고1',
  main_bottom: '메인 광고2',
  main_ad: '메인 광고3',
}

interface Props { banner?: any }

export default function BannerFormClient({ banner }: Props) {
  const router = useRouter()
  const isEdit = !!banner

  const [form, setForm] = useState({
    name: banner?.name ?? '',
    position: banner?.position ?? 'main_top',
    image_url: banner?.image_url ?? '',
    link_url: banner?.link_url ?? '',
    alt: banner?.alt ?? '',
    sort_order: banner?.sort_order ?? 0,
    starts_at: banner?.starts_at ? banner.starts_at.slice(0, 16) : '',
    ends_at: banner?.ends_at ? banner.ends_at.slice(0, 16) : '',
    is_active: banner?.is_active ?? true,
  })
  const [saving, setSaving] = useState(false)
  const [uploading, setUploading] = useState(false)
  const pcInputRef = useRef<HTMLInputElement>(null)

  function set(key: string, value: any) {
    setForm(f => ({ ...f, [key]: value }))
  }

  async function uploadImage(file: File): Promise<string | null> {
    const fd = new FormData()
    fd.append('file', file)
    const res = await fetch('/api/v1/admin/upload', { method: 'POST', body: fd })
    const data = await res.json()
    if (data.error) { alert(data.error); return null }
    return data.url
  }

  async function handlePcUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setUploading(true)
    const url = await uploadImage(file)
    setUploading(false)
    if (url) set('image_url', url)
    e.target.value = ''
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!form.image_url) { alert('PC 배너 이미지를 등록하세요'); return }
    setSaving(true)
    const payload = {
      ...form,
      sort_order: Number(form.sort_order),
      link_url: form.link_url || null,
      alt: form.alt || null,
      starts_at: form.starts_at || null,
      ends_at: form.ends_at || null,
    }
    const url = isEdit ? `/api/v1/admin/banners/${banner.id}` : '/api/v1/admin/banners'
    const method = isEdit ? 'PATCH' : 'POST'
    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    const data = await res.json()
    setSaving(false)
    if (data.error) { alert(data.error); return }
    router.push('/admin/banners')
    router.refresh()
  }

  async function handleDelete() {
    if (!confirm('배너를 삭제하시겠습니까?')) return
    await fetch(`/api/v1/admin/banners/${banner.id}`, { method: 'DELETE' })
    router.push('/admin/banners')
    router.refresh()
  }

  const inputClass = "w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"

  return (
    <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
      <div className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 className="font-semibold text-gray-800">기본 정보</h2>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">배너명 *</label>
          <input value={form.name} onChange={e => set('name', e.target.value)} required className={inputClass} />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">위치 *</label>
            <select value={form.position} onChange={e => set('position', e.target.value)} className={inputClass}>
              {Object.entries(POSITION_LABEL).map(([v, l]) => (
                <option key={v} value={v}>{l}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">정렬 순서</label>
            <input type="number" value={form.sort_order} onChange={e => set('sort_order', e.target.value)} className={inputClass} />
          </div>
        </div>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">링크 URL</label>
          <input value={form.link_url} onChange={e => set('link_url', e.target.value)}
            placeholder="https://..." className={inputClass} />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">시작일시</label>
            <input type="datetime-local" value={form.starts_at} onChange={e => set('starts_at', e.target.value)} className={inputClass} />
          </div>
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">종료일시</label>
            <input type="datetime-local" value={form.ends_at} onChange={e => set('ends_at', e.target.value)} className={inputClass} />
          </div>
        </div>

        <label className="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)}
            className="w-4 h-4 accent-blue-600" />
          <span className="text-sm">활성화</span>
        </label>
      </div>

      {/* 이미지 */}
      <div className="bg-white rounded-xl shadow-sm p-6 space-y-5">
        <h2 className="font-semibold text-gray-800">이미지</h2>

        {/* PC 이미지 */}
        <div>
          <label className="text-xs font-medium text-gray-600 mb-2 block">PC 배너 이미지 * <span className="text-gray-400">(권장: 1280×400px)</span></label>
          <div className="flex items-start gap-4">
            <button type="button" onClick={() => pcInputRef.current?.click()} disabled={uploading}
              className="flex-shrink-0 w-32 h-20 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center gap-1 hover:border-blue-400 hover:bg-blue-50 disabled:opacity-50">
              <Upload className="w-5 h-5 text-gray-400" />
              <span className="text-xs text-gray-400">업로드</span>
            </button>
            <input ref={pcInputRef} type="file" accept="image/*" className="hidden" onChange={handlePcUpload} />
            {form.image_url && (
              <div className="relative flex-1">
                <img src={form.image_url} alt="PC 배너" className="w-full h-24 object-cover rounded-xl border border-gray-200" />
                <button type="button" onClick={() => set('image_url', '')}
                  className="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center">
                  <X className="w-3 h-3" />
                </button>
              </div>
            )}
            {!form.image_url && (
              <input value={form.image_url} onChange={e => set('image_url', e.target.value)}
                placeholder="또는 URL 직접 입력" className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none" />
            )}
          </div>
        </div>

        {uploading && <p className="text-xs text-blue-600">업로드 중...</p>}
      </div>

      <div className="flex gap-3">
        <button type="submit" disabled={saving}
          className="flex-1 bg-blue-700 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-600 disabled:opacity-50">
          {saving ? '저장 중...' : isEdit ? '수정 완료' : '배너 등록'}
        </button>
        <a href="/admin/banners" className="px-6 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
          취소
        </a>
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
