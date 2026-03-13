import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  company_id: z.number(),
  courier_name: z.string().min(1),
  tracking_no: z.string().min(1),
})

// 송장번호 등록/수정
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { company_id, courier_name, tracking_no } = parsed.data

  // 주문 확인
  const { data: order } = await (supabase as any).from('orders').select('status').eq('id', id).single()
  if (!order) return NextResponse.json({ error: '주문을 찾을 수 없습니다' }, { status: 404 })

  if (!['preparing', 'shipped'].includes(order.status)) {
    return NextResponse.json({ error: '배송준비 또는 배송중 상태에서만 송장을 등록할 수 있습니다' }, { status: 400 })
  }

  // 기존 배송 정보가 있으면 업데이트, 없으면 생성
  const { data: existing } = await (supabase as any)
    .from('deliveries').select('id').eq('order_id', id).single()

  if (existing) {
    await (supabase as any).from('deliveries').update({
      company_id,
      courier_name,
      tracking_no,
      shipped_at: new Date().toISOString(),
      status: 'shipped',
    }).eq('id', existing.id)
  } else {
    await (supabase as any).from('deliveries').insert({
      order_id: id,
      company_id,
      courier_name,
      tracking_no,
      shipped_at: new Date().toISOString(),
      status: 'shipped',
    })
  }

  // 주문 상태가 preparing이면 shipped로 자동 전환
  if (order.status === 'preparing') {
    await (supabase as any).from('orders').update({ status: 'shipped' }).eq('id', id)
    await (supabase as any).from('order_status_logs').insert({
      order_id: id,
      from_status: 'preparing',
      to_status: 'shipped',
      memo: `송장등록: ${courier_name} ${tracking_no}`,
      changed_by: 'admin',
    })
  }

  return NextResponse.json({ success: true })
}

// 배송 정보 조회
export async function GET(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient()

  const { data: delivery } = await (supabase as any)
    .from('deliveries')
    .select('*, delivery_companies(name, tracking_url)')
    .eq('order_id', id)
    .single()

  const { data: companies } = await (supabase as any)
    .from('delivery_companies').select('id, name').order('id')

  return NextResponse.json({ delivery, companies: companies ?? [] })
}
