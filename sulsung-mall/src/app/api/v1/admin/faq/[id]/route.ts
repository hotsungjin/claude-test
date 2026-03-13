import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const UpdateSchema = z.object({
  category: z.string().optional(),
  question: z.string().optional(),
  answer: z.string().optional(),
  sort_order: z.number().optional(),
  is_active: z.boolean().optional(),
})

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  try {
    const body = UpdateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any

    const { data, error } = await supabase.from('faqs').update(body).eq('id', Number(id)).select().single()
    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  try {
    const supabase = await createAdminClient() as any
    const { error } = await supabase.from('faqs').delete().eq('id', Number(id))
    if (error) throw error
    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
