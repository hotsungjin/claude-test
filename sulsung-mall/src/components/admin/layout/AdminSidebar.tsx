'use client'

import { useState, useEffect, useCallback } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  LayoutDashboard, Package, ShoppingBag, Users, Tag, FolderTree, Layers, Bookmark,
  BarChart2, Image, MessageSquare, Settings, Star, Bell, Clock, Megaphone, ShoppingCart, Mail,
  HelpCircle, MessageCircle, ShieldCheck, ClipboardList, FileText, UserPlus, Crown, ChevronDown,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { SITE_NAME } from '@/constants'

type MenuItem = {
  href: string
  icon: typeof LayoutDashboard
  label: string
  permission: string | null
}

type MenuGroup = {
  key: string
  label: string
  items: MenuItem[]
}

const MENU_GROUPS: MenuGroup[] = [
  {
    key: 'dashboard',
    label: '',
    items: [
      { href: '/admin/dashboard', icon: LayoutDashboard, label: '대시보드', permission: null },
      { href: '/admin/stats',     icon: BarChart2,       label: '통계',     permission: 'stats' },
    ],
  },
  {
    key: 'orders',
    label: '주문/발주',
    items: [
      { href: '/admin/orders',     icon: ShoppingBag,   label: '주문 관리',     permission: 'orders' },
      { href: '/admin/purchases',  icon: ClipboardList, label: 'AI 발주관리',   permission: 'goods' },
    ],
  },
  {
    key: 'goods',
    label: '상품',
    items: [
      { href: '/admin/goods',       icon: Package,       label: '상품 관리',     permission: 'goods' },
      { href: '/admin/categories',  icon: FolderTree,    label: '카테고리 관리', permission: 'categories' },
      { href: '/admin/brands',      icon: Bookmark,      label: '브랜드 관리',   permission: 'goods' },
      { href: '/admin/curations',   icon: Layers,        label: '기획전 관리', permission: 'goods' },
    ],
  },
  {
    key: 'members',
    label: '회원',
    items: [
      { href: '/admin/members',           icon: Users,        label: '회원 관리',     permission: 'members' },
      { href: '/admin/membership',         icon: Crown,        label: '멤버십 관리',   permission: 'members' },
      { href: '/admin/referral',           icon: UserPlus,     label: '친구추천 관리', permission: 'members' },
    ],
  },
  {
    key: 'marketing',
    label: '마케팅',
    items: [
      { href: '/admin/coupons',            icon: Tag,          label: '쿠폰 관리',     permission: 'coupons' },
      { href: '/admin/cart-abandonment',   icon: ShoppingCart,  label: '이탈 장바구니', permission: 'orders' },
      { href: '/admin/time-sales',         icon: Clock,        label: '타임세일',      permission: 'time_sales' },
      { href: '/admin/email',          icon: Mail,  label: '이메일 발송',   permission: 'email' },
      { href: '/admin/notifications', icon: Bell,  label: '메시지/알림',    permission: 'notifications' },
    ],
  },
  {
    key: 'cs',
    label: '고객 소통',
    items: [
      { href: '/admin/reviews',   icon: Star,           label: '리뷰 관리',     permission: 'reviews' },
      { href: '/admin/faq',       icon: HelpCircle,     label: '자주 묻는 질문', permission: 'faq' },
      { href: '/admin/inquiries', icon: MessageCircle,  label: '1:1 문의',      permission: 'inquiries' },
      { href: '/admin/notices',   icon: MessageSquare,  label: '공지사항',      permission: 'notices' },
    ],
  },
  {
    key: 'content',
    label: '콘텐츠',
    items: [
      { href: '/admin/banners', icon: Image,     label: '배너 관리',   permission: 'banners' },
      { href: '/admin/popups',  icon: Megaphone, label: '팝업 관리',   permission: 'popups' },
    ],
  },
  {
    key: 'settings',
    label: '설정',
    items: [
      { href: '/admin/terms',         icon: FileText,    label: '약관 관리',   permission: 'settings' },
      { href: '/admin/settings',      icon: Settings,    label: '쇼핑몰 설정', permission: 'settings' },
      { href: '/admin/admins',        icon: ShieldCheck, label: '관리자 관리', permission: 'admins' },
    ],
  },
]

const STORAGE_KEY = 'admin-sidebar-groups'

interface AdminSidebarProps {
  role?: 'super' | 'admin'
  permissions?: string[]
}

