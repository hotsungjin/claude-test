import { createClient } from '@/lib/supabase/server'
import { formatPrice, formatDateTime, ORDER_STATUS_LABEL } from '@/utils/format'
import Link from 'next/link'
import MypageHeader from '@/components/store/mypage/MypageHeader'

const STATUS_STYLE: Record<string, { bg: string; color: string }> = {
  pending_payment: { bg: '#fff8e1', color: '#f59e0b' },
  paid: { bg: '#e8f4fd', color: '#3b82f6' },
  preparing: { bg: '#ede9fe', color: '#7c3aed' },
  shipped: { bg: '#fce7f3', color: '#ec4899' },
  delivered: { bg: '#d1fae5', color: '#059669' },
  confirmed: { bg: '#d1fae5', color: '#059669' },
  cancelled: { bg: '#fee2e2', color: '#ef4444' },
  returned: { bg: '#f3f4f6', color: '#6b7280' },
}

export default async function MyOrdersPage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()

  const { data: orders } = await supabase
    .from('orders')
    .select('id, order_no, total_amount, status, created_at, order_items(goods_name, qty, thumbnail_url)')
    .eq('member_id', (member as any).id)
    .order('created_at', { ascending: false })
    .limit(50)

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="주문내역" />
      {(!orders || orders.length === 0) ? (
        <div className="flex flex-col items-center justify-center px-8 text-center" style={{ height: '60vh' }}>
          <p className="text-[40px] mb-3">📦</p>
          <p className="text-[14px] font-medium" style={{ color: '#666' }}>주문 내역이 없습니다</p>
          <Link href="/goods" className="mt-4 px-6 py-2.5 rounded-full text-[13px] font-medium text-white"
            style={{ backgroundColor: '#968774' }}>
            쇼핑하러 가기
          </Link>
        </div>
      ) : (
        <div className="mt-2">
          {(orders as any[]).map((order: any, idx: number) => {
            const s = STATUS_STYLE[order.status] ?? { bg: '#f3f4f6', color: '#6b7280' }
            return (
              <div key={order.id} className="bg-white mb-2 px-4 py-4">
                {/* 날짜 & 상태 */}
                <div className="flex items-center justify-between mb-2.5">
                  <p className="text-[12px]" style={{ color: '#aaa' }}>{formatDateTime(order.created_at)}</p>
                  <span className="text-[11px] px-2.5 py-1 rounded-full font-medium"
                    style={{ backgroundColor: s.bg, color: s.color }}>
                    {ORDER_STATUS_LABEL[order.status] ?? order.status}
                  </span>
                </div>
                {/* 주문번호 */}
                <p className="text-[11px] font-mono mb-2" style={{ color: '#bbb' }}>{order.order_no}</p>
                {/* 상품 목록 */}
                <div className="space-y-1 mb-3">
                  {(order.order_items ?? []).slice(0, 2).map((item: any, i: number) => (
                    <p key={i} className="text-[13px]" style={{ color: '#444' }}>
                      {item.goods_name} <span style={{ color: '#aaa' }}>× {item.qty}</span>
                    </p>
                  ))}
                  {(order.order_items ?? []).length > 2 && (
                    <p className="text-[12px]" style={{ color: '#aaa' }}>외 {order.order_items.length - 2}개 상품</p>
                  )}
                </div>
                {/* 금액 & 버튼 */}
                <div className="flex items-center justify-between pt-3 border-t" style={{ borderColor: '#f3f0ed' }}>
                  <span className="text-[15px] font-bold" style={{ color: '#333' }}>{formatPrice(order.total_amount)}</span>
                  <div className="flex gap-1.5">
                    {order.status === 'delivered' && (
                      <Link href={`/mypage/orders/${order.id}/review`}
                        className="text-[12px] px-3 py-1.5 rounded-lg border"
                        style={{ borderColor: '#968774', color: '#968774' }}>
                        리뷰 작성
                      </Link>
                    )}
                    <Link href={`/mypage/orders/${order.id}`}
                      className="text-[12px] px-3 py-1.5 rounded-lg"
                      style={{ backgroundColor: '#f3f0ed', color: '#666' }}>
                      상세보기
                    </Link>
                  </div>
                </div>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
