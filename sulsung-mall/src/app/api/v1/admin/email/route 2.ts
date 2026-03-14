import { NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
import { sendBulkEmail } from '@/lib/email/resend'

export async function POST(req: Request) {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { subject, content, target, grades, memberIds } = await req.json()

  if (!subject || !content) {
    return NextResponse.json({ error: '제목과 내용을 입력해주세요.' }, { status: 400 })
  }

  let emails: string[] = []

  if (target === 'individual' && Array.isArray(memberIds) && memberIds.length > 0) {
    // 개별 선택
    const { data: members } = await supabase
      .from('members')
      .select('email')
      .in('id', memberIds)
      .not('email', 'is', null)
    emails = (members ?? []).map((m: any) => m.email).filter(Boolean)

  } else if (target === 'grade' && Array.isArray(grades) && grades.length > 0) {
    // 등급별
    const { data: members } = await supabase
      .from('members')
      .select('email')
      .in('grade', grades)
      .not('email', 'is', null)
      .limit(5000)
    emails = (members ?? []).map((m: any) => m.email).filter(Boolean)

  } else if (target === 'active') {
    // 최근 30일 내 주문한 활성 회원
    const thirtyDaysAgo = new Date(Date.now() - 30 * 86400000).toISOString()
    const { data: activeMembers } = await supabase
      .from('orders')
      .select('member_id')
      .gte('created_at', thirtyDaysAgo)
    const activeIds = [...new Set((activeMembers ?? []).map((o: any) => o.member_id))]
    if (activeIds.length === 0) {
      return NextResponse.json({ error: '대상 회원이 없습니다.', sent: 0, failed: 0 })
    }
    const { data: members } = await supabase
      .from('members')
      .select('email')
      .in('id', activeIds)
      .not('email', 'is', null)
    emails = (members ?? []).map((m: any) => m.email).filter(Boolean)

  } else if (target === 'dormant') {
    // 90일 이상 주문 없는 회원
    const ninetyDaysAgo = new Date(Date.now() - 90 * 86400000).toISOString()
    const { data: recentMembers } = await supabase
      .from('orders')
      .select('member_id')
      .gte('created_at', ninetyDaysAgo)
    const recentIds = [...new Set((recentMembers ?? []).map((o: any) => o.member_id))]
    let query = supabase.from('members').select('email').not('email', 'is', null)
    if (recentIds.length > 0) {
      query = query.not('id', 'in', `(${recentIds.join(',')})`)
    }
    const { data: members } = await query.limit(5000)
    emails = (members ?? []).map((m: any) => m.email).filter(Boolean)

  } else {
    // 전체 회원
    const { data: members } = await supabase
      .from('members')
      .select('email')
      .not('email', 'is', null)
      .limit(5000)
    emails = (members ?? []).map((m: any) => m.email).filter(Boolean)
  }

  if (emails.length === 0) {
    return NextResponse.json({ error: '발송 대상이 없습니다.', sent: 0, failed: 0 })
  }

  const results = await sendBulkEmail({ recipients: emails, subject, content })

  // 발송 이력 로그
  const targetLabel = target === 'grade' ? `grade:${grades?.join(',')}` :
    target === 'individual' ? `individual:${memberIds?.length}명` : target
  await supabase.from('notification_logs').insert({
    type: 'bulk_email',
    channel: 'email',
    receiver: `${emails.length}명`,
    message: `[${targetLabel}] ${subject}`,
    status: results.failed === 0 ? 'sent' : 'partial',
    sent_at: new Date().toISOString(),
  })

  return NextResponse.json({ success: true, ...results, total: emails.length })
}
