'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { X } from 'lucide-react'

export default function PopupModal({ popups }: { popups: any[] }) {
  const [visible, setVisible] = useState<any[]>([])

  useEffect(() => {
    const filtered = popups.filter(p => {
      const hideKey = `popup_hide_${p.id}`
      const hideUntil = localStorage.getItem(hideKey)
      if (hideUntil && Date.now() < Number(hideUntil)) return false
      return true
    })
    setVisible(filtered)
  }, [popups])

  const close = (popupId: string) => {
    setVisible(prev => prev.filter(p => p.id !== popupId))
  }

  const hideForDuration = (popup: any) => {
    const hideKey = `popup_hide_${popup.id}`
    const duration = (popup.hide_duration ?? 24) * 3600000
    localStorage.setItem(hideKey, String(Date.now() + duration))
    close(popup.id)
  }

  if (visible.length === 0) return null
  const popup = visible[0] // 한 번에 하나씩 표시

  const isFullscreen = popup.position === 'fullscreen'
  const isBottom = popup.position === 'bottom'

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50"
      style={isBottom ? { alignItems: 'flex-end' } : {}}>
      <div className={`relative bg-white overflow-hidden ${
        isFullscreen ? 'w-full h-full' :
        isBottom ? 'w-full max-w-md rounded-t-2xl' :
        'w-[90%] max-w-sm rounded-2xl'
      }`}>
        {/* 닫기 버튼 */}
        <button onClick={() => close(popup.id)}
          className="absolute top-3 right-3 z-10 w-8 h-8 bg-black/30 text-white rounded-full flex items-center justify-center">
          <X className="w-4 h-4" />
        </button>

        {/* 이미지 */}
        {popup.link_url ? (
          <Link href={popup.link_url} onClick={() => close(popup.id)}>
            <img
              src={popup.mobile_image_url || popup.image_url}
              alt={popup.name}
              className="w-full"
              style={isFullscreen ? { height: '100vh', objectFit: 'cover' } : {}}
            />
          </Link>
        ) : (
          <img
            src={popup.mobile_image_url || popup.image_url}
            alt={popup.name}
            className="w-full"
            style={isFullscreen ? { height: '100vh', objectFit: 'cover' } : {}}
          />
        )}

        {/* 하단 버튼 */}
        <div className="flex border-t" style={{ borderColor: '#eee' }}>
          <button onClick={() => hideForDuration(popup)}
            className="flex-1 py-3 text-[13px]" style={{ color: '#aaa' }}>
            {popup.hide_duration}시간 동안 보지 않기
          </button>
          <div className="w-px" style={{ backgroundColor: '#eee' }} />
          <button onClick={() => close(popup.id)}
            className="flex-1 py-3 text-[13px] font-medium" style={{ color: '#333' }}>
            닫기
          </button>
        </div>
      </div>
    </div>
  )
}
