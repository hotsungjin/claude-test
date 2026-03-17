import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  name: z.string().min(1),
  image_url: z.string().min(1),
  mobile_image_url: z.string().optional().nullable(),
  link_url: z.string().optional().nullable(),
  target_pages: z.array(z.string()).optional(),
  starts_at: z.string().optional().nullable(),
  ends_at: z.string().optional().nullable(),
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

const PartialSchema = z.object({
  name: z.string().min(1).optional(),
  image_url: z.string().min(1).optional(),
  mobile_image_url: z.string().optional().nullable(),
  link_url: z.string().optional().nullable(),
  target_pages: z.array(z.string()).optional(),
  starts_at: z.string().optional().nullable(),
  ends_at: z.string().optional().nullable(),
  hide_duration: z.number().optional(),
  sort_order: z.number().optional(),
  is_active: z.boolean().optional(),
})

export async function PATCH(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const { id, ...rest } = body
  if (!id) return NextResponse.json({ error: 'id 필요' }, { status: 400 })

  const parsed = PartialSchema.safeParse(rest)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const updateData: Record<string, any> = { ...parsed.data }
  // 빈 문자열을 null로 변환
  if ('link_url' in updateData && !updateData.link_url) updateData.link_url = null
  if ('mobile_image_url' in updateData && !updateData.mobile_image_url) updateData.mobile_image_url = null

  const { error } = await (supabase as any).from('popups').update(updateData).eq('id', id)
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
