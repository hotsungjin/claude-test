import { NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

// 결제 모듈 연동 전 임시: 주문 상태를 바로 paid로 변경
export async function POST(req: Request) {
  try {
    const { orderId } = await req.json()
    if (!orderId) return NextResponse.json({ error: 'orderId required' }, { status: 400 })

    const supabase = await createClient() as any

    await supabase
      .from('orders')
      .update({ status: 'paid', paid_at: new Date().toISOString() })
      .eq('id', orderId)

    await supabase
      .from('order_status_logs')
      .insert({ order_id: orderId, status: 'paid', note: '결제 모듈 미연동 - 테스트 주문' })

    return NextResponse.json({ success: true })
  } catch {
    return NextResponse.json({ error: '처리 실패' }, { status: 500 })
  }
}
