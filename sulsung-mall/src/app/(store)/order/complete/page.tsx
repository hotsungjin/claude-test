import { createClient } from '@/lib/supabase/server'
import { CheckCircle } from 'lucide-react'
import Link from 'next/link'
import { formatPrice } from '@/utils/format'

export default async function OrderCompletePage({ searchParams }: { searchParams: Promise<{ orderId?: string }> }) {
  const { orderId } = await searchParams
  const supabase = await createClient() as any

  const { data: order } = orderId
    ? await supabase.from('orders').select('order_no, total_amount, status, order_items(goods_name, qty)').eq('id', orderId).single()
    : { data: null }

  return (
    <div className="max-w-lg mx-auto px-4 py-16 text-center">
      <CheckCircle className="w-20 h-20 mx-auto mb-6" style={{ color: '#968774' }} />
      <h1 className="text-2xl font-bold text-gray-900 mb-2">주문이 완료되었습니다</h1>

      {order && (
        <div className="bg-gray-50 rounded-2xl p-6 mt-6 text-left space-y-3">
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">주문번호</span>
            <span className="font-mono font-medium">{order.order_no}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">결제금액</span>
            <span className="font-bold" style={{ color: '#968774' }}>{formatPrice(order.total_amount)}</span>
          </div>
          <div className="pt-2 border-t border-gray-200">
            {(order.order_items ?? []).map((item: any, i: number) => (
              <p key={i} className="text-sm text-gray-700">{item.goods_name} × {item.qty}</p>
            ))}
          </div>
        </div>
      )}

      <p className="text-gray-500 text-sm mt-6">주문 확인 알림톡이 발송되었습니다.</p>

      <div className="flex gap-3 mt-8 justify-center">
        <Link href="/mypage/orders" className="text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-opacity"
          style={{ backgroundColor: '#968774' }}>
          주문 내역 확인
        </Link>
        <Link href="/goods" className="border border-gray-200 text-gray-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50">
          계속 쇼핑하기
        </Link>
      </div>
    </div>
  )
}
