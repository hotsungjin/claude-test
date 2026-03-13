import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const GoodsSchema = z.object({
  name: z.string().min(1, '상품명을 입력하세요'),
  slug: z.string().optional(),
  category_id: z.number().nullable().optional(),
  summary: z.string().optional().nullable(),
  description: z.string().optional().nullable(),
  origin: z.string().optional().nullable(),
  manufacturer: z.string().optional().nullable(),
  brand: z.string().optional().nullable(),
  weight: z.number().int().nonnegative().optional().nullable(),
  price: z.number().int().positive('정가를 입력하세요'),
  sale_price: z.number().int().positive().optional().nullable(),
  cost_price: z.number().int().positive().optional().nullable(),
  tax_type: z.enum(['taxable', 'free', 'zero']).default('taxable'),
  status: z.enum(['active', 'inactive', 'soldout']).default('active'),
  stock: z.number().int().nonnegative().default(0),
  stock_alert_qty: z.number().int().nonnegative().default(5),
  mileage_rate: z.number().min(0).max(100).default(1),
  is_option: z.boolean().default(false),
  is_gift: z.boolean().default(false),
  thumbnail_url: z.string().url().optional().nullable(),
  images: z.array(z.object({ url: z.string(), sort_order: z.number(), alt: z.string().optional() })).default([]),
  tags: z.array(z.string()).default([]),
  naver_category: z.string().optional().nullable(),
  sort_order: z.number().int().default(0),
  required_info: z.record(z.string(), z.any()).default({}),
})

export async function GET(req: NextRequest) {
  const supabase = await createAdminClient() as any
  const url = new URL(req.url)
  const q = url.searchParams.get('q') ?? ''
  const limit = Number(url.searchParams.get('limit') ?? 50)

  let query = supabase
    .from('goods')
    .select('id, name, slug, price, sale_price, thumbnail_url, status')
    .order('created_at', { ascending: false })
    .limit(limit)

  if (q) query = query.ilike('name', `%${q}%`)

  const { data } = await query
  return NextResponse.json({ data: data ?? [] })
}

export async function POST(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = GoodsSchema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const insertData = { ...parsed.data, slug: parsed.data.slug || crypto.randomUUID() }
  const { data, error } = await (supabase as any).from('goods').insert(insertData).select().single()
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data }, { status: 201 })
}
