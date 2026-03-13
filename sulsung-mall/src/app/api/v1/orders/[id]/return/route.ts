import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  reason: z.string().min(1, '반품 사유를 입력해주세요'),
})

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인이 필요합니다' }, { status: 401 })

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 정보를 찾을 수 없습니다' }, { status: 404 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { data: order } = await supabase
    .from('orders').select('status')
    .eq('id', id).eq('member_id', member.id).single()

  if (!order) return NextResponse.json({ error: '주문을 찾을 수 없습니다' }, { status: 404 })

  if (!['delivered'].includes(order.status)) {
    return NextResponse.json({ error: '배송완료 상태에서만 반품 요청이 가능합니다' }, { status: 400 })
  }

  await supabase.from('orders').update({ status: 'return_requested' }).eq('id', id)

  await supabase.from('order_status_logs').insert({
    order_id: id,
    from_status: order.status,
    to_status: 'return_requested',
    memo: parsed.data.reason,
    changed_by: 'customer',
  })

  return NextResponse.json({ success: true })
}