export default function AdminSidebar({ role = 'super', permissions = [] }: AdminSidebarProps) {
  const pathname = usePathname()

  const isActive = useCallback((href: string) => pathname === href || pathname.startsWith(href + '/'), [pathname])

  // 현재 경로가 속한 그룹 찾기
  const findActiveGroup = useCallback(() => {
    for (const group of MENU_GROUPS) {
      if (group.items.some(item => isActive(item.href))) {
        return group.key
      }
    }
    return 'dashboard'
  }, [isActive])

  const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(() => {
    // SSR 안전: 초기값은 모든 그룹 열림
    const initial: Record<string, boolean> = {}
    MENU_GROUPS.forEach(g => { initial[g.key] = true })
    return initial
  })

  // 클라이언트 마운트 시 localStorage에서 복원 + 현재 경로 그룹 열기
  useEffect(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY)
      if (saved) {
        const parsed = JSON.parse(saved) as Record<string, boolean>
        // 현재 경로 그룹은 반드시 열기
        parsed[findActiveGroup()] = true
        setOpenGroups(prev => ({ ...prev, ...parsed, [findActiveGroup()]: true }))
      }
    } catch { /* ignore */ }
  }, [findActiveGroup])

  // 경로 변경 시 해당 그룹 자동 열기
  useEffect(() => {
    const activeKey = findActiveGroup()
    setOpenGroups(prev => {
      if (prev[activeKey]) return prev
      const next = { ...prev, [activeKey]: true }
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(next)) } catch { /* ignore */ }
      return next
    })
  }, [pathname, findActiveGroup])

  const toggleGroup = (key: string) => {
    setOpenGroups(prev => {
      const next = { ...prev, [key]: !prev[key] }
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(next)) } catch { /* ignore */ }
      return next
    })
  }

  const isItemVisible = (item: MenuItem) => {
    if (!item.permission) return true
    if (role === 'super') return true
    if (item.permission === 'admins') return false
    return permissions.includes(item.permission)
  }

  return (
    <aside className="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">
      <div className="px-6 py-5 border-b border-gray-700">
        <Link href="/admin/dashboard" className="text-lg font-bold text-gray-100">
          {SITE_NAME}
        </Link>
        <p className="text-xs text-gray-400 mt-0.5">
          {role === 'super' ? '최고 관리자' : '관리자'}
        </p>
      </div>

      <nav className="flex-1 py-2 px-3 overflow-y-auto">
        {MENU_GROUPS.map(group => {
          const visibleItems = group.items.filter(isItemVisible)
          if (visibleItems.length === 0) return null

          // 대시보드는 그룹 헤더 없이 단독 표시
          if (!group.label) {
            return visibleItems.map(({ href, icon: Icon, label }) => (
              <Link
                key={href}
                href={href}
                className={cn(
                  'flex items-center gap-3 px-3 py-[5px] text-[15px] rounded-lg transition-colors',
                  isActive(href)
                    ? 'bg-gray-700/80 text-white font-medium'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-gray-100'
                )}
              >
                <Icon className="w-4 h-4" />
                {label}
              </Link>
            ))
          }

          const isOpen = openGroups[group.key] ?? true

          return (
            <div key={group.key} className="mt-[3px]">
              <button
                onClick={() => toggleGroup(group.key)}
                className="w-full flex items-center justify-between px-3 py-[5px] text-[11px] font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-300 transition-colors"
              >
                {group.label}
                <ChevronDown
                  className={cn(
                    'w-3.5 h-3.5 transition-transform duration-200',
                    isOpen ? 'rotate-0' : '-rotate-90'
                  )}
                />
              </button>
              <div
                className={cn(
                  'overflow-hidden transition-all duration-200 ease-in-out',
                  isOpen ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'
                )}
              >
                <div className="space-y-0.5 pb-1">
                  {visibleItems.map(({ href, icon: Icon, label }) => (
                    <Link
                      key={href}
                      href={href}
                      className={cn(
                        'flex items-center gap-3 px-3 py-[5px] text-[14px] rounded-lg transition-colors',
                        isActive(href)
                          ? 'bg-gray-700/80 text-white font-medium'
                          : 'text-gray-300 hover:bg-gray-800 hover:text-gray-100'
                      )}
                    >
                      <Icon className="w-4 h-4" />
                      {label}
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          )
        })}
      </nav>

      <div className="px-6 py-4 border-t border-gray-700">
        <Link href="/" target="_blank" className="text-xs text-gray-400 hover:text-white">
          ← 쇼핑몰 보기
        </Link>
      </div>
    </aside>
  )
}
