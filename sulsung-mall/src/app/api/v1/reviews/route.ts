import { NextRequest, NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'
import { z } from 'zod'

const Schema = z.object({
  goods_id: z.string().uuid(),
  order_item_id: z.string().uuid().optional().nullable(),
  rating: z.number().int().min(1).max(5),
  title: z.string().optional().nullable(),
  content: z.string().min(1, '리뷰 내용을 입력하세요'),
  images: z.array(z.object({ url: z.string(), sort_order: z.number() })).default([]),
})

export async function POST(req: NextRequest) {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ error: '로그인이 필요합니다.' }, { status: 401 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  // 주문 아이템 소유권 확인
  if (parsed.data.order_item_id) {
    const { data: orderItem } = await supabase
      .from('order_items')
      .select('id, orders(member_id)')
      .eq('id', parsed.data.order_item_id)
      .single()
    if (!orderItem || (orderItem.orders as any)?.member_id !== memberId) {
      return NextResponse.json({ error: '주문 정보를 확인할 수 없습니다.' }, { status: 403 })
    }
  }

  const { data, error } = await supabase.from('reviews').insert({
    ...parsed.data,
    member_id: memberId,
  }).select().single()

  if (error) {
    if (error.code === '23505') return NextResponse.json({ error: '이미 리뷰를 작성하셨습니다.' }, { status: 409 })
    return NextResponse.json({ error: error.message }, { status: 500 })
  }

  // 리뷰 작성 마일리지 지급 (100P)
  await supabase.rpc('add_mileage', { p_member_id: memberId, p_amount: 100, p_description: '리뷰 작성 적립' }).catch(() => {})

  return NextResponse.json({ data }, { status: 201 })
}
