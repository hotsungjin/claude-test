'use client'

import { useState, useCallback, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { Upload, X, ImagePlus } from 'lucide-react'

interface Category { id: number; name: string; parent_id: number | null }
interface Brand { id: number; name: string }
interface GoodsData {
  id?: string
  name?: string; slug?: string; category_id?: number | null; summary?: string; description?: string
  origin?: string; manufacturer?: string; brand?: string; brand_id?: number | null; weight?: number
  price?: number; sale_price?: number | null; member_price?: number | null; cost_price?: number | null
  tax_type?: string; status?: string; stock?: number; stock_alert_qty?: number
  mileage_rate?: number; is_option?: boolean; is_gift?: boolean
  thumbnail_url?: string | null; images?: any[]; tags?: string[]
  naver_category?: string; sort_order?: number; required_info?: Record<string, string>
}

interface Props { categories: Category[]; brands?: Brand[]; initialData?: GoodsData }

export default function GoodsFormClient({ categories, brands = [], initialData }: Props) {
  const router = useRouter()
  const isEdit = !!initialData?.id

  const [form, setForm] = useState({
    name: initialData?.name ?? '',
    slug: initialData?.slug ?? '',
    category_id: initialData?.category_id ?? '',
    summary: initialData?.summary ?? '',
    description: initialData?.description ?? '',
    origin: initialData?.origin ?? '',
    manufacturer: initialData?.manufacturer ?? '',
    brand: initialData?.brand ?? '',
    brand_id: initialData?.brand_id ?? '',
    weight: initialData?.weight ?? '',
    price: initialData?.price ?? '',
    sale_price: initialData?.sale_price ?? '',
    member_price: initialData?.member_price ?? '',
    cost_price: initialData?.cost_price ?? '',
    tax_type: initialData?.tax_type ?? 'taxable',
    status: initialData?.status ?? 'active',
    stock: initialData?.stock ?? 0,
    stock_alert_qty: initialData?.stock_alert_qty ?? 5,
    mileage_rate: initialData?.mileage_rate ?? 1,
    is_option: initialData?.is_option ?? false,
    is_gift: initialData?.is_gift ?? false,
    thumbnail_url: initialData?.thumbnail_url ?? '',
    tags: (initialData?.tags ?? []).join(', '),
    naver_category: initialData?.naver_category ?? '',
    sort_order: initialData?.sort_order ?? 0,
  })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [uploading, setUploading] = useState(false)
  const [extraImages, setExtraImages] = useState<{ url: string; sort_order: number; alt: string }[]>(
    initialData?.images ?? []
  )
  const thumbInputRef = useRef<HTMLInputElement>(null)
  const extraInputRef = useRef<HTMLInputElement>(null)

  const set = useCallback((key: string, value: any) => setForm(f => ({ ...f, [key]: value })), [])

  async function uploadFile(file: File): Promise<string | null> {
    const fd = new FormData()
    fd.append('file', file)
    const res = await fetch('/api/v1/admin/upload', { method: 'POST', body: fd })
    const data = await res.json()
    if (data.error) { alert(data.error); return null }
    return data.url
  }

  async function handleThumbUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return
    setUploading(true)
    const url = await uploadFile(file)
    setUploading(false)
    if (url) set('thumbnail_url', url)
    e.target.value = ''
  }

  async function handleExtraUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const files = Array.from(e.target.files ?? [])
    if (!files.length) return
    setUploading(true)
    const urls = await Promise.all(files.map(uploadFile))
    setUploading(false)
    const newImages = urls
      .filter(Boolean)
      .map((url, i) => ({ url: url!, sort_order: extraImages.length + i, alt: '' }))
    setExtraImages(prev => [...prev, ...newImages])
    e.target.value = ''
  }

  function removeExtraImage(idx: number) {
    setExtraImages(prev => prev.filter((_, i) => i !== idx).map((img, i) => ({ ...img, sort_order: i })))
  }

  // 상품명으로 슬러그 자동 생성 (영문 입력 시)
  const handleNameChange = (v: string) => {
    set('name', v)
    if (!isEdit && !form.slug) {
      const auto = v.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')
      if (auto) set('slug', auto)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')

    const payload: any = {
      name: form.name,
      slug: form.slug || undefined,
      category_id: form.category_id ? Number(form.category_id) : null,
      summary: form.summary || null,
      description: form.description || null,
      origin: form.origin || null,
      manufacturer: form.manufacturer || null,
      brand: form.brand || null,
      brand_id: form.brand_id ? Number(form.brand_id) : null,
      weight: form.weight !== '' ? Number(form.weight) : null,
      price: Number(form.price),
      sale_price: form.sale_price !== '' ? Number(form.sale_price) : null,
      member_price: form.member_price !== '' ? Number(form.member_price) : null,
      cost_price: form.cost_price !== '' ? Number(form.cost_price) : null,
      tax_type: form.tax_type,
      status: form.status,
      stock: Number(form.stock),
      stock_alert_qty: Number(form.stock_alert_qty),
      mileage_rate: Number(form.mileage_rate),
      is_option: form.is_option,
      is_gift: form.is_gift,
      thumbnail_url: form.thumbnail_url || null,
      tags: form.tags.split(',').map((t: string) => t.trim()).filter(Boolean),
      naver_category: form.naver_category || null,
      sort_order: Number(form.sort_order),
      images: extraImages,
      required_info: initialData?.required_info ?? {},
    }

    const url = isEdit ? `/api/v1/admin/goods/${initialData.id}` : '/api/v1/admin/goods'
    const method = isEdit ? 'PATCH' : 'POST'

    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    const json = await res.json()

    if (!res.ok) {
      setError(json.error ?? '저장 실패')
      setLoading(false)
      return
    }

    router.push('/admin/goods')
    router.refresh()
  }

  const handleDelete = async () => {
    if (!confirm('상품을 삭제하시겠습니까? (목록에서 숨김 처리됩니다)')) return
    await fetch(`/api/v1/admin/goods/${initialData!.id}`, { method: 'DELETE' })
    router.push('/admin/goods')
    router.refresh()
  }

  const topCategories = categories.filter(c => !c.parent_id)
  const subCategories = categories.filter(c => c.parent_id)

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {error && <div className="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">{error}</div>}

      {/* 기본 정보 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">기본 정보</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-1">상품명 <span className="text-red-500">*</span></label>
            <input value={form.name} onChange={e => handleNameChange(e.target.value)} required
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">슬러그</label>
            <input value={form.slug} onChange={e => set('slug', e.target.value)}
              placeholder="비워두면 자동 생성됩니다"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 font-mono" />
            <p className="text-xs text-gray-400 mt-1">비워두면 자동 생성. URL에 표시됩니다.</p>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">카테고리 <span className="text-red-500">*</span></label>
            <select value={form.category_id} onChange={e => set('category_id', e.target.value)} required
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
              <option value="">카테고리 선택</option>
              {topCategories.map(cat => (
                <optgroup key={cat.id} label={cat.name}>
                  <option value={cat.id}>{cat.name}</option>
                  {subCategories.filter(s => s.parent_id === cat.id).map(sub => (
                    <option key={sub.id} value={sub.id}>　{sub.name}</option>
                  ))}
                </optgroup>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">요약 설명</label>
            <input value={form.summary} onChange={e => set('summary', e.target.value)}
              placeholder="목록에 표시되는 짧은 설명"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">태그</label>
            <input value={form.tags} onChange={e => set('tags', e.target.value)}
              placeholder="우유, 목장, 신선식품 (쉼표로 구분)"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">원산지</label>
            <input value={form.origin} onChange={e => set('origin', e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">제조사</label>
            <input value={form.manufacturer} onChange={e => set('manufacturer', e.target.value)}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">브랜드 <span className="text-red-500">*</span></label>
            <select value={form.brand_id} onChange={e => set('brand_id', e.target.value)} required
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
              <option value="">브랜드 선택</option>
              {brands.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">무게 (g)</label>
            <input type="number" value={form.weight} onChange={e => set('weight', e.target.value)} min={0}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
        </div>
      </section>

      {/* 가격/재고 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">가격 / 재고</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">정가 <span className="text-red-500">*</span></label>
            <input type="number" value={form.price} onChange={e => set('price', e.target.value)} required min={1}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">할인가</label>
            <input type="number" value={form.sale_price} onChange={e => set('sale_price', e.target.value)} min={1}
              placeholder="비워두면 정가 적용"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">멤버십가</label>
            <input type="number" value={form.member_price} onChange={e => set('member_price', e.target.value)} min={1}
              placeholder="비워두면 멤버십가 미표시"
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">원가 (내부용)</label>
            <input type="number" value={form.cost_price} onChange={e => set('cost_price', e.target.value)} min={1}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">과세유형 <span className="text-red-500">*</span></label>
            <select value={form.tax_type} onChange={e => set('tax_type', e.target.value)} required
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
              <option value="taxable">과세</option>
              <option value="free">면세</option>
              <option value="zero">영세율</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">재고</label>
            <input type="number" value={form.stock} onChange={e => set('stock', Number(e.target.value))} min={0}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">재고알림 기준</label>
            <input type="number" value={form.stock_alert_qty} onChange={e => set('stock_alert_qty', Number(e.target.value))} min={0}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">포인트 적립률 (%)</label>
            <input type="number" value={form.mileage_rate} onChange={e => set('mileage_rate', Number(e.target.value))} min={0} max={100} step={0.1}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">정렬순서</label>
            <input type="number" value={form.sort_order} onChange={e => set('sort_order', Number(e.target.value))}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          </div>
        </div>
      </section>

      {/* 상태/옵션 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">판매 상태 / 옵션</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">판매 상태</label>
            <div className="flex gap-3">
              {[['active', '판매중'], ['inactive', '비공개'], ['soldout', '품절']].map(([v, l]) => (
                <label key={v} className="flex items-center gap-1.5 cursor-pointer">
                  <input type="radio" name="status" value={v} checked={form.status === v} onChange={() => set('status', v)}
                    className="accent-blue-600" />
                  <span className="text-sm">{l}</span>
                </label>
              ))}
            </div>
          </div>
          <div className="flex gap-6">
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" checked={form.is_option} onChange={e => set('is_option', e.target.checked)}
                className="w-4 h-4 accent-blue-600" />
              <span className="text-sm font-medium text-gray-700">옵션 상품</span>
            </label>
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" checked={form.is_gift} onChange={e => set('is_gift', e.target.checked)}
                className="w-4 h-4 accent-blue-600" />
              <span className="text-sm font-medium text-gray-700">사은품</span>
            </label>
          </div>
        </div>
      </section>

      {/* 이미지 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">이미지</h2>

        {/* 대표 이미지 */}
        <div className="mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">대표 이미지 (썸네일)</label>
          <div className="flex items-start gap-4">
            {/* 업로드 버튼 */}
            <button type="button" onClick={() => thumbInputRef.current?.click()}
              disabled={uploading}
              className="flex-shrink-0 w-32 h-32 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center gap-1 hover:border-blue-400 hover:bg-blue-50 transition-colors disabled:opacity-50">
              <Upload className="w-5 h-5 text-gray-400" />
              <span className="text-xs text-gray-400">파일 업로드</span>
            </button>
            <input ref={thumbInputRef} type="file" accept="image/*" className="hidden" onChange={handleThumbUpload} />

            {/* 미리보기 */}
            {form.thumbnail_url && (
              <div className="relative">
                <img src={form.thumbnail_url} alt="대표 이미지"
                  className="w-32 h-32 object-cover rounded-xl border border-gray-200" />
                <button type="button" onClick={() => set('thumbnail_url', '')}
                  className="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600">
                  <X className="w-3 h-3" />
                </button>
              </div>
            )}

            {/* URL 직접 입력 */}
            <div className="flex-1">
              <label className="block text-xs text-gray-500 mb-1">또는 URL 직접 입력</label>
              <input value={form.thumbnail_url} onChange={e => set('thumbnail_url', e.target.value)}
                placeholder="https://..."
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
            </div>
          </div>
        </div>

        {/* 추가 이미지 */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">추가 이미지 (슬라이더)</label>
          <div className="flex flex-wrap gap-3">
            {extraImages.map((img, idx) => (
              <div key={idx} className="relative">
                <img src={img.url} alt={`추가 ${idx + 1}`}
                  className="w-24 h-24 object-cover rounded-xl border border-gray-200" />
                <button type="button" onClick={() => removeExtraImage(idx)}
                  className="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                  <X className="w-3 h-3" />
                </button>
                <span className="absolute bottom-1 left-1 text-[10px] bg-black/50 text-white px-1 rounded">
                  {idx + 1}
                </span>
              </div>
            ))}
            <button type="button" onClick={() => extraInputRef.current?.click()}
              disabled={uploading}
              className="w-24 h-24 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center gap-1 hover:border-blue-400 hover:bg-blue-50 transition-colors disabled:opacity-50">
              <ImagePlus className="w-5 h-5 text-gray-400" />
              <span className="text-xs text-gray-400">추가</span>
            </button>
            <input ref={extraInputRef} type="file" accept="image/*" multiple className="hidden" onChange={handleExtraUpload} />
          </div>
          {uploading && <p className="text-xs text-blue-600 mt-2">업로드 중...</p>}
        </div>
      </section>

      {/* 채널 피드 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">채널 피드</h2>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">네이버 쇼핑 카테고리 코드</label>
          <input value={form.naver_category} onChange={e => set('naver_category', e.target.value)}
            placeholder="예: 50000803"
            className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
          <p className="text-xs text-gray-400 mt-1">네이버 쇼핑 카테고리 코드를 입력하면 XML 피드에 포함됩니다.</p>
        </div>
      </section>

      {/* 상세 설명 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">상세 설명</h2>
        <textarea value={form.description} onChange={e => set('description', e.target.value)} rows={12}
          placeholder="상세 설명 HTML 또는 텍스트를 입력하세요"
          className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-500 resize-y" />
      </section>

      {/* 버튼 */}
      <div className="flex items-center justify-between pb-8">
        <div>
          {isEdit && (
            <button type="button" onClick={handleDelete}
              className="text-sm text-red-500 hover:text-red-700 underline">
              상품 삭제
            </button>
          )}
        </div>
        <div className="flex gap-3">
          <button type="button" onClick={() => router.back()}
            className="px-5 py-2.5 text-sm border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50">
            취소
          </button>
          <button type="submit" disabled={loading}
            className="px-6 py-2.5 bg-blue-700 text-white text-sm font-semibold rounded-xl hover:bg-blue-800 disabled:opacity-50">
            {loading ? '저장 중...' : isEdit ? '수정 완료' : '상품 등록'}
          </button>
        </div>
      </div>
    </form>
  )
}
