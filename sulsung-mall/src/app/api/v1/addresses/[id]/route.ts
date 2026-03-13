import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
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
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const { data: member } = await (supabase as any).from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 없음' }, { status: 403 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  if (parsed.data.is_default) {
    await (supabase as any).from('member_addresses').update({ is_default: false }).eq('member_id', member.id)
  }

  const { data, error } = await (supabase as any).from('member_addresses')
    .update(parsed.data).eq('id', id).eq('member_id', member.id).select().single()

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data })
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const { data: member } = await (supabase as any).from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 없음' }, { status: 403 })

  const { error } = await (supabase as any).from('member_addresses').delete().eq('id', id).eq('member_id', member.id)
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}
