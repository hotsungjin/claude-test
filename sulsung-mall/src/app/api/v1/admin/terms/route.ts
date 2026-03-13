import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  slug: z.string().min(1),
  content: z.string(),
})

export async function PUT(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { data, error } = await (supabase as any)
    .from('terms')
    .update({ content: parsed.data.content, updated_at: new Date().toISOString() })
    .eq('slug', parsed.data.slug)
    .select('updated_at')
    .single()

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ updated_at: data.updated_at })
}
