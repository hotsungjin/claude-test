import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const CreateSchema = z.object({
  name: z.string().min(1, '카테고리명을 입력하세요'),
  slug: z.string().min(1, '슬러그를 입력하세요'),
  parent_id: z.number().nullable().optional(),
  image_url: z.string().url().nullable().optional(),
  sort_order: z.number().default(0),
  is_active: z.boolean().default(true),
})

export async function POST(req: NextRequest) {
  try {
    const body = CreateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any

    const { data, error } = await supabase.from('categories').insert(body).select().single()
    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
