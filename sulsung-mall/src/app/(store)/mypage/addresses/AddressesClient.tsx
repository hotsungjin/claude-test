'use client'

import { useState } from 'react'
import { Plus, Pencil, Trash2, Star } from 'lucide-react'

interface Address {
  id: string; name: string; phone: string; zipcode: string
  address1: string; address2: string; is_default: boolean; label: string
}

export default function AddressesClient({ memberId, initialAddresses }: {
  memberId: string; initialAddresses: Address[]
}) {
  const [addresses, setAddresses] = useState<Address[]>(initialAddresses)
  const [showForm, setShowForm] = useState(false)
  const [editTarget, setEditTarget] = useState<Address | null>(null)
  const [form, setForm] = useState({ name: '', phone: '', zipcode: '', address1: '', address2: '', label: '', is_default: false })
  const [loading, setLoading] = useState(false)

  const openNew = () => {
    setForm({ name: '', phone: '', zipcode: '', address1: '', address2: '', label: '', is_default: addresses.length === 0 })
    setEditTarget(null)
    setShowForm(true)
  }

  const openEdit = (addr: Address) => {
    setForm({ name: addr.name, phone: addr.phone, zipcode: addr.zipcode, address1: addr.address1, address2: addr.address2 ?? '', label: addr.label ?? '', is_default: addr.is_default })
    setEditTarget(addr)
    setShowForm(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    const url = editTarget ? `/api/v1/addresses/${editTarget.id}` : '/api/v1/addresses'
    const method = editTarget ? 'PATCH' : 'POST'
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...form, member_id: memberId }),
    })
    if (res.ok) {
      const json = await res.json()
      if (editTarget) {
        setAddresses(prev => prev.map(a => a.id === editTarget.id ? json.data : a))
      } else {
        setAddresses(prev => [...prev, json.data])
      }
      setShowForm(false)
    }
    setLoading(false)
  }

  const handleDelete = async (id: string) => {
    if (!confirm('배송지를 삭제하시겠습니까?')) return
    const res = await fetch(`/api/v1/addresses/${id}`, { method: 'DELETE' })
    if (res.ok) setAddresses(prev => prev.filter(a => a.id !== id))
  }

  const setDefault = async (id: string) => {
    await fetch(`/api/v1/addresses/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ is_default: true }),
    })
    setAddresses(prev => prev.map(a => ({ ...a, is_default: a.id === id })))
  }

  return (
    <div className="px-4">
      <div className="flex justify-end mb-4">
        <button onClick={openNew}
          className="flex items-center gap-2 text-white text-sm px-4 py-2 rounded-xl hover:opacity-90 transition-opacity"
          style={{ backgroundColor: '#968774' }}>
          <Plus className="w-4 h-4" /> 새 배송지 추가
        </button>
      </div>

      {/* 배송지 목록 */}
      <div className="space-y-3">
        {addresses.map(addr => (
          <div key={addr.id} className={`bg-white rounded-xl p-5 shadow-sm border ${addr.is_default ? '' : 'border-gray-100'}`}
            style={addr.is_default ? { borderColor: '#c4b9a7' } : undefined}>
            <div className="flex items-start justify-between">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  {addr.is_default && <Star className="w-4 h-4" style={{ color: '#968774', fill: '#968774' }} />}
                  <span className="font-semibold text-gray-900">{addr.name}</span>
                  {addr.label && <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{addr.label}</span>}
                </div>
                <p className="text-sm text-gray-600">{addr.phone}</p>
                <p className="text-sm text-gray-700 mt-1">[{addr.zipcode}] {addr.address1} {addr.address2}</p>
              </div>
              <div className="flex gap-2 flex-shrink-0 ml-4">
                {!addr.is_default && (
                  <button onClick={() => setDefault(addr.id)}
                    className="text-xs px-2 py-1 rounded-lg border hover:opacity-80 transition-opacity"
                    style={{ color: '#968774', borderColor: '#c4b9a7' }}>
                    기본 설정
                  </button>
                )}
                <button onClick={() => openEdit(addr)} className="text-gray-400 hover:text-gray-700">
                  <Pencil className="w-4 h-4" />
                </button>
                <button onClick={() => handleDelete(addr.id)} className="text-gray-400 hover:text-red-500">
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        ))}
        {addresses.length === 0 && (
          <div className="bg-white rounded-xl p-12 text-center shadow-sm">
            <p className="text-gray-400">등록된 배송지가 없습니다.</p>
          </div>
        )}
      </div>

      {/* 폼 모달 */}
      {showForm && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
          <div className="bg-white rounded-2xl w-full max-w-md p-6">
            <h3 className="font-bold text-gray-900 mb-5">{editTarget ? '배송지 수정' : '새 배송지'}</h3>
            <form onSubmit={handleSubmit} className="space-y-3">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-600 mb-1">수령인 *</label>
                  <input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} required
                    className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
                    onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
                    onBlur={e => (e.currentTarget.style.borderColor = '')} />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-600 mb-1">연락처 *</label>
                  <input value={form.phone} onChange={e => setForm(f => ({ ...f, phone: e.target.value }))} required
                    className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
                    onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
                    onBlur={e => (e.currentTarget.style.borderColor = '')} />
                </div>
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">우편번호 *</label>
                <input value={form.zipcode} onChange={e => setForm(f => ({ ...f, zipcode: e.target.value }))} required
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
                  onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
                  onBlur={e => (e.currentTarget.style.borderColor = '')} />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">주소 *</label>
                <input value={form.address1} onChange={e => setForm(f => ({ ...f, address1: e.target.value }))} required
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
                  onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
                  onBlur={e => (e.currentTarget.style.borderColor = '')} />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">상세주소</label>
                <input value={form.address2} onChange={e => setForm(f => ({ ...f, address2: e.target.value }))}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
                  onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
                  onBlur={e => (e.currentTarget.style.borderColor = '')} />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">별칭 (예: 집, 회사)</label>
                <input value={form.label} onChange={e => setForm(f => ({ ...f, label: e.target.value }))}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
                  onFocus={e => (e.currentTarget.style.borderColor = '#968774')}
                  onBlur={e => (e.currentTarget.style.borderColor = '')} />
              </div>
              <label className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" checked={form.is_default} onChange={e => setForm(f => ({ ...f, is_default: e.target.checked }))}
                  className="w-4 h-4" style={{ accentColor: '#968774' }} />
                <span className="text-sm text-gray-700">기본 배송지로 설정</span>
              </label>
              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowForm(false)}
                  className="flex-1 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-xl hover:bg-gray-50">취소</button>
                <button type="submit" disabled={loading}
                  className="flex-1 py-2.5 text-white text-sm font-semibold rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                  style={{ backgroundColor: '#968774' }}>
                  {loading ? '저장 중...' : '저장'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
