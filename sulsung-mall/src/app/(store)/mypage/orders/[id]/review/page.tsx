import { createClient } from '@/lib/supabase/server'
import { notFound, redirect } from 'next/navigation'
import ReviewFormClient from './ReviewFormClient'

export default async function OrderReviewPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) notFound()

  const { data: order } = await supabase
    .from('orders')
    .select(`
      id, order_no, status,
      order_items(id, goods_id, goods_name, qty, goods(thumbnail_url))
    `)
    .eq('id', id)
    .eq('member_id', member.id)
    .single()

  if (!order) notFound()
  if (!['delivered', 'confirmed'].includes(order.status)) {
    redirect('/mypage/orders')
  }

  // 이미 리뷰 작성된 아이템 확인
  const { data: existingReviews } = await supabase
    .from('reviews')
    .select('order_item_id')
    .eq('member_id', member.id)
    .in('order_item_id', (order.order_items ?? []).map((i: any) => i.id))

  const reviewedItemIds = new Set((existingReviews ?? []).map((r: any) => r.order_item_id))
  const items = (order.order_items ?? []).map((item: any) => ({
    ...item,
    thumbnail_url: item.goods?.thumbnail_url ?? null,
    reviewed: reviewedItemIds.has(item.id),
  }))

  return (
    <div style={{ backgroundColor: '#f7f4f1', minHeight: '100vh' }}>
      <div className="bg-white px-4 py-3 border-b" style={{ borderColor: '#ebebeb' }}>
        <h1 className="text-[16px] font-bold" style={{ color: '#222' }}>리뷰 작성</h1>
        <p className="text-[11px] mt-0.5" style={{ color: '#aaa' }}>주문번호 {order.order_no}</p>
      </div>
      <ReviewFormClient items={items} />
    </div>
  )
}
