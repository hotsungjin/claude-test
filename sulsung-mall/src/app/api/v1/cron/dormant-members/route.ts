import { NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { sendDormantConverted, sendDormantWarning } from '@/lib/notification/solapi'

// 휴면 회원 자동 처리 Cron
// 1) 90일 미접속 → 휴면 전환
// 2) 80일 미접속 → 사전 경고 알림 (10일 전)
export async function GET(req: Request) {
  const authHeader = req.headers.get('authorization')
  if (authHeader !== `Bearer ${process.env.CRON_SECRET}`) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }

  const supabase = await createAdminClient() as any
  const now = new Date()
  const ninetyDaysAgo = new Date(now.getTime() - 90 * 86400000).toISOString()
  const eightyDaysAgo = new Date(now.getTime() - 80 * 86400000).toISOString()
  const eightyOneDaysAgo = new Date(now.getTime() - 81 * 86400000).toISOString()

  let dormantCount = 0
  let warnCount = 0

  // ── 1. 90일 이상 미접속 회원 → 휴면 전환 ──
  const { data: inactiveMembers } = await supabase
    .from('members')
    .select('id, name, email, phone, last_login_at')
    .eq('is_dormant', false)
    .eq('is_active', true)
    .lt('last_login_at', ninetyDaysAgo)
    .limit(200)

  for (const member of (inactiveMembers ?? [])) {
    // 휴면 전환
    await supabase
      .from('members')
      .update({
        is_dormant: true,
        dormant_at: now.toISOString(),
        updated_at: now.toISOString(),
      })
      .eq('id', member.id)

    // 휴면 전환 알림 (알림톡 우선, SMS 폴백)
    if (member.phone) {
      try {
        await sendDormantConverted({
          phone: member.phone,
          memberName: member.name ?? '고객',
        })
      } catch {}
    }

    // 로그 기록
    await supabase.from('notification_logs').insert({
      member_id: member.id,
      type: 'dormant_converted',
      channel: 'system',
      receiver: member.phone ?? member.email,
      message: '90일 미접속 휴면 전환',
      status: 'sent',
      sent_at: now.toISOString(),
    })

    dormantCount++
  }

  // ── 2. 80일 미접속 회원 → 사전 경고 알림 ──
  // 80~81일 사이 (하루 단위로 중복 방지)
  const { data: warningMembers } = await supabase
    .from('members')
    .select('id, name, email, phone, last_login_at')
    .eq('is_dormant', false)
    .eq('is_active', true)
    .lt('last_login_at', eightyDaysAgo)
    .gte('last_login_at', eightyOneDaysAgo)
    .limit(200)

  for (const member of (warningMembers ?? [])) {
    // 이미 경고 보냈는지 확인
    const { count } = await supabase
      .from('notification_logs')
      .select('*', { count: 'exact', head: true })
      .eq('member_id', member.id)
      .eq('type', 'dormant_warning')
      .gte('sent_at', new Date(now.getTime() - 15 * 86400000).toISOString())

    if ((count ?? 0) > 0) continue

    // 휴면 예고 알림 (알림톡 우선, SMS 폴백)
    if (member.phone) {
      try {
        await sendDormantWarning({
          phone: member.phone,
          memberName: member.name ?? '고객',
        })
      } catch {}
    }

    await supabase.from('notification_logs').insert({
      member_id: member.id,
      type: 'dormant_warning',
      channel: 'sms',
      receiver: member.phone ?? member.email,
      message: '휴면 전환 10일 전 사전 경고',
      status: 'sent',
      sent_at: now.toISOString(),
    })

    warnCount++
  }

  return NextResponse.json({
    message: 'Dormant member processing complete',
    dormant: dormantCount,
    warned: warnCount,
  })
}

