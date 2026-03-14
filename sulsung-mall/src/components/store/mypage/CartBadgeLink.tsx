'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { ShoppingCart } from 'lucide-react'

export default function CartBadgeLink() {
  const [count, setCount] = useState(0)

  useEffect(() => {
    fetch('/api/v1/cart')
      .then(r => r.json())
      .then(j => setCount((j.items ?? j.data ?? []).length))
      .catch(() => {})
  }, [])

  return (
    <Link href="/cart" className="w-10 h-10 flex items-center justify-center relative">
      <ShoppingCart className="w-[24px] h-[24px]" strokeWidth={2.2} style={{ color: '#222' }} />
      {count > 0 && (
        <span
          className="absolute top-1.5 right-1 w-[18px] h-[18px] flex items-center justify-center rounded-full text-white font-bold"
          style={{ backgroundColor: '#968774', fontSize: '10px' }}
        >
          {count > 9 ? '9+' : count}
        </span>
      )}
    </Link>
  )
}
