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
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(initialGoods.length < totalCount)
  const loaderRef = useRef<HTMLDivElement>(null)
  const loadingRef = useRef(false)

  // 필터 변경 시 리셋
  useEffect(() => {
    setGoods(initialGoods)
    setPage(1)
    setHasMore(initialGoods.length < totalCount)
    loadingRef.current = false
  }, [initialGoods, totalCount])

  const loadMore = useCallback(async () => {
    if (loadingRef.current || !hasMore) return
    loadingRef.current = true
    setLoading(true)
    const nextPage = page + 1
    try {
      const url = `/api/v1/goods?${queryString}${queryString ? '&' : ''}page=${nextPage}`
      const res = await fetch(url)
      const data = await res.json()
      const newGoods = data.goods ?? []
      setGoods(prev => {
        const updated = [...prev, ...newGoods]
        setHasMore(updated.length < totalCount)
        return updated
      })
      setPage(nextPage)
    } catch {} finally {
      setLoading(false)
      loadingRef.current = false
    }
  }, [hasMore, page, queryString, totalCount])

  useEffect(() => {
    const el = loaderRef.current
    if (!el) return
    const observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && !loadingRef.current) {
        loadMore()
      }
    }, { rootMargin: '200px' })
    observer.observe(el)
    return () => observer.disconnect()
  }, [loadMore])

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
