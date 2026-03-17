import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const UpdateSchema = z.object({
  name: z.string().min(1).optional(),
  slug: z.string().regex(/^[a-z0-9-]+$/).optional(),
  category_id: z.number().nullable().optional(),
  summary: z.string().nullable().optional(),
  description: z.string().nullable().optional(),
  origin: z.string().nullable().optional(),
  manufacturer: z.string().nullable().optional(),
  brand: z.string().nullable().optional(),
  brand_id: z.number().nullable().optional(),
  weight: z.number().int().nonnegative().nullable().optional(),
  price: z.number().int().positive().optional(),
  sale_price: z.number().int().positive().nullable().optional(),
  member_price: z.number().int().positive().nullable().optional(),
  cost_price: z.number().int().positive().nullable().optional(),
  tax_type: z.enum(['taxable', 'free', 'zero']).optional(),
  status: z.enum(['active', 'inactive', 'soldout']).optional(),
  stock: z.number().int().nonnegative().optional(),
  stock_alert_qty: z.number().int().nonnegative().optional(),
  mileage_rate: z.number().min(0).max(100).optional(),
  is_option: z.boolean().optional(),
  is_gift: z.boolean().optional(),
  thumbnail_url: z.string().nullable().optional(),
  images: z.array(z.any()).optional(),
  tags: z.array(z.string()).optional(),
  naver_category: z.string().nullable().optional(),
  sort_order: z.number().int().optional(),
  required_info: z.record(z.string(), z.any()).optional(),
})

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = UpdateSchema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { data, error } = await (supabase as any).from('goods').update(parsed.data).eq('id', id).select().single()
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data })
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()
  const { error } = await (supabase as any).from('goods').update({ status: 'deleted' }).eq('id', id)
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}
