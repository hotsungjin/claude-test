import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { z } from 'zod'

const GoodsBulkSchema = z.object({
  type: z.literal('goods'),
  action: z.enum(['status_change', 'delete']),
  ids: z.array(z.string().uuid()).min(1),
  value: z.string().optional(),
})

const OrderBulkSchema = z.object({
  type: z.literal('orders'),
  action: z.enum(['status_change']),
  ids: z.array(z.string().uuid()).min(1),
  value: z.string(),
})

const Schema = z.union([GoodsBulkSchema, OrderBulkSchema])

export async function POST(req: NextRequest) {
  const supabase = await createAdminClient()
  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const data = parsed.data

  if (data.type === 'goods') {
    if (data.action === 'status_change' && data.value) {
      const { error } = await (supabase as any)
        .from('goods')
        .update({ status: data.value })
        .in('id', data.ids)
      if (error) return NextResponse.json({ error: error.message }, { status: 500 })
    } else if (data.action === 'delete') {
      const { error } = await (supabase as any)
        .from('goods')
        .update({ status: 'deleted' })
        .in('id', data.ids)
      if (error) return NextResponse.json({ error: error.message }, { status: 500 })
    }
    return NextResponse.json({ success: true, affected: data.ids.length })
  }

  if (data.type === 'orders') {
    if (data.action === 'status_change') {
      // 주문 일괄 상태 변경 — 각 주문의 이전 상태를 조회해서 로그도 기록
      const { data: orders } = await (supabase as any)
        .from('orders').select('id, status').in('id', data.ids)

      let changed = 0
      for (const order of orders ?? []) {
        const { error } = await (supabase as any)
          .from('orders').update({ status: data.value }).eq('id', order.id)
        if (!error) {
          await (supabase as any).from('order_status_logs').insert({
            order_id: order.id,
            from_status: order.status,
            to_status: data.value,
            memo: '일괄 상태 변경',
            changed_by: 'admin',
          })
          changed++
        }
      }
      return NextResponse.json({ success: true, affected: changed })
    }
  }

  return NextResponse.json({ error: '지원하지 않는 작업' }, { status: 400 })
}
