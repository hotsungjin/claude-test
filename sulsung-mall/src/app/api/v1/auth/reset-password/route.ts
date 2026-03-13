import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/server'

const schema = z.object({
  phone: z.string().min(10),
  password: z.string().min(8),
  verificationId: z.number(),
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
      .eq('purpose', 'reset_password')
      .eq('verified', true)
      .single()

    if (!verification) {
      return NextResponse.json({ error: '휴대폰 인증이 완료되지 않았습니다.' }, { status: 400 })
    }

    // 회원 조회
    const { data: member } = await supabase
      .from('members')
      .select('auth_id')
      .eq('phone', body.phone)
      .single()

    if (!member || !member.auth_id) {
      return NextResponse.json({ error: '등록되지 않은 휴대폰 번호입니다.' }, { status: 404 })
    }

    // 비밀번호 변경
    const { error: updateError } = await supabase.auth.admin.updateUserById(
      member.auth_id,
      { password: body.password }
    )

    if (updateError) {
      return NextResponse.json({ error: updateError.message }, { status: 500 })
    }

    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
