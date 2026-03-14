import { NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
import { sendCartReminder } from '@/lib/notification/solapi'

export async function POST(req: Request) {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { memberId, memberName, phone, goodsName, itemCount } = await req.json()

  if (!phone || !memberId) {
    return NextResponse.json({ error: '필수 정보가 부족합니다.' }, { status: 400 })
  }

  try {
    await sendCartReminder({ phone, memberName, goodsName, itemCount })

    // 로그 기록
    await supabase.from('notification_logs').insert({
      member_id: memberId,
      type: 'cart_reminder',
      channel: 'sms',
      receiver: phone,
      message: `수동 장바구니 리마인드: ${goodsName}${itemCount > 1 ? ` 외 ${itemCount - 1}건` : ''}`,
      status: 'sent',
      sent_at: new Date().toISOString(),
    })

    return NextResponse.json({ success: true })
  } catch (e: any) {
    return NextResponse.json({ error: e.message ?? '발송 실패' }, { status: 500 })
  }
}
