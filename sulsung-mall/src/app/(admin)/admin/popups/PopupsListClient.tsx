'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { formatDateTime } from '@/utils/format'

const PAGE_LABELS: Record<string, string> = {
  '/': '홈',
  '/goods': '상품목록',
  '/categories': '카테고리',
  '/auth/signup': '회원가입',
  '/mypage': '마이페이지',
  'all': '전체',
}

export default function PopupsListClient({ items: initialItems }: { items: any[] }) {
  const router = useRouter()
  const [items, setItems] = useState(initialItems)
  const [togglingId, setTogglingId] = useState<number | null>(null)
  const now = new Date()

  const toggleActive = async (id: number, currentActive: boolean) => {
    setTogglingId(id)
    // Optimistic update
    setItems(prev => prev.map(item =>
      item.id === id ? { ...item, is_active: !currentActive } : item
    ))

    const res = await fetch('/api/v1/admin/popups', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, is_active: !currentActive }),
    })

    if (!res.ok) {
      // Revert on failure
      setItems(prev => prev.map(i =>
        i.id === id ? { ...i, is_active: currentActive } : i
      ))
    } else {
      router.refresh()
    }
    setTogglingId(null)
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">팝업 관리</h1>
        <Link href="/admin/popups/new"
          className="px-4 py-2 bg-blue-700 text-white text-sm font-semibold rounded-xl hover:bg-blue-800">
          팝업 등록
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {items.map((item: any) => {
          const isLive = item.is_active && (!item.starts_at || new Date(item.starts_at) <= now) && (!item.ends_at || new Date(item.ends_at) >= now)
          const pages = (item.target_pages ?? ['/']).map((p: string) => PAGE_LABELS[p] ?? p).join(', ')
          return (
            <div key={item.id} className="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
              <Link href={`/admin/popups/${item.id}`}>
                <div className="aspect-video bg-gray-100 relative">
                  {item.image_url && <img src={item.image_url} alt={item.name} className="w-full h-full object-cover" />}
                  <span className={`absolute top-2 right-2 text-xs px-2 py-1 rounded-full font-medium ${
                    isLive ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {isLive ? '노출중' : '비노출'}
                  </span>
                </div>
              </Link>
              <div className="p-4">
                <Link href={`/admin/popups/${item.id}`}>
                  <p className="font-medium text-gray-900 text-sm">{item.name}</p>
                  <p className="text-xs text-gray-400 mt-1">
                    {pages}
                    {item.starts_at && ` · ${formatDateTime(item.starts_at)} ~`}
                  </p>
                </Link>
                <div className="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                  <span className={`text-xs font-medium ${item.is_active ? 'text-blue-600' : 'text-gray-400'}`}>
                    {item.is_active ? '노출' : '비노출'}
                  </span>
                  <button
                    type="button"
                    onClick={() => toggleActive(item.id, item.is_active)}
                    disabled={togglingId === item.id}
                    className={`relative w-11 h-6 rounded-full transition-colors duration-200 ${
                      item.is_active ? 'bg-blue-600' : 'bg-gray-300'
                    } ${togglingId === item.id ? 'opacity-50' : ''}`}
                  >
                    <span className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 ${
                      item.is_active ? 'translate-x-5' : 'translate-x-0'
                    }`} />
                  </button>
                </div>
              </div>
            </div>
          )
        })}
        {items.length === 0 && (
          <div className="col-span-3 text-center py-20 text-gray-400 bg-white rounded-xl">등록된 팝업이 없습니다</div>
        )}
      </div>
    </div>
  )
}
