'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

interface CouponFormProps {
  coupon?: any
}

export default function CouponFormClient({ coupon }: CouponFormProps) {
  const router = useRouter()
  const isEdit = !!coupon

  const [form, setForm] = useState({
    name: coupon?.name ?? '',
    code: coupon?.code ?? '',
    type: coupon?.type ?? 'amount',
    discount_amount: coupon?.discount_amount ?? '',
    discount_rate: coupon?.discount_rate ?? '',
    max_discount: coupon?.max_discount ?? '',
    min_order_amount: coupon?.min_order_amount ?? 0,
    max_uses: coupon?.max_uses ?? '',
    starts_at: coupon?.starts_at ? coupon.starts_at.slice(0, 16) : '',
    expires_at: coupon?.expires_at ? coupon.expires_at.slice(0, 16) : '',
    is_active: coupon?.is_active ?? true,
    is_duplicate: coupon?.is_duplicate ?? false,
  })
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)

  function set(key: string, value: any) {
    setForm(f => ({ ...f, [key]: value }))
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!form.name) { alert('쿠폰명을 입력하세요'); return }

    setSaving(true)
    const payload: any = {
      name: form.name,
      code: form.code || null,
      type: form.type,
      min_order_amount: Number(form.min_order_amount) || 0,
      max_uses: form.max_uses ? Number(form.max_uses) : null,
      starts_at: form.starts_at || null,
      expires_at: form.expires_at || null,
      is_active: form.is_active,
      is_duplicate: form.is_duplicate,
    }
    if (form.type === 'amount') payload.discount_amount = Number(form.discount_amount) || 0
    if (form.type === 'rate') {
      payload.discount_rate = Number(form.discount_rate) || 0
      payload.max_discount = form.max_discount ? Number(form.max_discount) : null
    }

    const url = isEdit ? `/api/v1/admin/coupons/${coupon.id}` : '/api/v1/admin/coupons'
    const method = isEdit ? 'PATCH' : 'POST'
    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    const data = await res.json()
    setSaving(false)

    if (data.error) { alert(data.error); return }
    router.push('/admin/coupons')
    router.refresh()
  }

  async function handleDelete() {
    if (!confirm('쿠폰을 삭제하시겠습니까?')) return
    setDeleting(true)
    const res = await fetch(`/api/v1/admin/coupons/${coupon.id}`, { method: 'DELETE' })
    const data = await res.json()
    setDeleting(false)
    if (data.error) { alert(data.error); return }
    router.push('/admin/coupons')
    router.refresh()
  }

  const inputClass = "w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500"

  return (
    <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
      {/* 기본 정보 */}
      <div className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 className="font-semibold text-gray-800 mb-2">기본 정보</h2>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">쿠폰명 *</label>
          <input value={form.name} onChange={e => set('name', e.target.value)}
            placeholder="예: 신규회원 5,000원 할인" className={inputClass} />
        </div>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">쿠폰 코드 (비워두면 자동발급)</label>
          <input value={form.code} onChange={e => set('code', e.target.value.toUpperCase())}
            placeholder="예: WELCOME2024" className={inputClass} />
          <p className="text-xs text-gray-400 mt-1">코드 입력 시 회원이 직접 등록하는 방식, 비워두면 자동 발급 쿠폰</p>
        </div>

        <div className="flex items-center gap-4">
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)}
              className="w-4 h-4 accent-green-600" />
            <span className="text-sm">활성화</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" checked={form.is_duplicate} onChange={e => set('is_duplicate', e.target.checked)}
              className="w-4 h-4 accent-green-600" />
            <span className="text-sm">다른 쿠폰과 중복 사용 허용</span>
          </label>
        </div>
      </div>

      {/* 할인 설정 */}
      <div className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 className="font-semibold text-gray-800 mb-2">할인 설정</h2>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">할인 유형 *</label>
          <select value={form.type} onChange={e => set('type', e.target.value)} className={inputClass}>
            <option value="amount">정액 할인 (원)</option>
            <option value="rate">정률 할인 (%)</option>
            <option value="shipping">배송비 무료</option>
          </select>
        </div>

        {form.type === 'amount' && (
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">할인 금액 (원) *</label>
            <input type="number" value={form.discount_amount} onChange={e => set('discount_amount', e.target.value)}
              placeholder="5000" min={0} className={inputClass} />
          </div>
        )}

        {form.type === 'rate' && (
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-medium text-gray-600 mb-1 block">할인율 (%) *</label>
              <input type="number" value={form.discount_rate} onChange={e => set('discount_rate', e.target.value)}
                placeholder="10" min={0} max={100} className={inputClass} />
            </div>
            <div>
              <label className="text-xs font-medium text-gray-600 mb-1 block">최대 할인 금액 (원)</label>
              <input type="number" value={form.max_discount} onChange={e => set('max_discount', e.target.value)}
                placeholder="10000" min={0} className={inputClass} />
            </div>
          </div>
        )}

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">최소 주문 금액 (원)</label>
          <input type="number" value={form.min_order_amount} onChange={e => set('min_order_amount', e.target.value)}
            placeholder="0" min={0} className={inputClass} />
        </div>
      </div>

      {/* 발급 조건 */}
      <div className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 className="font-semibold text-gray-800 mb-2">발급 조건</h2>

        <div>
          <label className="text-xs font-medium text-gray-600 mb-1 block">최대 발급 수량 (비워두면 무제한)</label>
          <input type="number" value={form.max_uses} onChange={e => set('max_uses', e.target.value)}
            placeholder="무제한" min={1} className={inputClass} />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">시작일시</label>
            <input type="datetime-local" value={form.starts_at} onChange={e => set('starts_at', e.target.value)}
              className={inputClass} />
          </div>
          <div>
            <label className="text-xs font-medium text-gray-600 mb-1 block">만료일시</label>
            <input type="datetime-local" value={form.expires_at} onChange={e => set('expires_at', e.target.value)}
              className={inputClass} />
          </div>
        </div>
      </div>

      {/* 버튼 */}
      <div className="flex gap-3">
        <button type="submit" disabled={saving}
          className="flex-1 bg-green-700 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-green-600 disabled:opacity-50">
          {saving ? '저장 중...' : isEdit ? '수정 완료' : '쿠폰 생성'}
        </button>
        <a href="/admin/coupons"
          className="px-6 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
          취소
        </a>
        {isEdit && (
          <button type="button" onClick={handleDelete} disabled={deleting}
            className="px-6 py-2.5 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100 disabled:opacity-50">
            {deleting ? '삭제 중...' : '삭제'}
          </button>
        )}
      </div>
    </form>
  )
}
