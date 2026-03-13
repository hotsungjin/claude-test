'use client'

import { useState, useEffect } from 'react'
import { Plus, Pencil, Trash2, Package } from 'lucide-react'
import Link from 'next/link'

interface Curation {
  id: string
  name: string
  slug: string
  description: string | null
  sort_order: number
  is_active: boolean
  curation_goods: { count: number }[]
}

export default function CurationListClient() {
  const [curations, setCurations] = useState<Curation[]>([])
  const [showForm, setShowForm] = useState(false)
  const [editId, setEditId] = useState<string | null>(null)
  const [form, setForm] = useState({ name: '', slug: '', description: '' })
  const [error, setError] = useState('')

  useEffect(() => { load() }, [])

  async function load() {
    const res = await fetch('/api/v1/admin/curations')
    const { data } = await res.json()
    setCurations(data ?? [])
  }

  function openAdd() {
    setForm({ name: '', slug: '', description: '' })
    setEditId(null)
    setShowForm(true)
    setError('')
  }

  function openEdit(c: Curation) {
    setForm({ name: c.name, slug: c.slug, description: c.description ?? '' })
    setEditId(c.id)
    setShowForm(true)
    setError('')
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')

    const url = editId ? `/api/v1/admin/curations/${editId}` : '/api/v1/admin/curations'
    const method = editId ? 'PUT' : 'POST'

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })

    if (!res.ok) {
      const { error } = await res.json()
      setError(error || '저장 실패')
      return
    }

    setShowForm(false)
    load()
  }

  async function handleDelete(id: string) {
    if (!confirm('삭제하시겠습니까?')) return
    await fetch(`/api/v1/admin/curations/${id}`, { method: 'DELETE' })
    load()
  }

  async function toggleActive(c: Curation) {
    await fetch(`/api/v1/admin/curations/${c.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ is_active: !c.is_active }),
    })
    load()
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <button onClick={openAdd}
          className="flex items-center gap-1.5 bg-green-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-800">
          <Plus className="w-4 h-4" /> 큐레이션 추가
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h3 className="text-base font-semibold mb-3">{editId ? '큐레이션 수정' : '큐레이션 추가'}</h3>
          {error && <p className="text-red-500 text-sm mb-3">{error}</p>}
          <form onSubmit={handleSubmit} className="space-y-3">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs text-gray-500 mb-1">이름 *</label>
                <input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" required />
              </div>
              <div>
                <label className="block text-xs text-gray-500 mb-1">슬러그 *</label>
                <input value={form.slug} onChange={e => setForm(f => ({ ...f, slug: e.target.value }))}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" required
                  placeholder="sale, daily 등" />
              </div>
            </div>
            <div>
              <label className="block text-xs text-gray-500 mb-1">설명</label>
              <input value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div className="flex gap-2">
              <button type="submit" className="bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                {editId ? '수정' : '추가'}
              </button>
              <button type="button" onClick={() => setShowForm(false)}
                className="border border-gray-200 px-4 py-2 rounded-lg text-sm text-gray-600">취소</button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이름</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">슬러그</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상품 수</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {curations.map(c => (
              <tr key={c.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-900">{c.name}</td>
                <td className="px-4 py-3 text-gray-500 font-mono text-xs">{c.slug}</td>
                <td className="px-4 py-3 text-center text-gray-700">
                  {c.curation_goods?.[0]?.count ?? 0}개
                </td>
                <td className="px-4 py-3 text-center">
                  <button onClick={() => toggleActive(c)}
                    className={`text-xs px-2 py-0.5 rounded-full ${c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'}`}>
                    {c.is_active ? '활성' : '비활성'}
                  </button>
                </td>
                <td className="px-4 py-3 text-center">
                  <div className="flex items-center justify-center gap-2">
                    <Link href={`/admin/curations/${c.id}`}
                      className="text-blue-600 hover:text-blue-800" title="상품 관리">
                      <Package className="w-4 h-4" />
                    </Link>
                    <button onClick={() => openEdit(c)} className="text-gray-400 hover:text-gray-600">
                      <Pencil className="w-4 h-4" />
                    </button>
                    <button onClick={() => handleDelete(c.id)} className="text-red-400 hover:text-red-600">
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {curations.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-12 text-center text-gray-400">큐레이션이 없습니다.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
