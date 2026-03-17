'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ChevronLeft, Home, ShoppingCart } from 'lucide-react'
import { useState, useEffect } from 'react'

export default function MypageHeader({ title, backHref }: { title: string; backHref?: string }) {
  const router = useRouter()
  const [cartCount, setCartCount] = useState(0)

  useEffect(() => {
    fetch('/api/v1/cart')
      .then(r => r.json())
      .then(j => setCartCount((j.items ?? j.data ?? []).length))
      .catch(() => {})
  }, [])

  const handleBack = () => {
    if (backHref) {
      router.push(backHref)
    } else if (window.history.length > 1) {
      router.back()
    } else {
      router.push('/mypage')
    }
  }

  return (
    <header className="sticky top-0 z-40 bg-white">
      <div className="flex items-center h-[52px] px-1">
        <button type="button" onClick={handleBack} className="w-11 h-11 flex items-center justify-center flex-shrink-0">
          <ChevronLeft className="w-6 h-6" style={{ color: '#333' }} />
        </button>
        <h1 className="flex-1 text-[20px] font-bold truncate px-1" style={{ color: '#333' }}>
          {title}
        </h1>
        <div className="flex items-center flex-shrink-0">
          <Link href="/" className="w-10 h-10 flex items-center justify-center">
            <Home className="w-[24px] h-[24px]" strokeWidth={2.2} style={{ color: '#222' }} />
          </Link>
          <Link href="/cart" className="w-10 h-10 flex items-center justify-center relative">
            <ShoppingCart className="w-[24px] h-[24px]" strokeWidth={2.2} style={{ color: '#222' }} />
            {cartCount > 0 && (
              <span className="absolute top-1.5 right-1 w-[18px] h-[18px] flex items-center justify-center rounded-full text-white font-bold"
                style={{ backgroundColor: '#968774', fontSize: '10px' }}>
                {cartCount > 9 ? '9+' : cartCount}
              </span>
            )}
          </Link>
        </div>
      </div>
    </header>
  )
}
