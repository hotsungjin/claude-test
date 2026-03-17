import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { getAdminUser } from '@/lib/admin-auth'
import { createClient } from '@/lib/supabase/server'
import { sendAlimtalk, sendSms } from '@/lib/notification/solapi'

const sendSchema = z.object({
  target: z.enum(['all', 'selected', 'single']),
  phone: z.string().optional(),
  memberIds: z.array(z.string()).optional(),
  message: z.string().min(1),
  channel: z.enum(['alimtalk_sms', 'sms_only']),
})

export async function POST(req: NextRequest) {
  try {
    const admin = await getAdminUser()
    if (!admin) return NextResponse.json({ error: '관리자 권한이 필요합니다.' }, { status: 403 })

    const supabase = await createClient() as any
    const body = sendSchema.parse(await req.json())

    let phones: string[] = []

    if (body.target === 'single') {
      if (!body.phone) return NextResponse.json({ error: '전화번호를 입력해주세요.' }, { status: 400 })
      phones = [body.phone.replace(/[^0-9]/g, '')]
    } else if (body.target === 'selected') {
      if (!body.memberIds?.length) return NextResponse.json({ error: '대상을 선택해주세요.' }, { status: 400 })
      const { data: members } = await supabase
        .from('members')
        .select('phone')
        .in('id', body.memberIds)
      phones = (members ?? []).map((m: any) => m.phone?.replace(/[^0-9]/g, '')).filter(Boolean)
    } else {
      const { data: members } = await supabase
        .from('members')
        .select('phone')
        .eq('status', 'active')
      phones = (members ?? []).map((m: any) => m.phone?.replace(/[^0-9]/g, '')).filter(Boolean)
    }

    if (phones.length === 0) {
      return NextResponse.json({ error: '발송 가능한 대상이 없습니다.' }, { status: 400 })
    }

    let successCount = 0
    let failCount = 0
    const errors: string[] = []

    for (const phone of phones) {
      try {
        if (body.channel === 'alimtalk_sms' && process.env.SOLAPI_PFID) {
          await sendAlimtalk({
            to: phone,
            templateId: 'ADMIN_NOTICE',
            variables: { '#{내용}': body.message },
          })
        } else {
          await sendSms({ to: phone, text: `[설성목장몰] ${body.message}` })
        }
        successCount++

        await supabase.from('notification_logs').insert({
          type: 'admin_manual',
          channel: body.channel === 'alimtalk_sms' ? 'kakao' : 'sms',
          receiver: phone,
          message: body.message,
          status: 'sent',
          sent_at: new Date().toISOString(),
        })
      } catch (err: any) {
        failCount++
        errors.push(`${phone}: ${err.message}`)

        await supabase.from('notification_logs').insert({
          type: 'admin_manual',
          channel: body.channel === 'alimtalk_sms' ? 'kakao' : 'sms',
          receiver: phone,
          message: body.message,
          status: 'failed',
          error_msg: err.message,
          sent_at: new Date().toISOString(),
        })
      }
    }

    return NextResponse.json({
      success: true,
      total: phones.length,
      successCount,
      failCount,
      errors: errors.slice(0, 10),
    })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
