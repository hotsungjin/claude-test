import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createClient } from '@/lib/supabase/server'
import { generateOrderNo } from '@/utils/format'
import { BASE_SHIPPING_FEE, FREE_SHIPPING_THRESHOLD } from '@/constants'

const orderSchema = z.object({
  items: z.array(z.object({
    goodsId:  z.string().uuid(),
    optionId: z.number().optional(),
    qty:      z.number().int().positive(),
  })).min(1),
  recipient:    z.string().min(1),
  phone:        z.string().min(10),
  zipcode:      z.string().min(5),
  address1:     z.string().min(1),
  address2:     z.string().optional(),
  deliveryMemo: z.string().optional(),
  mileageUse:   z.number().int().min(0).default(0),
  depositUse:   z.number().int().min(0).default(0),
  couponIssueId: z.number().optional(),
})

export async function POST(req: NextRequest) {
  try {
    const body = orderSchema.parse(await req.json())
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const supabase = await createClient() as any

    // 현재 로그인 회원 확인
    const { data: { user } } = await supabase.auth.getUser()
    type MemberRow = { id: string; mileage: number; deposit: number; grade: string }
    const { data: member } = user
      ? await (supabase as any).from('members').select('id, mileage, deposit, grade').eq('auth_id', user.id).single() as { data: MemberRow | null }
      : { data: null }

    // 상품 정보 및 재고 확인
    const goodsIds = body.items.map(i => i.goodsId)
    type GoodsRow = { id: string; name: string; price: number; sale_price: number | null; stock: number; mileage_rate: number }
    const { data: goodsList } = await (supabase as any)
      .from('goods')
      .select('id, name, price, sale_price, stock, mileage_rate')
      .in('id', goodsIds)
      .eq('status', 'active') as { data: GoodsRow[] | null }

    if (!goodsList || goodsList.length !== goodsIds.length) {
      return NextResponse.json({ error: '유효하지 않은 상품이 포함되어 있습니다.' }, { status: 400 })
    }

    // 금액 계산 (서버에서 직접 계산 - 클라이언트 신뢰 안 함)
    let goodsAmount = 0
    const orderItems = body.items.map(item => {
      const goods = goodsList.find(g => g.id === item.goodsId)!
      if (goods.stock < item.qty) throw new Error(`${goods.name} 재고가 부족합니다.`)

      const unitPrice = goods.sale_price ?? goods.price
      const totalPrice = unitPrice * item.qty
      goodsAmount += totalPrice

      return {
        goods_id:     item.goodsId,
        option_id:    item.optionId ?? null,
        goods_name:   goods.name,
        qty:          item.qty,
        unit_price:   unitPrice,
        total_price:  totalPrice,
        mileage_earned: Math.floor(totalPrice * goods.mileage_rate / 100),
      }
    })

    // 배송비
    const shippingAmount = goodsAmount >= FREE_SHIPPING_THRESHOLD ? 0 : BASE_SHIPPING_FEE

    // 마일리지/예치금 검증
    const mileageUse = body.mileageUse
    const depositUse = body.depositUse
    if (member && mileageUse > member.mileage) {
      return NextResponse.json({ error: '마일리지가 부족합니다.' }, { status: 400 })
    }
    if (member && depositUse > member.deposit) {
      return NextResponse.json({ error: '예치금이 부족합니다.' }, { status: 400 })
    }

    // 총 결제금액
    const totalAmount = goodsAmount + shippingAmount - mileageUse - depositUse
    if (totalAmount < 0) {
      return NextResponse.json({ error: '결제 금액을 확인해주세요.' }, { status: 400 })
    }

    // 주문 생성
    const orderNo = generateOrderNo()
    const { data: order, error: orderError } = await supabase
      .from('orders')
      .insert({
        order_no:       orderNo,
        member_id:      member?.id ?? null,
        goods_amount:   goodsAmount,
        shipping_amount: shippingAmount,
        mileage_used:   mileageUse,
        deposit_used:   depositUse,
        total_amount:   totalAmount,
        recipient:      body.recipient,
        phone:          body.phone,
        zipcode:        body.zipcode,
        address1:       body.address1,
        address2:       body.address2 ?? null,
        delivery_memo:  body.deliveryMemo ?? null,
        status:         'pending_payment',
      })
      .select()
      .single()

    if (orderError || !order) throw new Error('주문 생성 실패')

    // 주문 상품 저장
    await supabase.from('order_items').insert(
      orderItems.map(item => ({ ...item, order_id: order.id }))
    )

    // 주문 상태 이력
    await supabase.from('order_status_logs').insert({
      order_id: order.id, to_status: 'pending_payment', created_by: 'system',
    })

    // 마일리지/예치금 차감 (선차감, 결제 실패 시 복구 필요)
    if (member && mileageUse > 0) {
      await supabase.from('mileage_logs').insert({
        member_id: member.id, delta: -mileageUse,
        balance: member.mileage - mileageUse, reason: '주문사용', order_id: order.id,
      })
      await supabase.from('members').update({ mileage: member.mileage - mileageUse }).eq('id', member.id)
    }

    return NextResponse.json({ orderId: order.id, orderNo, totalAmount })
  } catch (err: any) {
    console.error('[orders/create]', err)
    return NextResponse.json({ error: err.message ?? '주문 생성 중 오류' }, { status: 500 })
  }
}
