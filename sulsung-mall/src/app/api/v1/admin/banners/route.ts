import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  name: z.string().min(1),
  position: z.enum(['main_top', 'main_middle', 'main_bottom', 'main_ad']).default('main_top'),
  image_url: z.string().min(1),
  mobile_image_url: z.string().nullable().optional(),
  link_url: z.string().nullable().optional(),
  alt: z.string().nullable().optional(),
  sort_order: z.number().int().default(0),
  starts_at: z.string().nullable().optional(),
  ends_at: z.string().nullable().optional(),
  is_active: z.boolean().default(true),
})

export async function POST(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })
  const { data, error } = await (supabase as any).from('banners').insert(parsed.data).select().single()
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data }, { status: 201 })
}
