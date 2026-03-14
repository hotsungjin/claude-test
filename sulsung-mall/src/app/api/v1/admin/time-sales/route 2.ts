import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  name: z.string().min(1),
  goods_id: z.string().uuid(),
  discount_type: z.enum(['amount', 'rate']),
  discount_value: z.number().min(1),
  starts_at: z.string(),
  ends_at: z.string(),
  max_qty: z.number().optional(),
  is_active: z.boolean().optional(),
})

export async function GET() {
  const supabase = await createAdminClient()
  const { data } = await (supabase as any)
    .from('time_sales')
    .select('*, goods(name, thumbnail_url, price)')
    .order('created_at', { ascending: false })
  return NextResponse.json({ items: data ?? [] })
}

export async function POST(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { data, error } = await (supabase as any).from('time_sales').insert(parsed.data).select().single()
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json(data)
}

export async function PATCH(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const { id, ...rest } = body
  if (!id) return NextResponse.json({ error: 'id 필요' }, { status: 400 })

  const { error } = await (supabase as any).from('time_sales').update(rest).eq('id', id)
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}

export async function DELETE(req: NextRequest) {
  const supabase = await createAdminClient()
  const { id } = await req.json()
  if (!id) return NextResponse.json({ error: 'id 필요' }, { status: 400 })

  await (supabase as any).from('time_sales').delete().eq('id', id)
  return NextResponse.json({ success: true })
}
