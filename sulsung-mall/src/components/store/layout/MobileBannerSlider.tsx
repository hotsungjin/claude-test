'use client'

import { useState, useEffect, useRef } from 'react'
import Link from 'next/link'

interface Banner {
  id: number
  image_url: string
  mobile_image_url: string | null
  link_url: string | null
  alt: string | null
}

// 배너가 없을 때 표시할 플레이스홀더
const PLACEHOLDER_BANNERS = [
  { id: 1, image_url: '', mobile_image_url: null, link_url: '/goods', alt: '설성목장 신선한 한우' },
  { id: 2, image_url: '', mobile_image_url: null, link_url: '/goods', alt: '오늘도설성 기획전' },
]

export default function MobileBannerSlider({ banners }: { banners: Banner[] }) {
  const list = banners.length > 0 ? banners : PLACEHOLDER_BANNERS
  const [current, setCurrent] = useState(0)
  const timerRef = useRef<NodeJS.Timeout | null>(null)
  const touchStartX = useRef(0)

  const startTimer = () => {
    if (timerRef.current) clearInterval(timerRef.current)
    timerRef.current = setInterval(() => {
      setCurrent(c => (c + 1) % list.length)
    }, 4000)
  }

  useEffect(() => {
    if (list.length > 1) startTimer()
    return () => { if (timerRef.current) clearInterval(timerRef.current) }
  }, [list.length])

  const goTo = (idx: number) => {
    setCurrent(idx)
    startTimer()
  }

  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartX.current = e.touches[0].clientX
  }

  const handleTouchEnd = (e: React.TouchEvent) => {
    const diff = touchStartX.current - e.changedTouches[0].clientX
    if (Math.abs(diff) > 40) {
      if (diff > 0) setCurrent(c => (c + 1) % list.length)
      else setCurrent(c => (c - 1 + list.length) % list.length)
      startTimer()
    }
  }

  return (
    <div
      className="relative overflow-hidden w-full"
      style={{ aspectRatio: '375/330' }}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      {/* 슬라이드 트랙 */}
      <div
        className="flex h-full transition-transform duration-300 ease-in-out"
        style={{ transform: `translateX(-${current * 100}%)` }}
      >
        {list.map((banner, idx) => {
          const imgUrl = banner.mobile_image_url || banner.image_url
          return (
            <Link
              key={banner.id}
              href={banner.link_url ?? '/'}
              className="flex-shrink-0 w-full h-full block"
            >
              {imgUrl ? (
                <img
                  src={imgUrl}
                  alt={banner.alt ?? '배너'}
                  className="w-full h-full object-cover"
                  loading={idx === 0 ? 'eager' : 'lazy'}
                />
              ) : (
                /* 배너 이미지가 없을 때 플레이스홀더 */
                <div
                  className="w-full h-full flex flex-col items-center justify-center text-white"
                  style={{
                    background: idx === 0
                      ? 'linear-gradient(135deg, #968774 0%, #7a6e5f 100%)'
                      : 'linear-gradient(135deg, #7a6e5f 0%, #5c5248 100%)',
                  }}
                >
                  <span className="text-4xl mb-3">{idx === 0 ? '🐄' : '🎁'}</span>
                  <p className="text-lg font-bold">{banner.alt}</p>
                  <p className="text-sm mt-1 opacity-80">설성목장 직배송</p>
                </div>
              )}
            </Link>
          )
        })}
      </div>

      {/* 페이지 인디케이터 */}
      {list.length > 1 && (
        <div className="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5">
          {list.map((_, idx) => (
            <button
              key={idx}
              onClick={() => goTo(idx)}
              className="rounded-full transition-all duration-200"
              style={{
                width: idx === current ? '18px' : '6px',
                height: '6px',
                backgroundColor: idx === current ? '#ffffff' : 'rgba(255,255,255,0.5)',
              }}
            />
          ))}
        </div>
      )}

      {/* 페이지 카운터 (우측 하단) */}
      {list.length > 1 && (
        <div
          className="absolute bottom-3 right-3 text-xs text-white font-medium"
          style={{ background: 'rgba(0,0,0,0.3)', borderRadius: '10px', padding: '2px 8px' }}
        >
          {current + 1} / {list.length}
        </div>
      )}
    </div>
  )
}
