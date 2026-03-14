'use client'

import Link from 'next/link'
import Image from 'next/image'
import { useState, useEffect } from 'react'
import { useRouter, usePathname, useSearchParams } from 'next/navigation'
import { Search, ShoppingCart, User, X } from 'lucide-react'

const NAV_TABS = [
  { label: '추천', href: '/' },
  { label: '베스트', href: '/goods?sort=sale_count' },
  { label: '신상품', href: '/goods?sort=newest' },
  { label: '파격특가', href: '/goods?curation=sale' },
  { label: '오늘도설성', href: '/goods?curation=daily' },
]

export default function Header() {
  const router = useRouter()
  const pathname = usePathname()
  const searchParams = useSearchParams()
  const [searchOpen, setSearchOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')
  const [cartCount, setCartCount] = useState(0)
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 0)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    fetch('/api/v1/cart')
      .then(r => r.json())
      .then(j => setCartCount((j.items ?? j.data ?? []).length))
      .catch(() => {})
  }, [])

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault()
    if (searchQuery.trim()) {
      setSearchOpen(false)
      router.push(`/goods?q=${encodeURIComponent(searchQuery.trim())}`)
      setSearchQuery('')
    }
  }

  const isMypage = pathname.startsWith('/mypage') || pathname.startsWith('/notice') || pathname.startsWith('/faq')

  return (
    <>
      {/* 상단 헤더 — 마이페이지에서는 숨김 */}
      {!isMypage && (
      <header
        data-store-header
        className="sticky top-0 z-50 w-full bg-white"
        style={{ boxShadow: scrolled ? '0 1px 6px rgba(0,0,0,0.08)' : 'none' }}
      >
        <div className="flex items-center justify-between h-[52px]" style={{ paddingLeft: '18px', paddingRight: '12px' }}>
          <Link href="/" className="flex items-center">
            <Image src="/images/logo.png" alt="설성목장" width={90} height={26} priority style={{ width: 90, height: 26 }} />
          </Link>
          <div className="flex items-center">
            <button onClick={() => setSearchOpen(true)} className="w-10 h-10 flex items-center justify-center">
              <Search className="w-[22px] h-[22px]" style={{ color: '#444' }} />
            </button>
            <Link href="/cart" className="w-10 h-10 flex items-center justify-center relative">
              <ShoppingCart className="w-[22px] h-[22px]" style={{ color: '#444' }} />
              {cartCount > 0 && (
                <span className="absolute top-1.5 right-1 w-[18px] h-[18px] flex items-center justify-center rounded-full text-white font-bold"
                  style={{ backgroundColor: '#968774', fontSize: '10px' }}>
                  {cartCount > 9 ? '9+' : cartCount}
                </span>
              )}
            </Link>
            <Link href="/mypage" className="w-10 h-10 flex items-center justify-center">
              <User className="w-[22px] h-[22px]" style={{ color: '#444' }} />
            </Link>
          </div>
        </div>

        {/* 카테고리 탭 네비게이션 — 마이페이지에서는 숨김 */}
        {!pathname.startsWith('/mypage') && (
          <nav className="flex overflow-x-auto scrollbar-hide shadow-[0_1px_6px_rgba(0,0,0,0.06)]" style={{ paddingLeft: '18px', clipPath: 'inset(0 0 -10px 0)' }}>
            {NAV_TABS.map(tab => {
              const sp = searchParams.toString()
              const currentUrl = pathname + (sp ? `?${sp}` : '')
              const isActive = tab.href === '/'
                ? pathname === '/'
                : currentUrl === tab.href
              return (
                <Link
                  key={tab.label}
                  href={tab.href}
                  className="flex-shrink-0 px-4 py-3 text-[16px] whitespace-nowrap relative"
                  style={{
                    color: isActive ? '#968774' : '#333',
                    fontWeight: isActive ? 700 : 400,
                  }}
                >
                  {tab.label}
                  {isActive && (
                    <span
                      className="absolute bottom-0 left-4 right-4 h-[2px]"
                      style={{ backgroundColor: '#968774' }}
                    />
                  )}
                </Link>
              )
            })}
          </nav>
        )}
      </header>
      )}

      {/* 전체화면 검색 오버레이 */}
      {searchOpen && (
        <div className="fixed inset-0 z-[60] bg-white flex flex-col">
          <form onSubmit={handleSearch}
            className="flex items-center gap-3 px-4 border-b"
            style={{ height: '52px', borderColor: '#ebebeb' }}>
            <Search className="w-5 h-5 flex-shrink-0" style={{ color: '#968774' }} />
            <input autoFocus value={searchQuery} onChange={e => setSearchQuery(e.target.value)}
              placeholder="검색어를 입력하세요"
              className="flex-1 text-sm outline-none bg-transparent" style={{ color: '#333' }} />
            {searchQuery && (
              <button type="button" onClick={() => setSearchQuery('')}>
                <X className="w-4 h-4" style={{ color: '#aaa' }} />
              </button>
            )}
            <button type="button" onClick={() => { setSearchOpen(false); setSearchQuery('') }}
              className="text-sm font-medium flex-shrink-0" style={{ color: '#968774' }}>
              취소
            </button>
          </form>
          <div className="flex-1 bg-gray-50 px-4 py-6">
            <p className="text-xs font-semibold mb-3" style={{ color: '#999' }}>추천 검색어</p>
            <div className="flex flex-wrap gap-2">
              {['한우', '한돈', '간편식', '선물세트', '유제품', '베이비'].map(tag => (
                <button key={tag}
                  onClick={() => { setSearchQuery(tag); setSearchOpen(false); router.push(`/goods?q=${tag}`) }}
                  className="px-3 py-1.5 rounded-full border text-sm"
                  style={{ borderColor: '#ddd', color: '#555', background: '#fff' }}>
                  {tag}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </>
  )
}
