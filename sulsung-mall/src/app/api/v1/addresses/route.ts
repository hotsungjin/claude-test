import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  name: z.string().min(1),
  phone: z.string().min(1),
  zipcode: z.string().min(1),
  address1: z.string().min(1),
  address2: z.string().optional().default(''),
  label: z.string().optional().default(''),
  is_default: z.boolean().default(false),
})

export async function POST(req: NextRequest) {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const { data: member } = await (supabase as any).from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 없음' }, { status: 403 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  // 기본 배송지 설정 시 기존 기본 해제
  if (parsed.data.is_default) {
    await (supabase as any).from('member_addresses').update({ is_default: false }).eq('member_id', member.id)
  }

  const { data, error } = await (supabase as any).from('member_addresses').insert({
    ...parsed.data,
    member_id: member.id,
  }).select().single()

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data }, { status: 201 })
}
