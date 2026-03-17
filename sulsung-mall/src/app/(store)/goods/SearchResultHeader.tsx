'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Search, ShoppingCart, X, ChevronLeft } from 'lucide-react'

interface Props {
  initialQuery: string
}

export default function SearchResultHeader({ initialQuery }: Props) {
  const router = useRouter()
  const [query, setQuery] = useState(initialQuery)
  const [cartCount, setCartCount] = useState(0)

  useEffect(() => {
    fetch('/api/v1/cart')
      .then(r => r.json())
      .then(j => setCartCount((j.items ?? j.data ?? []).length))
      .catch(() => {})
  }, [])

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault()
    if (query.trim()) {
      router.push(`/goods?q=${encodeURIComponent(query.trim())}`)
      fetch('/api/v1/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ keyword: query.trim() }),
      }).catch(() => {})
    }
  }

  return (
    <header className="sticky top-0 z-50 bg-white" style={{ boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
      <form onSubmit={handleSearch} className="flex items-center gap-2 px-3" style={{ height: '52px' }}>
        {/* 뒤로가기 */}
        <button type="button" onClick={() => router.back()}
          className="flex-shrink-0 w-9 h-9 flex items-center justify-center">
          <ChevronLeft className="w-6 h-6" style={{ color: '#333' }} />
        </button>

        {/* 검색 입력 */}
        <div className="flex-1 flex items-center gap-2 h-[40px] rounded-full px-3.5"
          style={{ backgroundColor: '#f5f5f5' }}>
          <Search className="w-[18px] h-[18px] flex-shrink-0" style={{ color: '#aaa' }} />
          <input
            value={query}
            onChange={e => setQuery(e.target.value)}
            placeholder="검색어를 입력해 주세요"
            className="flex-1 text-[15px] outline-none bg-transparent"
            style={{ color: '#333' }}
          />
          {query && (
            <button type="button" onClick={() => setQuery('')}
              className="w-[18px] h-[18px] rounded-full flex items-center justify-center flex-shrink-0"
              style={{ backgroundColor: '#ccc' }}>
              <X className="w-3 h-3" style={{ color: '#fff' }} />
            </button>
          )}
        </div>

        {/* 장바구니 */}
        <button type="button" onClick={() => router.push('/cart')}
          className="flex-shrink-0 w-9 h-9 flex items-center justify-center relative">
          <ShoppingCart className="w-[22px] h-[22px]" strokeWidth={2.2} style={{ color: '#222' }} />
          {cartCount > 0 && (
            <span className="absolute top-0.5 right-0 w-[18px] h-[18px] flex items-center justify-center rounded-full text-white font-bold"
              style={{ backgroundColor: '#968774', fontSize: '10px' }}>
              {cartCount > 9 ? '9+' : cartCount}
            </span>
          )}
        </button>
      </form>
    </header>
  )
}
