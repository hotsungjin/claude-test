import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/server'
import { normalizePhone } from '@/lib/sms'

const schema = z.object({
  phone: z.string().min(10),
  code: z.string().length(6),
  purpose: z.enum(['signup', 'reset_password', 'change_phone', 'activate']),
})

export async function POST(req: NextRequest) {
  try {
    const body = schema.parse(await req.json())
    const phone = normalizePhone(body.phone)
    if (!phone) {
      return NextResponse.json({ error: '올바른 휴대폰 번호를 입력해주세요.' }, { status: 400 })
    }

    const supabase = await createAdminClient()

    // 가장 최근 미인증 코드 조회
    const { data: verification } = await (supabase as any)
      .from('sms_verifications')
      .select('*')
      .eq('phone', phone)
      .eq('purpose', body.purpose)
      .eq('verified', false)
      .gt('expires_at', new Date().toISOString())
      .order('created_at', { ascending: false })
      .limit(1)
      .single()

    if (!verification) {
      return NextResponse.json({ error: '인증번호가 만료되었습니다. 다시 요청해주세요.' }, { status: 400 })
    }

    // 시도 횟수 제한 (5회)
    if (verification.attempts >= 5) {
      return NextResponse.json({ error: '인증 시도 횟수를 초과했습니다. 다시 요청해주세요.' }, { status: 429 })
    }

    // 시도 횟수 증가
    await (supabase as any)
      .from('sms_verifications')
      .update({ attempts: verification.attempts + 1 })
      .eq('id', verification.id)

    // 코드 검증
    if (verification.code !== body.code) {
      return NextResponse.json({ error: '인증번호가 일치하지 않습니다.' }, { status: 400 })
    }

    // 인증 완료 처리
    await (supabase as any)
      .from('sms_verifications')
      .update({ verified: true })
      .eq('id', verification.id)

    return NextResponse.json({ success: true, verificationId: verification.id })
  } catch (err: any) {
    console.error('verify-code error:', err)
    return NextResponse.json({ error: err.message || '인증에 실패했습니다.' }, { status: 500 })
  }
}
