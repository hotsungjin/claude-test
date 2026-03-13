import { NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { sendCartReminder } from '@/lib/notification/solapi'

// Vercel Cron 또는 외부 스케줄러로 호출
// 헤더에 CRON_SECRET 필요
export async function GET(req: Request) {
  const authHeader = req.headers.get('authorization')
  if (authHeader !== `Bearer ${process.env.CRON_SECRET}`) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }

  const supabase = await createAdminClient() as any

  // 설정: 장바구니 리마인드 활성화 여부 & 시간
  const { data: config } = await supabase
    .from('site_configs')
    .select('value')
    .eq('key', 'cart_reminder')
    .single()

  const settings = (config?.value as any) ?? { enabled: true, hours: 24, max_per_day: 100 }
  if (!settings.enabled) {
    return NextResponse.json({ message: 'Cart reminder disabled', sent: 0 })
  }

  const hoursAgo = new Date(Date.now() - settings.hours * 3600000).toISOString()
  const twoDaysAgo = new Date(Date.now() - 48 * 3600000).toISOString()

  // 조건: N시간 전에 장바구니에 담았고, 아직 주문하지 않은 회원
  // cart_reminders에 이미 발송된 적 없는 건만
  const { data: abandonedCarts } = await supabase
    .from('carts')
    .select(`
      id, member_id, goods_id, option_id, qty, created_at,
      members!inner(id, name, phone),
      goods!inner(id, name, status)
    `)
    .not('member_id', 'is', null)
    .lte('created_at', hoursAgo)
    .gte('created_at', twoDaysAgo)
    .eq('goods.status', 'active')
    .order('created_at', { ascending: false })
    .limit(500)

  if (!abandonedCarts || abandonedCarts.length === 0) {
    return NextResponse.json({ message: 'No abandoned carts found', sent: 0 })
  }

  // 회원별로 그룹핑
  const memberCarts = new Map<string, { member: any; items: any[] }>()
  for (const cart of abandonedCarts) {
    const memberId = cart.member_id
    if (!memberCarts.has(memberId)) {
      memberCarts.set(memberId, { member: cart.members, items: [] })
    }
    memberCarts.get(memberId)!.items.push(cart)
  }

  // 이미 리마인드 발송한 회원 제외 (24시간 이내)
  const oneDayAgo = new Date(Date.now() - 24 * 3600000).toISOString()
  const { data: recentReminders } = await supabase
    .from('cart_reminders')
    .select('member_id')
    .gte('reminded_at', oneDayAgo)

  const recentlyReminded = new Set((recentReminders ?? []).map((r: any) => r.member_id))

  let sentCount = 0
  const maxPerDay = settings.max_per_day ?? 100

  for (const [memberId, { member, items }] of memberCarts) {
    if (sentCount >= maxPerDay) break
    if (recentlyReminded.has(memberId)) continue
    if (!member?.phone) continue

    // 최근 주문 확인 - 최근 24시간 내 주문이 있으면 스킵
    const { count: recentOrderCount } = await supabase
      .from('orders')
      .select('*', { count: 'exact', head: true })
      .eq('member_id', memberId)
      .gte('created_at', oneDayAgo)

    if ((recentOrderCount ?? 0) > 0) continue

    try {
      await sendCartReminder({
        phone: member.phone,
        memberName: member.name ?? '고객',
        goodsName: items[0].goods?.name ?? '상품',
        itemCount: items.length,
      })

      // cart_reminders에 기록
      for (const item of items) {
        await supabase.from('cart_reminders').upsert({
          member_id: memberId,
          goods_id: item.goods_id,
          option_id: item.option_id,
          qty: item.qty,
          reminded_at: new Date().toISOString(),
        }, { onConflict: 'member_id,goods_id' })
      }

      // 알림 로그
      await supabase.from('notification_logs').insert({
        member_id: memberId,
        type: 'cart_reminder',
        channel: 'sms',
        receiver: member.phone,
        message: `장바구니 리마인드: ${items[0].goods?.name} 외 ${items.length - 1}건`,
        status: 'sent',
        sent_at: new Date().toISOString(),
      })

      sentCount++
    } catch (e) {
      console.error(`Cart reminder failed for member ${memberId}:`, e)
    }
  }

  return NextResponse.json({ message: 'Cart reminders processed', sent: sentCount })
}
