import { MEMBERSHIP_GOODS_SLUG } from '@/constants'

/**
 * 주문 상품 중 멤버십 상품이 포함되어 있는지 확인하고, 포함 시 멤버십 활성화
 * - 결제 완료 시 (payment/confirm, webhook/toss) 호출
 */
export async function activateMembershipIfNeeded(
  supabase: any,
  orderId: string,
  memberId: string,
) {
  // 1. 주문 상품에서 멤버십 상품 확인
  const { data: orderItems } = await supabase
    .from('order_items')
    .select('goods_id, unit_price')
    .eq('order_id', orderId)

  if (!orderItems || orderItems.length === 0) return

  // 멤버십 상품 goods_id 조회
  const { data: membershipGoods } = await supabase
    .from('goods')
    .select('id')
    .eq('slug', MEMBERSHIP_GOODS_SLUG)
    .single()

  if (!membershipGoods) return

  const membershipItem = orderItems.find((item: any) => item.goods_id === membershipGoods.id)
  if (!membershipItem) return

  // 2. 결제 금액으로 플랜 매칭 (가격이 일치하는 활성 플랜)
  const { data: plan } = await supabase
    .from('membership_plans')
    .select('id, duration_days')
    .eq('is_active', true)
    .eq('price', membershipItem.unit_price)
    .single()

  // 가격 매칭 실패 시 첫 번째 활성 플랜 사용
  const targetPlan = plan ?? (await supabase
    .from('membership_plans')
    .select('id, duration_days')
    .eq('is_active', true)
    .order('price')
    .limit(1)
    .single()
  ).data

  if (!targetPlan) {
    console.error('[Membership] 활성 멤버십 플랜 없음')
    return
  }

  // 3. 기존 활성 구독 확인 → 연장 or 신규 생성
  const { data: existing } = await supabase
    .from('membership_subscriptions')
    .select('id, expires_at')
    .eq('member_id', memberId)
    .eq('status', 'active')
    .order('expires_at', { ascending: false })
    .limit(1)
    .single()

  const now = new Date()
  let expiresAt: Date

  if (existing) {
    // 기존 만료일부터 연장
    const currentExpiry = new Date(existing.expires_at)
    const base = currentExpiry > now ? currentExpiry : now
    expiresAt = new Date(base)
    expiresAt.setDate(expiresAt.getDate() + targetPlan.duration_days)

    await supabase
      .from('membership_subscriptions')
      .update({
        plan_id: targetPlan.id,
        expires_at: expiresAt.toISOString(),
      })
      .eq('id', existing.id)
  } else {
    // 신규 구독 생성
    expiresAt = new Date(now)
    expiresAt.setDate(expiresAt.getDate() + targetPlan.duration_days)

    await supabase.from('membership_subscriptions').insert({
      member_id: memberId,
      plan_id: targetPlan.id,
      status: 'active',
      expires_at: expiresAt.toISOString(),
    })
  }

  // 4. 회원 등급 silver로 변경
  await supabase.from('members').update({ grade: 'silver' }).eq('id', memberId)

  // 5. 주문에 멤버십 활성화 기록 (order_status_logs 메모)
  await supabase.from('order_status_logs').insert({
    order_id: orderId,
    from_status: 'paid',
    to_status: 'paid',
    memo: `멤버십 활성화 (만료: ${expiresAt.toLocaleDateString('ko-KR')})`,
    changed_by: 'system',
  })

  console.log(`[Membership] 멤버십 활성화: member=${memberId}, expires=${expiresAt.toISOString()}`)
}

/**
 * 멤버십 상품 주문 취소 시 멤버십 해지
 */
export async function cancelMembershipIfNeeded(
  supabase: any,
  orderId: string,
  memberId: string,
) {
  // 주문 상품에서 멤버십 상품 확인
  const { data: orderItems } = await supabase
    .from('order_items')
    .select('goods_id')
    .eq('order_id', orderId)

  if (!orderItems || orderItems.length === 0) return

  const { data: membershipGoods } = await supabase
    .from('goods')
    .select('id')
    .eq('slug', MEMBERSHIP_GOODS_SLUG)
    .single()

  if (!membershipGoods) return

  const hasMembership = orderItems.some((item: any) => item.goods_id === membershipGoods.id)
  if (!hasMembership) return

  // 활성 구독 해지
  await supabase
    .from('membership_subscriptions')
    .update({ status: 'cancelled', cancelled_at: new Date().toISOString() })
    .eq('member_id', memberId)
    .eq('status', 'active')

  // 등급 bronze로 변경
  await supabase.from('members').update({ grade: 'bronze' }).eq('id', memberId)

  console.log(`[Membership] 멤버십 해지 (주문 취소): member=${memberId}, order=${orderId}`)
}
