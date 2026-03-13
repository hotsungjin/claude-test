import { NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

// 휴면 회원 재활성화 API
export async function POST() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  // members에서 휴면 여부 확인
  const { data: member } = await supabase
    .from('members')
    .select('id, is_dormant')
    .eq('auth_id', user.id)
    .single()

  if (!member) return NextResponse.json({ error: '회원 정보 없음' }, { status: 404 })
  if (!member.is_dormant) return NextResponse.json({ message: '이미 활성 상태' })

  // 재활성화
  await supabase
    .from('members')
    .update({
      is_dormant: false,
      dormant_at: null,
      last_login_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    })
    .eq('id', member.id)

  // 로그
  await supabase.from('notification_logs').insert({
    member_id: member.id,
    type: 'dormant_reactivated',
    channel: 'system',
    receiver: user.email,
    message: '휴면 해제 (본인 로그인)',
    status: 'sent',
    sent_at: new Date().toISOString(),
  })

  return NextResponse.json({ success: true, message: '계정이 재활성화되었습니다.' })
}
