import { NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'

// 매일 새벽 2시 실행 — 1년 이상 미접속 회원 휴면 처리
export async function GET(req: Request) {
  const authHeader = req.headers.get('authorization')
  if (authHeader !== `Bearer ${process.env.CRON_SECRET}`) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }

  const supabase = createAdminClient()
  const oneYearAgo = new Date(Date.now() - 365 * 24 * 3600000).toISOString()

  // 1년 이상 미접속 + 아직 휴면이 아닌 회원
  const { data: targets, error: fetchError } = await (supabase as any)
    .from('members')
    .select('id, email, name, last_login_at')
    .eq('is_active', true)
    .eq('is_dormant', false)
    .or(`last_login_at.is.null,last_login_at.lte.${oneYearAgo}`)
    .lte('created_at', oneYearAgo)
    .limit(500)

  if (fetchError) {
    return NextResponse.json({ error: fetchError.message }, { status: 500 })
  }

  if (!targets || targets.length === 0) {
    return NextResponse.json({ message: 'No dormant candidates', processed: 0 })
  }

  const ids = targets.map((m: any) => m.id)

  // 휴면 처리
  const { error: updateError } = await (supabase as any)
    .from('members')
    .update({
      is_dormant: true,
      dormant_at: new Date().toISOString(),
    })
    .in('id', ids)

  if (updateError) {
    return NextResponse.json({ error: updateError.message }, { status: 500 })
  }

  // 알림 로그 기록
  await (supabase as any).from('notification_logs').insert(
    targets.map((m: any) => ({
      member_id: m.id,
      type: 'dormant_notice',
      channel: 'system',
      receiver: m.email,
      message: `휴면 회원 전환: ${m.name} (마지막 접속: ${m.last_login_at ?? '없음'})`,
      status: 'sent',
      sent_at: new Date().toISOString(),
    }))
  )

  return NextResponse.json({ message: 'Dormant check completed', processed: ids.length })
}
