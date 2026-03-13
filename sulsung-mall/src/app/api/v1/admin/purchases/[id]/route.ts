import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

// 발주서 상세
export async function GET(_: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any

  const { data, error } = await supabase
    .from('purchase_orders')
    .select('*, suppliers(name), purchase_order_items(*, goods(id, name, thumbnail_url, stock))')
    .eq('id', Number(id))
    .single()

  if (error) return NextResponse.json({ error: error.message }, { status: 404 })
  return NextResponse.json({ order: data })
}

// 발주서 상태 변경 / 입고 처리
export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const body = await req.json()

  // 입고 처리
  if (body.action === 'receive') {
    const { items } = body // [{ id: poi_id, received_qty: N }]

    for (const item of items) {
      // 발주 아이템 업데이트
      const { data: poi } = await supabase
        .from('purchase_order_items')
        .update({ received_qty: item.received_qty })
        .eq('id', item.id)
        .select('goods_id, qty')
        .single()

      if (poi) {
        // 재고 증가
        await supabase.rpc('increment_stock', { gid: poi.goods_id, amount: item.received_qty })

        // 재고 로그
        await supabase.from('stock_logs').insert({
          goods_id: poi.goods_id,
          type: 'inbound',
          qty: item.received_qty,
          memo: `발주서 PO-${String(id).padStart(4, '0')} 입고`,
          ref_type: 'purchase_order',
          ref_id: id,
        })
      }
    }

    // 전체 입고 완료 여부 확인
    const { data: allItems } = await supabase
      .from('purchase_order_items')
      .select('qty, received_qty')
      .eq('po_id', Number(id))

    const allReceived = (allItems ?? []).every((i: any) => i.received_qty >= i.qty)
    if (allReceived) {
      await supabase.from('purchase_orders').update({ status: 'completed', completed_at: new Date().toISOString() }).eq('id', Number(id))
    } else {
      await supabase.from('purchase_orders').update({ status: 'receiving' }).eq('id', Number(id))
    }

    return NextResponse.json({ success: true })
  }

  // 상태 변경
  if (body.status) {
    const update: any = { status: body.status, updated_at: new Date().toISOString() }
    if (body.status === 'ordered') update.ordered_at = new Date().toISOString()
    if (body.status === 'completed') update.completed_at = new Date().toISOString()

    const { error } = await supabase.from('purchase_orders').update(update).eq('id', Number(id))
    if (error) return NextResponse.json({ error: error.message }, { status: 500 })
    return NextResponse.json({ success: true })
  }

  return NextResponse.json({ error: 'Invalid action' }, { status: 400 })
}

// 발주서 삭제
export async function DELETE(_: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any

  const { error } = await supabase.from('purchase_orders').update({ status: 'cancelled' }).eq('id', Number(id))
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}
