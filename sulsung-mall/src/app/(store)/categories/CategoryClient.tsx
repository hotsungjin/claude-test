'use client'

import { useState, useRef, useEffect, useCallback } from 'react'
import Link from 'next/link'
import Image from 'next/image'

interface Category {
  id: number
  name: string
  slug: string
  image_url: string | null
  sort_order: number
  parent_id: number | null
}

const CATEGORY_EMOJI: Record<string, string> = {
  '한우': '🐄',
  '한돈': '🐷',
  '간편식': '🍱',
  '간식·유제품': '🥛',
  '베이비·키즈': '👶',
  '선물세트': '🎁',
  '존쿡델리미트': '🥩',
}

export default function CategoryClient({
  parents,
  children,
}: {
  parents: Category[]
  children: Category[]
}) {
  const [activeId, setActiveId] = useState<number | null>(parents[0]?.id ?? null)
  const rightRef = useRef<HTMLDivElement>(null)
  const sectionRefs = useRef<Map<number, HTMLDivElement>>(new Map())
  const isScrolling = useRef(false)
  const leftRef = useRef<HTMLDivElement>(null)

  // 우측 스크롤 시 좌측 활성 카테고리 변경
  const handleScroll = useCallback(() => {
    if (isScrolling.current) return
    const container = rightRef.current
    if (!container) return

    const scrollTop = container.scrollTop
    let currentId = parents[0]?.id ?? null

    for (const parent of parents) {
      const el = sectionRefs.current.get(parent.id)
      if (el && el.offsetTop <= scrollTop + 60) {
        currentId = parent.id
      }
    }

    if (currentId !== null) {
      setActiveId(currentId)
    }
  }, [parents])

  useEffect(() => {
    const container = rightRef.current
    if (!container) return
    container.addEventListener('scroll', handleScroll, { passive: true })
    return () => container.removeEventListener('scroll', handleScroll)
  }, [handleScroll])

  // 좌측 활성 항목이 보이도록 스크롤
  useEffect(() => {
    if (!leftRef.current || activeId === null) return
    const activeBtn = leftRef.current.querySelector(`[data-cat-id="${activeId}"]`) as HTMLElement
    if (activeBtn) {
      activeBtn.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    }
  }, [activeId])

  // 좌측 클릭 시 우측 해당 섹션으로 스크롤
  function scrollToSection(parentId: number) {
    setActiveId(parentId)
    const el = sectionRefs.current.get(parentId)
    const container = rightRef.current
    if (el && container) {
      isScrolling.current = true
      container.scrollTo({ top: el.offsetTop, behavior: 'smooth' })
      setTimeout(() => { isScrolling.current = false }, 500)
    }
  }

  return (
    <div className="min-h-screen flex flex-col" style={{ backgroundColor: '#fff' }}>
      <style>{`[data-store-footer] { display: none !important; }`}</style>
      {/* 헤더 */}
      <div className="px-4 py-3 border-b" style={{ borderColor: '#eee' }}>
        <h1 className="text-[18px] font-bold" style={{ color: '#333' }}>카테고리</h1>
      </div>

      <div className="flex flex-1 overflow-hidden">
        {/* 좌측: 대분류 */}
        <div
          ref={leftRef}
          className="overflow-y-auto flex-shrink-0 py-2"
          style={{
            width: '165px',
            backgroundColor: '#f7f5f2',
          }}
        >
          {parents.map(cat => {
            const isActive = cat.id === activeId
            return (
              <button
                key={cat.id}
                data-cat-id={cat.id}
                onClick={() => scrollToSection(cat.id)}
                className="w-full text-center px-3 py-4"
                style={{
                  backgroundColor: isActive ? '#fff' : 'transparent',
                  borderRadius: isActive ? '24px' : '0',
                  margin: isActive ? '2px 8px' : '0',
                  width: isActive ? 'calc(100% - 16px)' : '100%',
                }}
              >
                <span
                  className="text-[17px] leading-tight block"
                  style={{
                    color: isActive ? '#968774' : '#666',
                    fontWeight: isActive ? 700 : 400,
                  }}
                >
                  {cat.name}
                </span>
              </button>
            )
          })}
        </div>

        {/* 우측: 모든 카테고리 연속 표시 */}
        <div ref={rightRef} className="flex-1 overflow-y-auto">
          {parents.map(parent => {
            const parentChildren = children.filter(c => c.parent_id === parent.id)
            const emoji = CATEGORY_EMOJI[parent.name] ?? '🥩'
            return (
              <div
                key={parent.id}
                ref={el => { if (el) sectionRefs.current.set(parent.id, el) }}
              >
                {/* 대분류 헤더 */}
                <Link
                  href={`/goods?category=${parent.slug}`}
                  className="flex items-center gap-3 px-4 py-5"
                  style={{ backgroundColor: '#faf9f7' }}
                >
                  {parent.image_url ? (
                    <div className="w-11 h-11 rounded-full overflow-hidden flex-shrink-0">
                      <Image
                        src={parent.image_url}
                        alt={parent.name}
                        width={44}
                        height={44}
                        className="object-cover w-full h-full"
                      />
                    </div>
                  ) : (
                    <div
                      className="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                      style={{ backgroundColor: '#f3f0ed' }}
                    >
                      <span className="text-[20px]">{emoji}</span>
                    </div>
                  )}
                  <span className="text-[17px] font-bold flex-1" style={{ color: '#333' }}>
                    {parent.name}
                  </span>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M9 6l6 6-6 6" stroke="#bbb" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                </Link>

                {/* 소분류 리스트 */}
                {parentChildren.map(child => (
                  <Link
                    key={child.id}
                    href={`/goods?category=${child.slug}`}
                    className="flex items-center justify-between px-5 py-4"
                  >
                    <span className="text-[16px]" style={{ color: '#333' }}>
                      {child.name}
                    </span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path d="M9 6l6 6-6 6" stroke="#ccc" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                  </Link>
                ))}
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}
