import { NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { sendSms } from '@/lib/notification/solapi'
import { sendEmail } from '@/lib/email/resend'

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

    // 휴면 전환 알림
    if (member.phone) {
      try {
        await sendSms({
          to: member.phone,
          text: `[설성목장몰] ${member.name ?? '고객'}님, 90일간 미접속으로 계정이 휴면 처리되었습니다. 로그인하시면 바로 정상 이용 가능합니다.\n\n👉 ${process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'}/auth/login`,
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

    if (member.email) {
      try {
        await sendEmail({
          to: member.email,
          subject: '[설성목장몰] 10일 후 계정이 휴면 처리됩니다',
          html: dormantWarningHtml(member.name ?? '고객'),
        })
      } catch {}
    }

    if (member.phone) {
      try {
        await sendSms({
          to: member.phone,
          text: `[설성목장몰] ${member.name ?? '고객'}님, 10일 후 장기 미접속으로 계정이 휴면 처리됩니다. 지금 로그인하시면 유지됩니다!\n\n👉 ${process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'}/auth/login`,
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

function dormantWarningHtml(name: string) {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'
  return `
<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,sans-serif">
<div style="max-width:600px;margin:0 auto;padding:20px">
  <div style="background:#968774;padding:20px 24px;border-radius:12px 12px 0 0">
    <h1 style="margin:0;color:#fff;font-size:18px">설성목장몰</h1>
  </div>
  <div style="background:#fff;padding:32px 24px;border-radius:0 0 12px 12px">
    <h2 style="margin:0 0 16px;color:#333;font-size:20px">${name}님, 오랜만이에요!</h2>
    <p style="color:#666;line-height:1.6;margin:0 0 24px">
      마지막 방문 이후 80일이 지났습니다.<br>
      <strong style="color:#e84a3b">10일 후</strong> 장기 미접속으로 계정이 휴면 처리됩니다.
    </p>
    <div style="background:#fef3cd;border-radius:8px;padding:16px;margin-bottom:24px;font-size:14px;color:#856404">
      휴면 처리되면 적립된 마일리지와 쿠폰을 사용할 수 없습니다.<br>
      로그인하시면 계정이 유지됩니다.
    </div>
    <a href="${siteUrl}/auth/login" style="display:block;text-align:center;background:#968774;color:#fff;padding:14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">
      지금 로그인하기
    </a>
  </div>
</div>
</body></html>`
}
