import { createClient } from '@/lib/supabase/server'
import { notFound, redirect } from 'next/navigation'
import { formatPrice, formatDateTime, ORDER_STATUS_LABEL } from '@/utils/format'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import OrderActionClient from './OrderActionClient'

const STATUS_STYLE: Record<string, { bg: string; color: string }> = {
  pending_payment: { bg: '#fff8e1', color: '#f59e0b' },
  paid:            { bg: '#e8f4fd', color: '#3b82f6' },
  preparing:       { bg: '#ede9fe', color: '#7c3aed' },
  shipped:         { bg: '#fce7f3', color: '#ec4899' },
  delivered:       { bg: '#d1fae5', color: '#059669' },
  confirmed:       { bg: '#d1fae5', color: '#059669' },
  cancelled:       { bg: '#fee2e2', color: '#ef4444' },
  returned:        { bg: '#f3f4f6', color: '#6b7280' },
}

export default async function OrderDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) notFound()

  const { data: order } = await supabase
    .from('orders')
    .select(`
      id, order_no, status, goods_amount, discount_amount, coupon_amount,
      mileage_used, shipping_amount, total_amount,
      recipient, phone, zipcode, address1, address2, delivery_memo,
      created_at, paid_at,
      order_items(id, goods_id, goods_name, option_name, qty, unit_price, total_price),
      deliveries(courier_name, tracking_no, delivery_companies(tracking_url))
    `)
    .eq('id', id)
    .eq('member_id', member.id)
    .single()

  if (!order) notFound()

  const s = STATUS_STYLE[order.status] ?? { bg: '#f3f4f6', color: '#6b7280' }
  const canReview = ['delivered', 'confirmed'].includes(order.status)
  const delivery = (order.deliveries ?? [])[0]
  const trackingUrl = delivery?.tracking_no && delivery?.delivery_companies?.tracking_url
    ? delivery.delivery_companies.tracking_url.replace('{no}', delivery.tracking_no)
    : null

  return (
    <div style={{ backgroundColor: '#f7f4f1', minHeight: '100vh' }}>
      {/* 헤더 */}
      <div className="bg-white flex items-center px-4 py-3 border-b" style={{ borderColor: '#ebebeb' }}>
        <Link href="/mypage/orders" className="mr-3">
          <ChevronLeft className="w-5 h-5" style={{ color: '#333' }} />
        </Link>
        <h1 className="text-[16px] font-bold" style={{ color: '#222' }}>주문 상세</h1>
      </div>

      {/* 상태 */}
      <div className="bg-white mt-2 px-4 py-5 text-center">
        <span className="text-[13px] px-4 py-1.5 rounded-full font-semibold"
          style={{ backgroundColor: s.bg, color: s.color }}>
          {ORDER_STATUS_LABEL[order.status] ?? order.status}
        </span>
        <p className="text-[11px] font-mono mt-2" style={{ color: '#bbb' }}>{order.order_no}</p>
        <p className="text-[11px] mt-1" style={{ color: '#aaa' }}>{formatDateTime(order.created_at)} 주문</p>
      </div>

      {/* 주문 상품 */}
      <div className="bg-white mt-2 px-4 py-4">
        <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>주문 상품</h2>
        <div className="space-y-3">
          {(order.order_items ?? []).map((item: any) => (
            <div key={item.id} className="flex items-center gap-3">
              <div className="flex-1 min-w-0">
                <p className="text-[13px] font-medium" style={{ color: '#333' }}>{item.goods_name}</p>
                {item.option_name && <p className="text-[11px] mt-0.5" style={{ color: '#888' }}>{item.option_name}</p>}
                <p className="text-[12px] mt-0.5" style={{ color: '#aaa' }}>× {item.qty}</p>
              </div>
              <p className="text-[14px] font-bold flex-shrink-0" style={{ color: '#333' }}>
                {formatPrice(item.total_price)}
              </p>
            </div>
          ))}
        </div>
      </div>

      {/* 배송 정보 */}
      <div className="bg-white mt-2 px-4 py-4">
        <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>배송 정보</h2>
        <div className="space-y-1.5 text-[13px]">
          <div className="flex gap-3"><span className="w-16 flex-shrink-0" style={{ color: '#aaa' }}>받는 분</span><span style={{ color: '#333' }}>{order.recipient}</span></div>
          <div className="flex gap-3"><span className="w-16 flex-shrink-0" style={{ color: '#aaa' }}>연락처</span><span style={{ color: '#333' }}>{order.phone}</span></div>
          <div className="flex gap-3"><span className="w-16 flex-shrink-0" style={{ color: '#aaa' }}>주소</span>
            <span style={{ color: '#333' }}>({order.zipcode}) {order.address1} {order.address2}</span>
          </div>
          {order.delivery_memo && (
            <div className="flex gap-3"><span className="w-16 flex-shrink-0" style={{ color: '#aaa' }}>배송 메모</span><span style={{ color: '#555' }}>{order.delivery_memo}</span></div>
          )}
        </div>
      </div>

      {/* 배송 조회 */}
      {delivery?.tracking_no && (
        <div className="bg-white mt-2 px-4 py-4">
          <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>배송 조회</h2>
          <div className="space-y-1.5 text-[13px]">
            <div className="flex gap-3">
              <span className="w-16 flex-shrink-0" style={{ color: '#aaa' }}>택배사</span>
              <span style={{ color: '#333' }}>{delivery.courier_name ?? '-'}</span>
            </div>
            <div className="flex gap-3">
              <span className="w-16 flex-shrink-0" style={{ color: '#aaa' }}>운송장</span>
              <span style={{ color: '#333' }}>{delivery.tracking_no}</span>
            </div>
          </div>
          {trackingUrl && (
            <a href={trackingUrl} target="_blank" rel="noopener noreferrer"
              className="mt-3 block w-full text-center py-2.5 rounded-xl text-[13px] font-medium"
              style={{ backgroundColor: '#f3f0ed', color: '#968774' }}>
              배송 조회하기 →
            </a>
          )}
        </div>
      )}

      {/* 결제 금액 */}
      <div className="bg-white mt-2 px-4 py-4">
        <h2 className="text-[14px] font-bold mb-3" style={{ color: '#333' }}>결제 금액</h2>
        <div className="space-y-2 text-[13px]">
          <div className="flex justify-between"><span style={{ color: '#888' }}>상품금액</span><span style={{ color: '#333' }}>{formatPrice(order.goods_amount)}</span></div>
          <div className="flex justify-between"><span style={{ color: '#888' }}>배송비</span><span style={{ color: '#333' }}>{order.shipping_amount === 0 ? '무료' : formatPrice(order.shipping_amount)}</span></div>
          {order.coupon_amount > 0 && <div className="flex justify-between" style={{ color: '#968774' }}><span>쿠폰 할인</span><span>-{formatPrice(order.coupon_amount)}</span></div>}
          {order.mileage_used > 0 && <div className="flex justify-between" style={{ color: '#968774' }}><span>포인트 할인</span><span>-{formatPrice(order.mileage_used)}</span></div>}
        </div>
        <div className="flex justify-between items-center mt-3 pt-3 border-t" style={{ borderColor: '#f0ece8' }}>
          <span className="text-[14px] font-bold" style={{ color: '#333' }}>최종 결제금액</span>
          <span className="text-[20px] font-bold" style={{ color: '#968774' }}>{formatPrice(order.total_amount)}</span>
        </div>
      </div>

      {/* 리뷰 버튼 */}
      {canReview && (
        <div className="px-4 mt-4">
          <Link href={`/mypage/orders/${order.id}/review`}
            className="block w-full text-center py-3 rounded-xl font-semibold text-[14px] border"
            style={{ borderColor: '#968774', color: '#968774' }}>
            리뷰 작성하기 (+100P)
          </Link>
        </div>
      )}

      {/* 취소/반품 버튼 */}
      <OrderActionClient orderId={order.id} status={order.status} />

      <div style={{ height: '32px' }} />
    </div>
  )
}
