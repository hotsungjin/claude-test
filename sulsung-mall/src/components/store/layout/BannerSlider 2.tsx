'use client'

import { useState, useEffect } from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { ChevronLeft, ChevronRight } from 'lucide-react'

interface Banner {
  id: number
  image_url: string
  mobile_image_url: string | null
  link_url: string | null
  alt: string | null
}

export default function BannerSlider({ banners }: { banners: Banner[] }) {
  const [current, setCurrent] = useState(0)

  useEffect(() => {
    if (banners.length <= 1) return
    const timer = setInterval(() => {
      setCurrent((c) => (c + 1) % banners.length)
    }, 5000)
    return () => clearInterval(timer)
  }, [banners.length])

  if (banners.length === 0) {
    return (
      <div className="w-full text-white flex items-center justify-center h-64 md:h-96" style={{ background: 'linear-gradient(to right, #968774, #a89882)' }}>
        <div className="text-center">
          <h1 className="text-3xl md:text-5xl font-bold mb-4">설성목장몰</h1>
          <p className="text-lg md:text-xl opacity-90">신선한 목장 직배송</p>
          <Link href="/goods" className="inline-block mt-6 bg-white px-6 py-2 rounded-full font-semibold hover:opacity-90 transition-opacity"
            style={{ color: '#968774' }}>
            쇼핑하기
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="relative w-full overflow-hidden bg-gray-100 h-64 md:h-[480px]">
      {banners.map((banner, idx) => (
        <div
          key={banner.id}
          className={`absolute inset-0 transition-opacity duration-700 ${idx === current ? 'opacity-100' : 'opacity-0'}`}
        >
          {banner.link_url ? (
            <Link href={banner.link_url}>
              <Image src={banner.image_url} alt={banner.alt ?? ''} fill className="object-cover" priority={idx === 0} />
            </Link>
          ) : (
            <Image src={banner.image_url} alt={banner.alt ?? ''} fill className="object-cover" priority={idx === 0} />
          )}
        </div>
      ))}

      {banners.length > 1 && (
        <>
          <button onClick={() => setCurrent((c) => (c - 1 + banners.length) % banners.length)}
            className="absolute left-4 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white rounded-full p-2 shadow">
            <ChevronLeft className="w-5 h-5" />
          </button>
          <button onClick={() => setCurrent((c) => (c + 1) % banners.length)}
            className="absolute right-4 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white rounded-full p-2 shadow">
            <ChevronRight className="w-5 h-5" />
          </button>
          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
            {banners.map((_, idx) => (
              <button key={idx} onClick={() => setCurrent(idx)}
                className={`w-2 h-2 rounded-full transition-colors ${idx === current ? 'bg-white' : 'bg-white/40'}`} />
            ))}
          </div>
        </>
      )}
    </div>
  )
}
