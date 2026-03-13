import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import GoodsDetailClient from '@/components/store/goods/GoodsDetailClient'
import type { Metadata } from 'next'

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params
  const supabase = await createClient() as any
  const { data } = await supabase.from('goods').select('name, summary, thumbnail_url').eq('slug', slug).single()
  if (!data) return {}
  const d = data as unknown as { name: string; summary: string | null; thumbnail_url: string | null }
  return {
    title: d.name,
    description: d.summary ?? undefined,
    openGraph: { images: d.thumbnail_url ? [d.thumbnail_url] : [] },
  }
}

export default async function GoodsDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const supabase = await createClient() as any

  const { data: goodsRaw, error: goodsError } = await supabase
    .from('goods')
    .select('*')
    .eq('slug', slug)
    .eq('status', 'active')
    .single()

  if (!goodsRaw || goodsError) notFound()

  // 리뷰 별도 조회
  const { data: reviews } = await supabase
    .from('reviews')
    .select('id, rating, title, content, images, created_at, members(name)')
    .eq('goods_id', goodsRaw.id)
    .order('created_at', { ascending: false })

  goodsRaw.reviews = reviews ?? []
  const goods = goodsRaw as any

  // 조회수 증가 (비동기, 응답 대기 안 함)
  void (supabase.from('goods') as any).update({ view_count: goods.view_count + 1 }).eq('id', goods.id)

  // 관련 상품: 같은 카테고리 상품 (최대 8개)
  let relatedGoods: any[] = []

  if (goods.category_id) {
    const { data } = await supabase
      .from('goods')
      .select('id, name, slug, price, sale_price, thumbnail_url, sale_count')
      .eq('status', 'active')
      .eq('category_id', goods.category_id)
      .neq('id', goods.id)
      .order('sale_count', { ascending: false })
      .limit(8)
    relatedGoods = (data as any[]) ?? []
  }

  // 카테고리 매칭이 부족하면 태그로 보충
  if (relatedGoods.length < 4 && goods.tags?.length > 0) {
    const { data: tagGoods } = await supabase
      .from('goods')
      .select('id, name, slug, price, sale_price, thumbnail_url, sale_count')
      .eq('status', 'active')
      .neq('id', goods.id)
      .overlaps('tags', goods.tags)
      .order('sale_count', { ascending: false })
      .limit(8)

    const existingIds = new Set(relatedGoods.map((g: any) => g.id))
    for (const g of (tagGoods as any[]) ?? []) {
      if (!existingIds.has(g.id) && relatedGoods.length < 8) {
        relatedGoods.push(g)
      }
    }
  }

  return <GoodsDetailClient goods={goods} relatedGoods={relatedGoods} />
}
