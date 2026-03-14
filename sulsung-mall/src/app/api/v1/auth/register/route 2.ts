import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/server'

const schema = z.object({
  phone: z.string().min(10),
  password: z.string().min(8),
  name: z.string().min(1),
  verificationId: z.number(),
  marketingSms: z.boolean().default(false),
  marketingKakao: z.boolean().default(false),
})

export async function POST(req: NextRequest) {
  try {
    const body = schema.parse(await req.json())
    const supabase = await createAdminClient() as any

    // 인증 완료 여부 확인
    const { data: verification } = await supabase
      .from('sms_verifications')
      .select('*')
      .eq('id', body.verificationId)
      .eq('phone', body.phone)
      .eq('purpose', 'signup')
      .eq('verified', true)
      .single()

    if (!verification) {
      return NextResponse.json({ error: '휴대폰 인증이 완료되지 않았습니다.' }, { status: 400 })
    }

    // 중복 가입 체크
    const { data: existing } = await supabase
      .from('members')
      .select('id')
      .eq('phone', body.phone)
      .single()

    if (existing) {
      return NextResponse.json({ error: '이미 가입된 휴대폰 번호입니다.' }, { status: 409 })
    }

    // Supabase Auth 계정 생성 (phone@sulsung.internal)
    const email = `${body.phone}@sulsung.internal`
    const { data: authData, error: authError } = await supabase.auth.admin.createUser({
      email,
      password: body.password,
      email_confirm: true,
    })

    if (authError) {
      return NextResponse.json({ error: authError.message }, { status: 500 })
    }

    // members 테이블에 프로필 저장
    const { error: memberError } = await supabase.from('members').insert({
      auth_id: authData.user.id,
      phone: body.phone,
      name: body.name,
      grade: '일반',
      marketing_sms: body.marketingSms,
      marketing_kakao: body.marketingKakao,
    })

    if (memberError) {
      // 롤백: auth user 삭제
      await supabase.auth.admin.deleteUser(authData.user.id)
      return NextResponse.json({ error: memberError.message }, { status: 500 })
    }

    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
