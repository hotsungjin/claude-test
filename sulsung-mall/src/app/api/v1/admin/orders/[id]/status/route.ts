import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const VALID_TRANSITIONS: Record<string, string[]> = {
  pending_payment:   ['paid', 'cancelled'],
  paid:              ['preparing', 'cancel_requested', 'cancelled'],
  preparing:         ['shipped', 'cancel_requested'],
  shipped:           ['delivered'],
  delivered:         ['confirmed', 'return_requested'],
  cancel_requested:  ['cancelled', 'paid'],
  return_requested:  ['returning', 'delivered'],
  returning:         ['returned'],
}

const Schema = z.object({
  status: z.enum([
    'pending_payment', 'paid', 'preparing', 'shipped', 'delivered',
    'confirmed', 'cancel_requested', 'cancelled', 'return_requested',
    'returning', 'returned', 'exchange_requested', 'exchanged',
  ]),
  memo: z.string().optional(),
})

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { status: newStatus, memo } = parsed.data

  // 현재 상태 조회
  const { data: order } = await (supabase as any)
    .from('orders')
    .select('status, member_id, mileage_used')
    .eq('id', id).single()
  if (!order) return NextResponse.json({ error: '주문을 찾을 수 없습니다' }, { status: 404 })

  // 같은 상태면 메모만 저장
  if (order.status === newStatus) {
    if (memo) await (supabase as any).from('orders').update({ admin_memo: memo }).eq('id', id)
    return NextResponse.json({ success: true, status: newStatus })
  }

  const allowed = VALID_TRANSITIONS[order.status]
  if (allowed && !allowed.includes(newStatus)) {
    return NextResponse.json({ error: `${order.status} → ${newStatus} 전환은 허용되지 않습니다` }, { status: 400 })
  }

  const updateData: any = { status: newStatus }
  if (newStatus === 'paid') updateData.paid_at = new Date().toISOString()
  if (newStatus === 'confirmed') updateData.confirmed_at = new Date().toISOString()
  if (newStatus === 'cancelled') updateData.cancelled_at = new Date().toISOString()
  if (memo) updateData.admin_memo = memo

  // 상태 업데이트
  const { error: updateError } = await (supabase as any).from('orders').update(updateData).eq('id', id)
  if (updateError) return NextResponse.json({ error: updateError.message }, { status: 500 })

  // 상태 이력 기록
  await (supabase as any).from('order_status_logs').insert({
    order_id: id,
    from_status: order.status,
    to_status: newStatus,
    memo: memo ?? null,
    changed_by: 'admin',
  })

  // 취소/반품 완료 시 마일리지 복원 + 재고 복원
  if (['cancelled', 'returned'].includes(newStatus)) {
    // 마일리지 복원
    if (order.mileage_used > 0 && order.member_id) {
      await (supabase as any).rpc('add_mileage', {
        p_member_id: order.member_id,
        p_amount: order.mileage_used,
        p_description: newStatus === 'cancelled' ? '주문 취소 마일리지 복원' : '반품 완료 마일리지 복원',
      })
    }

    // 재고 복원
    const { data: items } = await (supabase as any)
      .from('order_items').select('goods_id, qty').eq('order_id', id)
    if (items) {
      for (const item of items) {
        if (item.goods_id) {
          // goods 테이블의 stock 직접 증가
          const { data: goods } = await (supabase as any)
            .from('goods').select('stock').eq('id', item.goods_id).single()
          if (goods) {
            await (supabase as any)
              .from('goods')
              .update({ stock: goods.stock + item.qty })
              .eq('id', item.goods_id)
          }
        }
      }
    }
  }

  return NextResponse.json({ success: true, status: newStatus })
}
