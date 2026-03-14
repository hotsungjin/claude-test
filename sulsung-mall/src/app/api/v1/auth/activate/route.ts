import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/server'
import { normalizePhone } from '@/lib/sms'

const schema = z.object({
  phone: z.string().min(10),
  password: z.string().min(8),
  verificationId: z.number(),
})

export async function POST(req: NextRequest) {
  try {
    const body = schema.parse(await req.json())
    const phone = normalizePhone(body.phone)
    if (!phone) {
      return NextResponse.json({ error: '올바른 휴대폰 번호를 입력해주세요.' }, { status: 400 })
    }

    const supabase = await createAdminClient() as any

    // 1. SMS 인증 확인
    const { data: verification } = await supabase
      .from('sms_verifications')
      .select('*')
      .eq('id', body.verificationId)
      .eq('phone', phone)
      .eq('purpose', 'activate')
      .eq('verified', true)
      .single()

    if (!verification) {
      return NextResponse.json({ error: '휴대폰 인증이 완료되지 않았습니다.' }, { status: 400 })
    }

    // 2. 마이그레이션 회원 확인 (auth_id 없는 회원)
    const { data: member } = await supabase
      .from('members')
      .select('id, auth_id, name')
      .eq('phone', phone)
      .single()

    if (!member) {
      return NextResponse.json({ error: '등록되지 않은 회원입니다.' }, { status: 404 })
    }

    if (member.auth_id) {
      return NextResponse.json({ error: '이미 활성화된 계정입니다. 로그인해주세요.' }, { status: 409 })
    }

    // 3. Supabase Auth 계정 생성
    const email = `${phone}@sulsung.internal`
    const { data: authData, error: authError } = await supabase.auth.admin.createUser({
      email,
      password: body.password,
      email_confirm: true,
    })

    if (authError) {
      return NextResponse.json({ error: authError.message }, { status: 500 })
    }

    // 4. members 테이블에 auth_id 연결
    const { error: updateError } = await supabase
      .from('members')
      .update({ auth_id: authData.user.id })
      .eq('id', member.id)

    if (updateError) {
      // 롤백: auth user 삭제
      await supabase.auth.admin.deleteUser(authData.user.id)
      return NextResponse.json({ error: updateError.message }, { status: 500 })
    }

    return NextResponse.json({ success: true, name: member.name })
  } catch (err: any) {
    console.error('activate error:', err)
    return NextResponse.json({ error: err.message || '계정 활성화에 실패했습니다.' }, { status: 500 })
  }
}
