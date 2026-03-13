'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Plus, Pencil, Trash2, GripVertical } from 'lucide-react'

interface Brand {
  id: number
  name: string
  slug: string
  logo_url: string | null
  description: string | null
  sort_order: number
  is_active: boolean
}

export default function BrandListClient({ brands }: { brands: Brand[] }) {
  const router = useRouter()
  const [showForm, setShowForm] = useState(false)
  const [editId, setEditId] = useState<number | null>(null)
  const [form, setForm] = useState({ name: '', slug: '', description: '', sort_order: 0, is_active: true })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const resetForm = () => {
    setForm({ name: '', slug: '', description: '', sort_order: 0, is_active: true })
    setEditId(null)
    setShowForm(false)
    setError('')
  }

  const startEdit = (brand: Brand) => {
    setForm({
      name: brand.name,
      slug: brand.slug,
      description: brand.description ?? '',
      sort_order: brand.sort_order,
      is_active: brand.is_active,
    })
    setEditId(brand.id)
    setShowForm(true)
    setError('')
  }

  const startAdd = () => {
    resetForm()
    setShowForm(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')

    const url = editId ? `/api/v1/admin/brands/${editId}` : '/api/v1/admin/brands'
    const method = editId ? 'PUT' : 'POST'

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      })
      const json = await res.json().catch(() => ({}))
      if (!res.ok) { setError(json.error ?? '저장에 실패했습니다.'); return }
      resetForm()
      router.refresh()
    } catch {
      setError('저장에 실패했습니다.')
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`"${name}" 브랜드를 삭제하시겠습니까?`)) return

    try {
      const res = await fetch(`/api/v1/admin/brands/${id}`, { method: 'DELETE' })
      const json = await res.json().catch(() => ({}))
      if (!res.ok) { alert(json.error ?? '삭제에 실패했습니다.'); return }
      router.refresh()
    } catch {
      alert('삭제에 실패했습니다.')
    }
  }

  const handleNameChange = (v: string) => {
    setForm(prev => ({
      ...prev,
      name: v,
      slug: !editId ? v.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9가-힣-]/g, '') : prev.slug,
    }))
  }

  return (
    <div>
      {/* 추가/수정 폼 */}
      {showForm && (
        <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
          <h2 className="text-base font-semibold text-gray-900 mb-4">
            {editId ? '브랜드 수정' : '브랜드 추가'}
          </h2>
          {error && <p className="text-sm text-red-600 mb-3">{error}</p>}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">브랜드명 *</label>
                <input value={form.name} onChange={e => handleNameChange(e.target.value)} required
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500"
                  placeholder="예: 설성목장" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">슬러그 *</label>
                <input value={form.slug} onChange={e => setForm(prev => ({ ...prev, slug: e.target.value }))} required
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 font-mono"
                  placeholder="예: sulsung" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">정렬 순서</label>
                <input type="number" value={form.sort_order} onChange={e => setForm(prev => ({ ...prev, sort_order: Number(e.target.value) }))}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500" />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">설명</label>
              <input value={form.description} onChange={e => setForm(prev => ({ ...prev, description: e.target.value }))}
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500"
                placeholder="브랜드에 대한 간단한 설명" />
            </div>
            <div className="flex items-center gap-2">
              <input type="checkbox" id="brand-active" checked={form.is_active}
                onChange={e => setForm(prev => ({ ...prev, is_active: e.target.checked }))} />
              <label htmlFor="brand-active" className="text-sm text-gray-700">활성</label>
            </div>
            <div className="flex gap-2">
              <button type="submit" disabled={loading}
                className="px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-lg hover:bg-green-800 disabled:opacity-50">
                {loading ? '저장 중...' : editId ? '수정' : '추가'}
              </button>
              <button type="button" onClick={resetForm}
                className="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200">
                취소
              </button>
            </div>
          </form>
        </div>
      )}

      {/* 브랜드 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <span className="text-sm text-gray-500">총 <strong>{brands.length}</strong>건</span>
          <button onClick={startAdd} className="flex items-center gap-1 px-3 py-1.5 bg-green-700 text-white text-xs font-semibold rounded-lg hover:bg-green-800">
            <Plus className="w-3.5 h-3.5" /> 브랜드 추가
          </button>
        </div>
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-16">순서</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">브랜드명</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-32">슬러그</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">설명</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-20">상태</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-32">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {brands.map(brand => (
              <tr key={brand.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-gray-400 text-xs">
                  <div className="flex items-center gap-1">
                    <GripVertical className="w-3.5 h-3.5 text-gray-300" />
                    {brand.sort_order}
                  </div>
                </td>
                <td className="px-4 py-3 text-gray-800 font-medium">{brand.name}</td>
                <td className="px-4 py-3 text-xs text-gray-500 font-mono">{brand.slug}</td>
                <td className="px-4 py-3 text-xs text-gray-500 truncate max-w-xs">{brand.description}</td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    brand.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {brand.is_active ? '활성' : '비활성'}
                  </span>
                </td>
                <td className="px-4 py-3 text-center">
                  <div className="flex items-center justify-center gap-2">
                    <button onClick={() => startEdit(brand)} className="text-gray-400 hover:text-blue-600">
                      <Pencil className="w-4 h-4" />
                    </button>
                    <button onClick={() => handleDelete(brand.id, brand.name)} className="text-gray-400 hover:text-red-600">
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {brands.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-gray-400">등록된 브랜드가 없습니다.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
