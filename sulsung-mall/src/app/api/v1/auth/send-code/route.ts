import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/server'
import { generateCode, normalizePhone } from '@/lib/sms'
import { sendVerificationCode } from '@/lib/notification/solapi'

const schema = z.object({
  phone: z.string().min(10),
  purpose: z.enum(['signup', 'reset_password', 'change_phone']),
})

export async function POST(req: NextRequest) {
  try {
    const body = schema.parse(await req.json())
    const phone = normalizePhone(body.phone)
    if (!phone) {
      return NextResponse.json({ error: '올바른 휴대폰 번호를 입력해주세요.' }, { status: 400 })
    }

    const supabase = await createAdminClient()

    // 회원가입 시 이미 등록된 번호 체크
    if (body.purpose === 'signup') {
      const { data: existing } = await (supabase as any).from('members').select('id').eq('phone', phone).single()
      if (existing) {
        return NextResponse.json({ error: '이미 가입된 휴대폰 번호입니다.' }, { status: 409 })
      }
    }

    // 비밀번호 찾기 시 등록된 번호인지 확인
    if (body.purpose === 'reset_password') {
      const { data: existing } = await (supabase as any).from('members').select('id').eq('phone', phone).single()
      if (!existing) {
        return NextResponse.json({ error: '등록되지 않은 휴대폰 번호입니다.' }, { status: 404 })
      }
    }

    // 1분 이내 재발송 방지
    const { data: recent } = await (supabase as any)
      .from('sms_verifications')
      .select('id')
      .eq('phone', phone)
      .eq('purpose', body.purpose)
      .gt('created_at', new Date(Date.now() - 60 * 1000).toISOString())
      .limit(1)

    if (recent && recent.length > 0) {
      return NextResponse.json({ error: '1분 후에 다시 시도해주세요.' }, { status: 429 })
    }

    const code = generateCode()
    const expiresAt = new Date(Date.now() + 3 * 60 * 1000) // 3분

    // DB에 인증 코드 저장
    await (supabase as any).from('sms_verifications').insert({
      phone,
      code,
      purpose: body.purpose,
      expires_at: expiresAt.toISOString(),
    })

    // 알림톡 우선 발송 (실패 시 SMS 폴백)
    await sendVerificationCode({ phone, code })

    return NextResponse.json({ success: true, expiresAt: expiresAt.toISOString() })
  } catch (err: any) {
    console.error('send-code error:', err)
    return NextResponse.json({ error: err.message || '인증번호 발송에 실패했습니다.' }, { status: 500 })
  }
}
