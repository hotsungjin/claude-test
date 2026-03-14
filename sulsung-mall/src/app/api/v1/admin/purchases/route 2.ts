import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

// 발주서 목록
export async function GET() {
  const supabase = await createClient() as any
  const { data, error } = await supabase
    .from('purchase_orders')
    .select('*, suppliers(name), purchase_order_items(*, goods(id, name, thumbnail_url))')
    .order('created_at', { ascending: false })

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ orders: data })
}

// 발주서 생성
export async function POST(req: NextRequest) {
  const supabase = await createClient() as any
  const body = await req.json()

  const { supplier_id, memo, items } = body
  if (!items?.length) return NextResponse.json({ error: '상품을 선택해주세요' }, { status: 400 })

  const totalAmount = items.reduce((s: number, i: any) => s + (i.qty * i.unit_price), 0)

  const { data: po, error: poError } = await supabase
    .from('purchase_orders')
    .insert({ supplier_id: supplier_id || null, memo, total_amount: totalAmount, status: 'draft' })
    .select()
    .single()

  if (poError) return NextResponse.json({ error: poError.message }, { status: 500 })

  const poItems = items.map((i: any) => ({
    po_id: po.id,
    goods_id: i.goods_id,
    qty: i.qty,
    unit_price: i.unit_price || 0,
  }))

  const { error: itemsError } = await supabase.from('purchase_order_items').insert(poItems)
  if (itemsError) return NextResponse.json({ error: itemsError.message }, { status: 500 })

  return NextResponse.json({ order: po })
}
