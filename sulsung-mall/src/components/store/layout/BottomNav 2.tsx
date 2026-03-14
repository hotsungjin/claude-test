'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { Home, Grid3X3, Search, ShoppingCart, User } from 'lucide-react'

const NAV_ITEMS = [
  { href: '/',          icon: Home,       label: '홈' },
  { href: '/goods',     icon: Grid3X3,    label: '카테고리' },
  { href: '/goods?q=',  icon: Search,     label: '검색' },
  { href: '/cart',      icon: ShoppingCart, label: '장바구니' },
  { href: '/mypage',    icon: User,       label: '마이' },
]

export default function BottomNav() {
  const pathname = usePathname()

  const isActive = (href: string) => {
    if (href === '/') return pathname === '/'
    return pathname.startsWith(href.split('?')[0])
  }

  return (
    <nav
      data-bottom-nav
      className="sticky bottom-0 z-50 bg-white flex justify-evenly shadow-[0_-1px_6px_rgba(0,0,0,0.06)]"
      style={{ borderTop: '1px solid #ebebeb', height: '52px' }}
    >
      {NAV_ITEMS.map(item => {
        const Icon = item.icon
        const active = isActive(item.href)
        return (
          <Link
            key={item.href}
            href={item.href}
            className="flex-1 flex flex-col items-center justify-center gap-0.5"
          >
            <Icon
              className="w-5 h-5"
              style={{
                color: active ? '#968774' : '#aaa',
                fill: active && item.href === '/' ? '#968774' : 'none',
              }}
            />
            <span
              className="text-[10px] font-medium"
              style={{ color: active ? '#968774' : '#aaa' }}
            >
              {item.label}
            </span>
          </Link>
        )
      })}
    </nav>
  )
}
