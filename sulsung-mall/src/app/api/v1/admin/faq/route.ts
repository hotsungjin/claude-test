import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const CreateSchema = z.object({
  category: z.string().default('일반'),
  question: z.string().min(1),
  answer: z.string().min(1),
  sort_order: z.number().default(0),
  is_active: z.boolean().default(true),
})

export async function POST(req: NextRequest) {
  try {
    const body = CreateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any

    const { data, error } = await supabase.from('faqs').insert(body).select().single()
    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
