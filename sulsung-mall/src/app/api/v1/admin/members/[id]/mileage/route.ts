import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  delta: z.number().int().refine(v => v !== 0, '0은 입력할 수 없습니다'),
  reason: z.string().min(1),
})

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { delta, reason } = parsed.data

  // 현재 마일리지 조회
  const { data: member } = await (supabase as any).from('members').select('mileage').eq('id', id).single()
  if (!member) return NextResponse.json({ error: '회원을 찾을 수 없습니다' }, { status: 404 })

  const newBalance = member.mileage + delta
  if (newBalance < 0) return NextResponse.json({ error: '마일리지 잔액이 부족합니다' }, { status: 400 })

  // 마일리지 업데이트
  const { error: updateError } = await (supabase as any).from('members').update({ mileage: newBalance }).eq('id', id)
  if (updateError) return NextResponse.json({ error: updateError.message }, { status: 500 })

  // 이력 기록
  await (supabase as any).from('mileage_logs').insert({
    member_id: id,
    delta,
    balance: newBalance,
    reason,
  })

  return NextResponse.json({ success: true, balance: newBalance })
}
