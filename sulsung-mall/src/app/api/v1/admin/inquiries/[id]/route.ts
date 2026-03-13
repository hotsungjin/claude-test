import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  answer: z.string().optional(),
  status: z.enum(['pending', 'answered', 'closed']).optional(),
})

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const updateData: any = {}
  if (parsed.data.answer !== undefined) updateData.reply = parsed.data.answer
  if (parsed.data.status !== undefined) updateData.status = parsed.data.status
  if (parsed.data.status === 'answered') updateData.replied_at = new Date().toISOString()

  const { data, error } = await (supabase as any).from('inquiries').update(updateData).eq('id', id).select().single()
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data })
}
