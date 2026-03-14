'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ChevronLeft, Home, ShoppingCart } from 'lucide-react'
import { useState, useEffect } from 'react'

export default function GoodsListHeader({ title }: { title: string }) {
  const router = useRouter()
  const [cartCount, setCartCount] = useState(0)

  useEffect(() => {
    fetch('/api/v1/cart')
      .then(r => r.json())
      .then(j => setCartCount((j.items ?? j.data ?? []).length))
      .catch(() => {})
  }, [])

  return (
    <header className="sticky top-0 z-50 bg-white" style={{ borderBottom: '1px solid #f0f0f0' }}>
      <div className="flex items-center h-[52px] px-1">
        <button onClick={() => router.back()} className="w-11 h-11 flex items-center justify-center flex-shrink-0">
          <ChevronLeft className="w-6 h-6" style={{ color: '#333' }} />
        </button>
        <h1 className="flex-1 text-center text-[16px] font-bold truncate px-1" style={{ color: '#333' }}>
          {title}
        </h1>
        <div className="flex items-center flex-shrink-0">
          <Link href="/" className="w-10 h-10 flex items-center justify-center">
            <Home className="w-[22px] h-[22px]" style={{ color: '#333' }} />
          </Link>
          <Link href="/cart" className="w-10 h-10 flex items-center justify-center relative">
            <ShoppingCart className="w-[22px] h-[22px]" style={{ color: '#333' }} />
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
