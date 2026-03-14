'use client'

import { useState, useEffect, useRef } from 'react'
import { usePathname } from 'next/navigation'
import Link from 'next/link'
import { X } from 'lucide-react'

export default function PopupModal({ popups }: { popups: any[] }) {
  const pathname = usePathname()
  const [visible, setVisible] = useState<any[]>([])
  const [animating, setAnimating] = useState(false)
  const wrapperRef = useRef<HTMLDivElement>(null)
  const [rect, setRect] = useState({ left: 0, width: 480 })

  // 컨텐츠 영역 위치 계산
  useEffect(() => {
    function measure() {
      const el = wrapperRef.current?.closest('.app-scroll-wrapper') as HTMLElement
      if (el) {
        const r = el.getBoundingClientRect()
        setRect({ left: r.left, width: r.width })
      }
    }
    measure()
    window.addEventListener('resize', measure)
    return () => window.removeEventListener('resize', measure)
  }, [])

  useEffect(() => {
    const filtered = popups.filter(p => {
      const hideKey = `popup_hide_${p.id}`
      const hideUntil = localStorage.getItem(hideKey)
      if (hideUntil && Date.now() < Number(hideUntil)) return false

      const targets: string[] = p.target_pages ?? ['/']
      if (targets.includes('all')) return true
      return targets.some((t: string) => {
        if (t === '/') return pathname === '/'
        return pathname.startsWith(t)
      })
    })
    if (filtered.length > 0) {
      setVisible(filtered)
      requestAnimationFrame(() => {
        requestAnimationFrame(() => setAnimating(true))
      })
    }
  }, [popups, pathname])

  const close = (popupId: string) => {
    setAnimating(false)
    setTimeout(() => {
      setVisible(prev => {
        const next = prev.filter(p => p.id !== popupId)
        if (next.length > 0) {
          requestAnimationFrame(() => {
            requestAnimationFrame(() => setAnimating(true))
          })
        }
        return next
      })
    }, 300)
  }

  const hideForDuration = (popup: any) => {
    const hideKey = `popup_hide_${popup.id}`
    const duration = (popup.hide_duration ?? 24) * 3600000
    localStorage.setItem(hideKey, String(Date.now() + duration))
    close(popup.id)
  }

  if (visible.length === 0) return <div ref={wrapperRef} />
  const popup = visible[0]

  const imageEl = (
    <img
      src={popup.image_url}
      alt={popup.name}
      className="w-full"
      style={{ maxHeight: '60vh', objectFit: 'cover' }}
    />
  )

  return (
    <div ref={wrapperRef}>
      {/* 오버레이 - 컨텐츠 영역에 맞춤 */}
      <div
        className="fixed inset-0 z-[100] bg-black/50 transition-opacity duration-300"
        style={{ opacity: animating ? 1 : 0 }}
        onClick={() => close(popup.id)}
      />

      {/* 바텀시트 - 컨텐츠 영역에 맞춤 */}
      <div
        className="fixed bottom-0 z-[101] transition-transform duration-300 ease-out"
        style={{
          left: rect.left,
          width: rect.width,
          transform: animating ? 'translateY(0)' : 'translateY(100%)',
        }}
      >
        <div className="bg-white rounded-t-2xl overflow-hidden shadow-2xl">
          {/* 닫기 버튼 */}
          <button
            onClick={() => close(popup.id)}
            className="absolute top-3 right-3 z-10 w-8 h-8 bg-black/40 text-white rounded-full flex items-center justify-center"
          >
            <X className="w-4 h-4" />
          </button>

          {/* 이미지 */}
          {popup.image_url && (
            popup.link_url ? (
              <Link href={popup.link_url} onClick={() => close(popup.id)}>
                {imageEl}
              </Link>
            ) : imageEl
          )}

          {/* 하단 버튼 */}
          <div className="flex border-t" style={{ borderColor: '#eee' }}>
            <button
              onClick={() => hideForDuration(popup)}
              className="flex-1 py-4 text-[14px]"
              style={{ color: '#999' }}
            >
              {popup.hide_duration ?? 24}시간 동안 보지 않기
            </button>
            <div className="w-px" style={{ backgroundColor: '#eee' }} />
            <button
              onClick={() => close(popup.id)}
              className="flex-1 py-4 text-[14px] font-semibold"
              style={{ color: '#333' }}
            >
              닫기
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
