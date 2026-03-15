import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { getAuthMember } from '@/lib/supabase/auth'

// 장바구니 조회
export async function GET() {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ items: [], defaultAddress: null })

  const [{ data }, { data: defaultAddr }] = await Promise.all([
    supabase
      .from('carts')
      .select(`
        id, qty, option_name,
        goods(id, name, slug, price, sale_price, member_price, thumbnail_url, stock, status)
      `)
      .eq('member_id', memberId)
      .order('created_at', { ascending: false }),
    supabase
      .from('member_addresses')
      .select('id, name, address1, address2, label')
      .eq('member_id', memberId)
      .eq('is_default', true)
      .single(),
  ])

  return NextResponse.json({ items: data ?? [], defaultAddress: defaultAddr ?? null })
}

// 장바구니 추가/수량변경
const addSchema = z.object({
  goodsId:    z.string().uuid(),
  optionName: z.string().optional(),
  qty:        z.number().int().positive(),
})

export async function POST(req: NextRequest) {
  try {
    const body = addSchema.parse(await req.json())
    const { supabase, memberId } = await getAuthMember()
    if (!memberId) return NextResponse.json({ error: '로그인이 필요합니다.' }, { status: 401 })

    // 재고 확인
    const { data: goods } = await supabase.from('goods').select('stock, status').eq('id', body.goodsId).single()
    if (!goods || goods.status !== 'active') return NextResponse.json({ error: '구매 불가 상품입니다.' }, { status: 400 })
    if (goods.stock < body.qty) return NextResponse.json({ error: '재고가 부족합니다.' }, { status: 400 })

    // 이미 담긴 상품이면 수량 합산
    let existingQuery = supabase
      .from('carts')
      .select('id, qty')
      .eq('member_id', memberId)
      .eq('goods_id', body.goodsId)

    if (body.optionName) {
      existingQuery = existingQuery.eq('option_name', body.optionName)
    } else {
      existingQuery = existingQuery.is('option_name', null)
    }

    const { data: existing } = await existingQuery.single()

    if (existing) {
      const { error } = await supabase.from('carts').update({ qty: existing.qty + body.qty }).eq('id', existing.id)
      if (error) return NextResponse.json({ error: error.message }, { status: 500 })
    } else {
      const { error } = await supabase.from('carts').insert({
        member_id:   memberId,
        goods_id:    body.goodsId,
        option_name: body.optionName ?? null,
        qty:         body.qty,
      })
      if (error) return NextResponse.json({ error: error.message }, { status: 500 })
    }
    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}

// 수량 변경
export async function PATCH(req: NextRequest) {
  try {
    const { cartId, qty } = await req.json()
    const { supabase, memberId } = await getAuthMember()
    if (!memberId) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

    await supabase.from('carts').update({ qty }).eq('id', cartId).eq('member_id', memberId)
    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}

// 장바구니 삭제
export async function DELETE(req: NextRequest) {
  try {
    const { cartId } = await req.json()
    const { supabase, memberId } = await getAuthMember()
    if (!memberId) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

    await supabase.from('carts').delete().eq('id', cartId).eq('member_id', memberId)
    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
