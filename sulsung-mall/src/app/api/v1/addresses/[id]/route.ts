import { NextRequest, NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'
import { z } from 'zod'

const Schema = z.object({
  name: z.string().min(1).optional(),
  phone: z.string().optional(),
  zipcode: z.string().optional(),
  address1: z.string().optional(),
  address2: z.string().optional(),
  label: z.string().optional(),
  is_default: z.boolean().optional(),
})

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  if (parsed.data.is_default) {
    await (supabase as any).from('member_addresses').update({ is_default: false }).eq('member_id', memberId)
  }

  const { data, error } = await (supabase as any).from('member_addresses')
    .update(parsed.data).eq('id', id).eq('member_id', memberId).select().single()

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data })
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const { error } = await (supabase as any).from('member_addresses').delete().eq('id', id).eq('member_id', memberId)
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}
