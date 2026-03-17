'use client'

import { useState, useEffect, useRef, useCallback } from 'react'
import GoodsCard from '@/components/store/goods/GoodsCard'

interface GoodsItem {
  id: string; name: string; slug: string; price: number; sale_price: number | null; thumbnail_url: string | null; sale_count?: number
}

interface Props {
  initialGoods: GoodsItem[]
  totalCount: number
  perPage: number
  queryString: string
}

export default function GoodsInfiniteGrid({ initialGoods, totalCount, perPage, queryString }: Props) {
  const [goods, setGoods] = useState<GoodsItem[]>(initialGoods)
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(initialGoods.length < totalCount)
  const loaderRef = useRef<HTMLDivElement>(null)

  // ref로 최신 값 유지 (observer 콜백에서 사용)
  const pageRef = useRef(1)
  const hasMoreRef = useRef(initialGoods.length < totalCount)
  const loadingRef = useRef(false)
  const queryStringRef = useRef(queryString)
  const totalCountRef = useRef(totalCount)

  // 필터 변경 시 리셋
  useEffect(() => {
    setGoods(initialGoods)
    setHasMore(initialGoods.length < totalCount)
    pageRef.current = 1
    hasMoreRef.current = initialGoods.length < totalCount
    loadingRef.current = false
    queryStringRef.current = queryString
    totalCountRef.current = totalCount
  }, [initialGoods, totalCount, queryString])

  const loadMore = useCallback(async () => {
    if (loadingRef.current || !hasMoreRef.current) return
    loadingRef.current = true
    setLoading(true)
    const nextPage = pageRef.current + 1
    try {
      const qs = queryStringRef.current
      const url = `/api/v1/goods?${qs}${qs ? '&' : ''}page=${nextPage}`
      const res = await fetch(url)
      const data = await res.json()
      const newGoods = data.goods ?? []
      if (newGoods.length === 0) {
        hasMoreRef.current = false
        setHasMore(false)
      } else {
        setGoods(prev => {
          const updated = [...prev, ...newGoods]
          const more = updated.length < totalCountRef.current
          hasMoreRef.current = more
          setHasMore(more)
          return updated
        })
        pageRef.current = nextPage
      }
    } catch {
      hasMoreRef.current = false
      setHasMore(false)
    } finally {
      setLoading(false)
      loadingRef.current = false
    }
  }, []) // 의존성 없음 — 모두 ref 사용

  // IntersectionObserver — loadMore가 안정적이므로 한 번만 생성
  useEffect(() => {
    const el = loaderRef.current
    if (!el) return
    const observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && !loadingRef.current && hasMoreRef.current) {
        loadMore()
      }
    }, { rootMargin: '200px' })
    observer.observe(el)
    return () => observer.disconnect()
  }, [loadMore, hasMore]) // hasMore 변경 시 observer 재연결 (loader div 재생성 대응)

  if (goods.length === 0) {
    return (
      <div className="text-center py-20">
        <p className="text-[40px] mb-3">🔍</p>
        <p className="text-[14px] font-medium" style={{ color: '#666' }}>상품이 없습니다</p>
        <p className="text-[12px] mt-1" style={{ color: '#aaa' }}>다른 카테고리를 선택해보세요</p>
      </div>
    )
  }

  return (
    <>
      <div className="grid grid-cols-2 gap-x-3 gap-y-6">
        {goods.map((item) => (
          <GoodsCard key={item.id} goods={item} />
        ))}
      </div>
      {hasMore && (
        <div ref={loaderRef} className="flex justify-center py-6">
          {loading && (
            <div className="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin" style={{ borderColor: '#968774', borderTopColor: 'transparent' }} />
          )}
        </div>
      )}
    </>
  )
}
