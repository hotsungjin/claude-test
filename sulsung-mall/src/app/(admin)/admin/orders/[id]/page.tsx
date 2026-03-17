import { createAdminClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import { ChevronRight } from 'lucide-react'
import { formatPrice, formatDateTime, ORDER_STATUS_LABEL } from '@/utils/format'
import OrderStatusClient from './OrderStatusClient'

const STATUS_COLOR: Record<string, string> = {
  pending_payment: 'bg-yellow-100 text-yellow-700',
  paid:            'bg-blue-100 text-blue-700',
  preparing:       'bg-indigo-100 text-indigo-700',
  shipped:         'bg-purple-100 text-purple-700',
  delivered:       'bg-blue-100 text-blue-700',
  confirmed:       'bg-blue-200 text-blue-800',
  cancelled:       'bg-gray-100 text-gray-500',
  returned:        'bg-gray-100 text-gray-500',
}

export default async function AdminOrderDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient() as any

  const [{ data: order }, { data: items }, { data: logs }] = await Promise.all([
    supabase.from('orders').select(`*, members(name, email, phone)`).eq('id', id).single(),
    supabase.from('order_items').select(`*, goods(name, thumbnail_url)`).eq('order_id', id),
    supabase.from('order_status_logs').select('*').eq('order_id', id).order('created_at', { ascending: false }),
  ])

  if (!order) notFound()

  return (
    <div>
      <nav className="flex items-center gap-1 text-sm text-gray-500 mb-6">
        <Link href="/admin/orders" className="hover:text-blue-700">주문 관리</Link>
        <ChevronRight className="w-4 h-4" />
        <span className="text-gray-900 font-medium">{order.order_no}</span>
      </nav>

      <div className="flex items-start justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{order.order_no}</h1>
          <p className="text-sm text-gray-500 mt-1">{formatDateTime(order.created_at)}</p>
        </div>
        <span className={`text-sm px-3 py-1.5 rounded-full font-medium ${STATUS_COLOR[order.status] ?? 'bg-gray-100 text-gray-500'}`}>
          {ORDER_STATUS_LABEL[order.status] ?? order.status}
        </span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* 좌측: 주문 정보 */}
        <div className="lg:col-span-2 space-y-6">
          {/* 주문 상품 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">주문 상품</h2>
            <div className="space-y-4">
              {(items ?? []).map((item: any) => (
                <div key={item.id} className="flex gap-4">
                  {item.goods?.thumbnail_url
                    ? <img src={item.goods.thumbnail_url} alt="" className="w-16 h-16 rounded-lg object-cover flex-shrink-0" />
                    : <div className="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0" />}
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 text-sm">{item.goods_name}</p>
                    {item.option_name && <p className="text-xs text-gray-500 mt-0.5">{item.option_name}</p>}
                    <p className="text-sm text-gray-700 mt-1">
                      {formatPrice(item.unit_price)} × {item.qty}개
                      = <strong>{formatPrice(item.total_price)}</strong>
                    </p>
                  </div>
                </div>
              ))}
            </div>
            <div className="border-t border-gray-100 mt-4 pt-4 space-y-2 text-sm">
              <div className="flex justify-between text-gray-600">
                <span>상품 합계</span><span>{formatPrice(order.goods_amount)}</span>
              </div>
              {order.discount_amount > 0 && (
                <div className="flex justify-between text-red-600">
                  <span>할인</span><span>-{formatPrice(order.discount_amount)}</span>
                </div>
              )}
              {order.coupon_amount > 0 && (
                <div className="flex justify-between text-red-600">
                  <span>쿠폰 할인</span><span>-{formatPrice(order.coupon_amount)}</span>
                </div>
              )}
              {order.mileage_used > 0 && (
                <div className="flex justify-between text-red-600">
                  <span>포인트 사용</span><span>-{formatPrice(order.mileage_used)}</span>
                </div>
              )}
              <div className="flex justify-between text-gray-600">
                <span>배송비</span><span>{order.shipping_amount > 0 ? formatPrice(order.shipping_amount) : '무료'}</span>
              </div>
              <div className="flex justify-between font-bold text-gray-900 text-base pt-2 border-t border-gray-100">
                <span>최종 결제금액</span><span>{formatPrice(order.total_amount)}</span>
              </div>
            </div>
          </section>

          {/* 배송지 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">배송지 정보</h2>
            <dl className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">수령인</dt>
                <dd className="font-medium text-gray-900">{order.recipient}</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">연락처</dt>
                <dd className="font-medium text-gray-900">{order.phone}</dd>
              </div>
              <div className="col-span-2">
                <dt className="text-gray-500 text-xs mb-0.5">주소</dt>
                <dd className="font-medium text-gray-900">[{order.zipcode}] {order.address1} {order.address2}</dd>
              </div>
              {order.delivery_memo && (
                <div className="col-span-2">
                  <dt className="text-gray-500 text-xs mb-0.5">배송 요청사항</dt>
                  <dd className="text-gray-700">{order.delivery_memo}</dd>
                </div>
              )}
            </dl>
          </section>

          {/* 상태 이력 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">상태 이력</h2>
            {(logs ?? []).length > 0 ? (
              <ol className="relative border-l border-gray-200 space-y-4 pl-4">
                {(logs ?? []).map((log: any) => (
                  <li key={log.id} className="relative">
                    <div className="absolute -left-[17px] top-1 w-2 h-2 bg-blue-500 rounded-full" />
                    <p className="text-sm font-medium text-gray-800">
                      {ORDER_STATUS_LABEL[log.from_status] ?? log.from_status}
                      {' → '}
                      {ORDER_STATUS_LABEL[log.to_status] ?? log.to_status}
                    </p>
                    <p className="text-xs text-gray-400 mt-0.5">
                      {formatDateTime(log.created_at)}
                      {log.changed_by === 'admin' ? ' (관리자)' : ''}
                    </p>
                    {log.memo && <p className="text-xs text-gray-600 mt-1">{log.memo}</p>}
                  </li>
                ))}
              </ol>
            ) : (
              <p className="text-sm text-gray-400">이력 없음</p>
            )}
          </section>
        </div>

        {/* 우측: 주문자/상태관리 */}
        <div className="space-y-6">
          {/* 주문자 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">주문자 정보</h2>
            <dl className="space-y-3 text-sm">
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">이름</dt>
                <dd className="font-medium text-gray-900">{order.members?.name ?? order.guest_name ?? '-'}</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">이메일</dt>
                <dd className="text-gray-700">{order.members?.email ?? order.guest_email ?? '-'}</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">연락처</dt>
                <dd className="text-gray-700">{order.members?.phone ?? order.guest_phone ?? '-'}</dd>
              </div>
              {order.paid_at && (
                <div>
                  <dt className="text-gray-500 text-xs mb-0.5">결제일시</dt>
                  <dd className="text-gray-700">{formatDateTime(order.paid_at)}</dd>
                </div>
              )}
            </dl>
          </section>

          {/* 상태 관리 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">상태 관리</h2>
            <OrderStatusClient orderId={order.id} currentStatus={order.status} adminMemo={order.admin_memo} />
          </section>
        </div>
      </div>
    </div>
  )
}
