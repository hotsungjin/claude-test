import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  name: z.string().min(1),
  image_url: z.string().min(1),
  mobile_image_url: z.string().optional(),
  link_url: z.string().optional(),
  position: z.enum(['center', 'bottom', 'fullscreen']).optional(),
  starts_at: z.string().optional(),
  ends_at: z.string().optional(),
  hide_duration: z.number().optional(),
  sort_order: z.number().optional(),
  is_active: z.boolean().optional(),
})

export async function GET() {
  const supabase = await createAdminClient()
  const { data } = await (supabase as any)
    .from('popups')
    .select('*')
    .order('sort_order')
  return NextResponse.json({ items: data ?? [] })
}

export async function POST(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { data, error } = await (supabase as any).from('popups').insert(parsed.data).select().single()
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json(data)
}

export async function PATCH(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const { id, ...rest } = body
  if (!id) return NextResponse.json({ error: 'id 필요' }, { status: 400 })

  const { error } = await (supabase as any).from('popups').update(rest).eq('id', id)
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}

export async function DELETE(req: NextRequest) {
  const supabase = await createAdminClient()
  const { id } = await req.json()
  if (!id) return NextResponse.json({ error: 'id 필요' }, { status: 400 })

  await (supabase as any).from('popups').delete().eq('id', id)
  return NextResponse.json({ success: true })
}
