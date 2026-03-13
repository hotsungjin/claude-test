import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
import { ITEMS_PER_PAGE } from '@/constants'

export async function GET(req: NextRequest) {
  const sp = req.nextUrl.searchParams
  const page = Number(sp.get('page') ?? 1)
  const from = (page - 1) * ITEMS_PER_PAGE
  const supabase = await createClient()

  const category = sp.get('category')
  const sort = sp.get('sort')
  const q = sp.get('q')
  const minPrice = sp.get('min_price')
  const maxPrice = sp.get('max_price')
  const tag = sp.get('tag')
  const curation = sp.get('curation')

  let goods: any[] = []
  let count: number | null = 0

  if (curation) {
    const { data: cur } = await (supabase as any)
      .from('curations').select('id').eq('slug', curation).eq('is_active', true).single()
    if (cur) {
      const { data, count: c } = await (supabase as any)
        .from('curation_goods')
        .select('goods(id, name, slug, price, sale_price, thumbnail_url, sale_count, tags, status)', { count: 'exact' })
        .eq('curation_id', cur.id)
        .order('sort_order')
        .range(from, from + ITEMS_PER_PAGE - 1)
      goods = (data ?? []).map((cg: any) => cg.goods).filter((g: any) => g?.status === 'active')
      count = c
    }
  } else {
    let query = supabase
      .from('goods')
      .select('id, name, slug, price, sale_price, thumbnail_url, sale_count, tags', { count: 'exact' })
      .eq('status', 'active')
      .range(from, from + ITEMS_PER_PAGE - 1)

    if (q) query = query.or(`name.ilike.%${q}%,tags.cs.{${q}}`)
    if (minPrice) { const n = Number(minPrice); if (!isNaN(n)) query = query.gte('price', n) }
    if (maxPrice) { const n = Number(maxPrice); if (!isNaN(n)) query = query.lte('price', n) }
    if (tag) query = query.contains('tags', [tag])

    if (sort === 'newest') query = query.order('created_at', { ascending: false })
    else if (sort === 'price_asc') query = query.order('sale_price', { ascending: true, nullsFirst: false })
    else if (sort === 'price_desc') query = query.order('price', { ascending: false })
    else query = query.order('sale_count', { ascending: false })

    if (category) {
      const { data: cat } = await supabase.from('categories').select('id').eq('slug', category).single()
      if (cat) query = (query as any).eq('category_id', (cat as any).id)
    }

    const { data, count: c } = await query
    goods = (data as any[]) ?? []
    count = c
  }

  return NextResponse.json({ goods, count, page, perPage: ITEMS_PER_PAGE })
}
