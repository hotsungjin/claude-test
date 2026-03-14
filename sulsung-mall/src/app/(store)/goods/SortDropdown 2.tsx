'use client'

import { useState, useRef, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { ChevronDown } from 'lucide-react'

const SORT_OPTIONS = [
  { value: '', label: '인기순' },
  { value: 'newest', label: '최신순' },
  { value: 'price_asc', label: '낮은가격순' },
  { value: 'price_desc', label: '높은가격순' },
]

interface Props {
  currentSort: string
  currentParams: Record<string, string | undefined>
}

export default function SortDropdown({ currentSort, currentParams }: Props) {
  const router = useRouter()
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  const currentLabel = SORT_OPTIONS.find(o => o.value === (currentSort ?? ''))?.label ?? '인기순'

  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', handleClick)
    return () => document.removeEventListener('mousedown', handleClick)
  }, [])

  const handleSelect = (value: string) => {
    const qs = new URLSearchParams()
    if (currentParams.category) qs.set('category', currentParams.category)
    if (value) qs.set('sort', value)
    if (currentParams.q) qs.set('q', currentParams.q)
    if (currentParams.min_price) qs.set('min_price', currentParams.min_price)
    if (currentParams.max_price) qs.set('max_price', currentParams.max_price)
    if (currentParams.tag) qs.set('tag', currentParams.tag)
    if (currentParams.curation) qs.set('curation', currentParams.curation)
    const str = qs.toString()
    router.push(`/goods${str ? `?${str}` : ''}`)
    setOpen(false)
  }

  return (
    <div ref={ref} className="relative">
      <button
        onClick={() => setOpen(!open)}
        className="flex items-center gap-0.5 text-[12px]"
        style={{ color: '#333' }}
      >
        {currentLabel}
        <ChevronDown className="w-3.5 h-3.5" style={{ color: '#999' }} />
      </button>

      {open && (
        <div
          className="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border z-50 py-1 min-w-[100px]"
          style={{ borderColor: '#eee' }}
        >
          {SORT_OPTIONS.map(opt => (
            <button
              key={opt.value}
              onClick={() => handleSelect(opt.value)}
              className="block w-full text-left px-4 py-2 text-[12px]"
              style={{
                color: (currentSort ?? '') === opt.value ? '#968774' : '#666',
                fontWeight: (currentSort ?? '') === opt.value ? '600' : '400',
                backgroundColor: (currentSort ?? '') === opt.value ? '#f9f7f5' : 'transparent',
              }}
            >
              {opt.label}
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
