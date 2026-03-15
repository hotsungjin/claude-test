import { NextRequest, NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'
import { z } from 'zod'

const Schema = z.object({
  reason: z.string().min(1, '취소 사유를 입력해주세요'),
})

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ error: '로그인이 필요합니다' }, { status: 401 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  // 주문 확인 (본인 주문만)
  const { data: order } = await supabase
    .from('orders').select('status, mileage_used')
    .eq('id', id).eq('member_id', memberId).single()

  if (!order) return NextResponse.json({ error: '주문을 찾을 수 없습니다' }, { status: 404 })

  // pending_payment, paid 상태에서만 직접 취소 가능
  const canCancel = ['pending_payment', 'paid'].includes(order.status)
  // preparing 상태는 취소 요청만 가능
  const canRequest = order.status === 'preparing'

  if (!canCancel && !canRequest) {
    return NextResponse.json({ error: '취소할 수 없는 주문 상태입니다' }, { status: 400 })
  }

  const newStatus = canCancel ? 'cancelled' : 'cancel_requested'

  await supabase.from('orders').update({
    status: newStatus,
    ...(newStatus === 'cancelled' ? { cancelled_at: new Date().toISOString() } : {}),
  }).eq('id', id)

  await supabase.from('order_status_logs').insert({
    order_id: id,
    from_status: order.status,
    to_status: newStatus,
    memo: parsed.data.reason,
    changed_by: 'customer',
  })

  // 즉시 취소 시 마일리지 복원
  if (newStatus === 'cancelled' && order.mileage_used > 0) {
    await supabase.rpc('add_mileage', {
      p_member_id: memberId,
      p_amount: order.mileage_used,
      p_description: `주문 취소 마일리지 복원`,
    })
  }

  return NextResponse.json({ success: true, status: newStatus })
}
