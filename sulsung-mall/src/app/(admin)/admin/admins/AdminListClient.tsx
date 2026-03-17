'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Plus, Pencil, Trash2, Shield, ShieldCheck } from 'lucide-react'

const ALL_PERMISSIONS = [
  { key: 'orders', label: '주문 관리' },
  { key: 'goods', label: '상품 관리' },
  { key: 'categories', label: '카테고리 관리' },
  { key: 'members', label: '회원 관리' },
  { key: 'coupons', label: '쿠폰 관리' },
  { key: 'reviews', label: '리뷰 관리' },
  { key: 'faq', label: 'FAQ' },
  { key: 'inquiries', label: '1:1 문의' },
  { key: 'notices', label: '공지사항' },
  { key: 'banners', label: '배너 관리' },
  { key: 'time_sales', label: '타임세일' },
  { key: 'popups', label: '팝업 관리' },
  { key: 'stats', label: '통계' },
  { key: 'email', label: '이메일 발송' },
  { key: 'notifications', label: '알림 설정' },
  { key: 'settings', label: '쇼핑몰 설정' },
]

interface Admin {
  id: string
  email: string
  name: string
  role: 'super' | 'admin'
  permissions: string[]
  created_at: string
}

export default function AdminListClient({ currentAdminId }: { currentAdminId: string }) {
  const router = useRouter()
  const [admins, setAdmins] = useState<Admin[]>([])
  const [showForm, setShowForm] = useState(false)
  const [editId, setEditId] = useState<string | null>(null)
  const [form, setForm] = useState({ email: '', name: '', role: 'admin' as 'super' | 'admin', permissions: [] as string[] })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    fetch('/api/v1/admin/admins').then(r => r.json()).then(j => setAdmins(j.data ?? []))
  }, [])

  const resetForm = () => {
    setForm({ email: '', name: '', role: 'admin', permissions: [] })
    setEditId(null)
    setShowForm(false)
    setError('')
  }

  const startEdit = (admin: Admin) => {
    setForm({ email: admin.email, name: admin.name, role: admin.role, permissions: admin.permissions })
    setEditId(admin.id)
    setShowForm(true)
    setError('')
  }

  const togglePermission = (key: string) => {
    setForm(prev => ({
      ...prev,
      permissions: prev.permissions.includes(key)
        ? prev.permissions.filter(p => p !== key)
        : [...prev.permissions, key],
    }))
  }

  const toggleAll = () => {
    if (form.permissions.length === ALL_PERMISSIONS.length) {
      setForm(prev => ({ ...prev, permissions: [] }))
    } else {
      setForm(prev => ({ ...prev, permissions: ALL_PERMISSIONS.map(p => p.key) }))
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')

    const url = editId ? `/api/v1/admin/admins/${editId}` : '/api/v1/admin/admins'
    const method = editId ? 'PUT' : 'POST'

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(editId ? { name: form.name, role: form.role, permissions: form.permissions } : form),
      })
      const json = await res.json().catch(() => ({}))
      if (!res.ok) { setError(json.error ?? '저장에 실패했습니다.'); return }
      resetForm()
      // 새로고침
      const r = await fetch('/api/v1/admin/admins')
      const j = await r.json()
      setAdmins(j.data ?? [])
    } catch {
      setError('저장에 실패했습니다.')
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async (id: string, name: string) => {
    if (!confirm(`"${name}" 관리자를 삭제하시겠습니까?`)) return
    try {
      const res = await fetch(`/api/v1/admin/admins/${id}`, { method: 'DELETE' })
      const json = await res.json().catch(() => ({}))
      if (!res.ok) { alert(json.error ?? '삭제에 실패했습니다.'); return }
      setAdmins(prev => prev.filter(a => a.id !== id))
    } catch {
      alert('삭제에 실패했습니다.')
    }
  }

  return (
    <div>
      {/* 추가/수정 폼 */}
      {showForm && (
        <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
          <h2 className="text-base font-semibold text-gray-900 mb-4">
            {editId ? '관리자 수정' : '관리자 추가'}
          </h2>
          {error && <p className="text-sm text-red-600 mb-3">{error}</p>}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">이메일 *</label>
                <input value={form.email} onChange={e => setForm(prev => ({ ...prev, email: e.target.value }))}
                  required disabled={!!editId} type="email"
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 disabled:bg-gray-50"
                  placeholder="admin@example.com" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">이름 *</label>
                <input value={form.name} onChange={e => setForm(prev => ({ ...prev, name: e.target.value }))}
                  required className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">등급</label>
                <select value={form.role} onChange={e => setForm(prev => ({ ...prev, role: e.target.value as any }))}
                  disabled={editId === currentAdminId}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                  <option value="super">최고 관리자</option>
                  <option value="admin">일반 관리자</option>
                </select>
              </div>
            </div>

            {/* 권한 선택 (일반 관리자일 때만) */}
            {form.role === 'admin' && (
              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="block text-sm font-medium text-gray-700">접근 권한</label>
                  <button type="button" onClick={toggleAll} className="text-xs text-blue-600 hover:underline">
                    {form.permissions.length === ALL_PERMISSIONS.length ? '전체 해제' : '전체 선택'}
                  </button>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                  {ALL_PERMISSIONS.map(p => (
                    <label key={p.key} className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                      <input type="checkbox" checked={form.permissions.includes(p.key)}
                        onChange={() => togglePermission(p.key)}
                        className="rounded" />
                      {p.label}
                    </label>
                  ))}
                </div>
              </div>
            )}

            <div className="flex gap-2">
              <button type="submit" disabled={loading}
                className="px-4 py-2 bg-blue-700 text-white text-sm font-semibold rounded-lg hover:bg-blue-800 disabled:opacity-50">
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

      {/* 관리자 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <span className="text-sm text-gray-500">총 <strong>{admins.length}</strong>명</span>
          <button onClick={() => { resetForm(); setShowForm(true) }}
            className="flex items-center gap-1 px-3 py-1.5 bg-blue-700 text-white text-xs font-semibold rounded-lg hover:bg-blue-800">
            <Plus className="w-3.5 h-3.5" /> 관리자 추가
          </button>
        </div>
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이름</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이메일</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-28">등급</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">권한</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-24">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {admins.map(admin => (
              <tr key={admin.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-gray-800 font-medium">{admin.name}</td>
                <td className="px-4 py-3 text-gray-500 text-xs">{admin.email}</td>
                <td className="px-4 py-3 text-center">
                  {admin.role === 'super' ? (
                    <span className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700">
                      <ShieldCheck className="w-3 h-3" /> 최고 관리자
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium bg-blue-100 text-blue-700">
                      <Shield className="w-3 h-3" /> 일반 관리자
                    </span>
                  )}
                </td>
                <td className="px-4 py-3">
                  {admin.role === 'super' ? (
                    <span className="text-xs text-gray-400">전체 권한</span>
                  ) : admin.permissions.length > 0 ? (
                    <div className="flex flex-wrap gap-1">
                      {admin.permissions.slice(0, 5).map(p => (
                        <span key={p} className="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                          {ALL_PERMISSIONS.find(ap => ap.key === p)?.label ?? p}
                        </span>
                      ))}
                      {admin.permissions.length > 5 && (
                        <span className="text-[10px] text-gray-400">+{admin.permissions.length - 5}</span>
                      )}
                    </div>
                  ) : (
                    <span className="text-xs text-red-400">권한 없음</span>
                  )}
                </td>
                <td className="px-4 py-3 text-center">
                  <div className="flex items-center justify-center gap-2">
                    <button onClick={() => startEdit(admin)} className="text-gray-400 hover:text-blue-600">
                      <Pencil className="w-4 h-4" />
                    </button>
                    {admin.id !== currentAdminId && (
                      <button onClick={() => handleDelete(admin.id, admin.name)} className="text-gray-400 hover:text-red-600">
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
