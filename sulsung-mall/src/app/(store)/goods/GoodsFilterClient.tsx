'use client'

import { useState, useEffect } from 'react'
import { createPortal } from 'react-dom'
import { useRouter } from 'next/navigation'
import { SlidersHorizontal, X } from 'lucide-react'

interface Props {
  currentMinPrice: string
  currentMaxPrice: string
  currentTag: string
  currentParams: Record<string, string | undefined>
  activeFilters: number
}

const PRICE_PRESETS = [
  { label: '1만원 이하', min: '', max: '10000' },
  { label: '1~3만원', min: '10000', max: '30000' },
  { label: '3~5만원', min: '30000', max: '50000' },
  { label: '5만원 이상', min: '50000', max: '' },
]

const TAG_PRESETS = ['한우', '한돈', '유제품', '간편식', '선물세트', '신선식품', '베이비']

export default function GoodsFilterClient({ currentMinPrice, currentMaxPrice, currentTag, currentParams, activeFilters }: Props) {
  const router = useRouter()
  const [open, setOpen] = useState(false)
  const [minPrice, setMinPrice] = useState(currentMinPrice)
  const [maxPrice, setMaxPrice] = useState(currentMaxPrice)
  const [tag, setTag] = useState(currentTag)
  const [portalTarget, setPortalTarget] = useState<HTMLElement | null>(null)

  useEffect(() => {
    const wrapper = document.querySelector('.app-scroll-wrapper')
    setPortalTarget(wrapper as HTMLElement ?? document.body)
  }, [])

  const applyFilter = () => {
    const qs = new URLSearchParams()
    if (currentParams.category) qs.set('category', currentParams.category)
    if (currentParams.sort) qs.set('sort', currentParams.sort)
    if (currentParams.q) qs.set('q', currentParams.q)
    if (minPrice) qs.set('min_price', minPrice)
    if (maxPrice) qs.set('max_price', maxPrice)
    if (tag) qs.set('tag', tag)
    if (currentParams.title) qs.set('title', currentParams.title)
    router.push(`/goods?${qs.toString()}`)
    setOpen(false)
  }

  const resetFilter = () => {
    setMinPrice('')
    setMaxPrice('')
    setTag('')
    const qs = new URLSearchParams()
    if (currentParams.category) qs.set('category', currentParams.category)
    if (currentParams.sort) qs.set('sort', currentParams.sort)
    if (currentParams.q) qs.set('q', currentParams.q)
    if (currentParams.title) qs.set('title', currentParams.title)
    router.push(`/goods?${qs.toString()}`)
    setOpen(false)
  }

  const selectPreset = (min: string, max: string) => {
    setMinPrice(min)
    setMaxPrice(max)
  }

  const sheet = open ? (
    <div
      className="fixed inset-0 z-[100]"
      style={{ position: 'sticky', top: 0, height: '100vh', marginTop: '-100vh' }}
      onClick={() => setOpen(false)}
    >
      <div style={{ position: 'absolute', inset: 0, backgroundColor: 'rgba(0,0,0,0.4)' }} />
      <div
        style={{
          position: 'absolute',
          bottom: 0,
          left: 0,
          right: 0,
          backgroundColor: 'white',
          borderRadius: '16px 16px 0 0',
          animation: 'cart-slide-up 0.3s ease-out',
        }}
        onClick={e => e.stopPropagation()}
      >
        {/* 헤더 */}
        <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: '#ebebeb' }}>
          <h3 className="text-[16px] font-bold" style={{ color: '#333' }}>상세 필터</h3>
          <button onClick={() => setOpen(false)}><X className="w-5 h-5" style={{ color: '#999' }} /></button>
        </div>

        <div className="px-5 py-5 space-y-6 max-h-[60vh] overflow-y-auto">
          {/* 가격대 */}
          <div>
            <p className="text-[13px] font-semibold mb-3" style={{ color: '#333' }}>가격대</p>
            <div className="flex flex-wrap gap-2 mb-3">
              {PRICE_PRESETS.map(p => {
                const active = minPrice === p.min && maxPrice === p.max
                return (
                  <button key={p.label} onClick={() => selectPreset(p.min, p.max)}
                    className="text-[12px] px-3 py-1.5 rounded-full border"
                    style={active
                      ? { backgroundColor: '#968774', color: '#fff', borderColor: '#968774' }
                      : { borderColor: '#ddd', color: '#666' }}>
                    {p.label}
                  </button>
                )
              })}
            </div>
            <div className="flex items-center gap-2">
              <input type="number" value={minPrice} onChange={e => setMinPrice(e.target.value)}
                placeholder="최소" min={0}
                className="flex-1 border rounded-lg px-3 py-2 text-[13px] focus:outline-none"
                style={{ borderColor: '#ddd' }} />
              <span className="text-[12px]" style={{ color: '#aaa' }}>~</span>
              <input type="number" value={maxPrice} onChange={e => setMaxPrice(e.target.value)}
                placeholder="최대" min={0}
                className="flex-1 border rounded-lg px-3 py-2 text-[13px] focus:outline-none"
                style={{ borderColor: '#ddd' }} />
            </div>
          </div>

          {/* 태그 */}
          <div>
            <p className="text-[13px] font-semibold mb-3" style={{ color: '#333' }}>태그</p>
            <div className="flex flex-wrap gap-2">
              {TAG_PRESETS.map(t => (
                <button key={t} onClick={() => setTag(tag === t ? '' : t)}
                  className="text-[12px] px-3 py-1.5 rounded-full border"
                  style={tag === t
                    ? { backgroundColor: '#968774', color: '#fff', borderColor: '#968774' }
                    : { borderColor: '#ddd', color: '#666' }}>
                  #{t}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* 하단 버튼 */}
        <div className="flex gap-2 px-5 py-4 border-t" style={{ borderColor: '#ebebeb' }}>
          <button onClick={resetFilter}
            className="flex-1 py-3 rounded-xl text-[13px] font-medium border"
            style={{ borderColor: '#ddd', color: '#888' }}>
            초기화
          </button>
          <button onClick={applyFilter}
            className="flex-[2] py-3 rounded-xl text-[13px] font-semibold text-white"
            style={{ backgroundColor: '#968774' }}>
            적용하기
          </button>
        </div>
      </div>
    </div>
  ) : null

  return (
    <>
      {/* 필터 버튼 (인라인) */}
      <button
        onClick={() => setOpen(true)}
        className="flex items-center gap-1 text-[12px] px-2.5 py-1 rounded-full border flex-shrink-0"
        style={{ borderColor: activeFilters > 0 ? '#968774' : '#ddd', color: activeFilters > 0 ? '#968774' : '#888' }}
      >
        <SlidersHorizontal className="w-3 h-3" />
        필터
        {activeFilters > 0 && (
          <span className="w-4 h-4 flex items-center justify-center rounded-full text-[10px] text-white"
            style={{ backgroundColor: '#968774' }}>
            {activeFilters}
          </span>
        )}
      </button>

      {portalTarget && sheet && createPortal(sheet, portalTarget)}
    </>
  )
}
