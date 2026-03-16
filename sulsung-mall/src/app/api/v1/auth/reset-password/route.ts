import { NextRequest, NextResponse } from 'next/server'
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/server'

const schema = z.object({
  phone: z.string().min(10),
  password: z.string().min(8),
  verificationId: z.number().optional(),
})

export async function POST(req: NextRequest) {
  try {
    const body = schema.parse(await req.json())
    const supabase = await createAdminClient() as any

    // TODO: 솔라피 계정 생성 후 SMS 인증 다시 활성화
    // 현재는 인증 없이 비밀번호 변경 허용

    // 회원 조회 (하이픈 없는 번호 + 하이픈 있는 번호 모두 시도)
    const cleanPhone = body.phone.replace(/[^0-9]/g, '')
    const formattedPhone = cleanPhone.length === 11
      ? `${cleanPhone.slice(0, 3)}-${cleanPhone.slice(3, 7)}-${cleanPhone.slice(7)}`
      : cleanPhone

    let { data: member } = await supabase
      .from('members')
      .select('auth_id, phone')
      .eq('phone', cleanPhone)
      .single()

    if (!member) {
      const { data: member2 } = await supabase
        .from('members')
        .select('auth_id, phone')
        .eq('phone', formattedPhone)
        .single()
      member = member2
    }

    if (!member || !member.auth_id) {
      console.log(`[reset-password] 회원 없음: phone=${cleanPhone}/${formattedPhone}, member=`, member)
      return NextResponse.json({ error: '등록되지 않은 휴대폰 번호입니다.' }, { status: 404 })
    }

    // auth 유저 존재 확인
    const { data: authUser, error: getUserError } = await supabase.auth.admin.getUserById(member.auth_id)
    if (getUserError || !authUser?.user) {
      console.error(`[reset-password] auth 유저 조회 실패: auth_id=${member.auth_id}`, getUserError)
      return NextResponse.json({ error: '계정 정보를 찾을 수 없습니다. 고객센터에 문의해주세요.' }, { status: 500 })
    }

    console.log(`[reset-password] auth 유저 확인: id=${authUser.user.id}, email=${authUser.user.email}`)

    // 비밀번호 변경
    const { error: updateError } = await supabase.auth.admin.updateUserById(
      member.auth_id,
      { password: body.password }
    )

    if (updateError) {
      console.error(`[reset-password] 비밀번호 변경 실패: auth_id=${member.auth_id}`, updateError)
      return NextResponse.json({ error: updateError.message }, { status: 500 })
    }

    // auth 이메일이 {phone}@sulsung.internal이 아니면 RPC로 직접 변경
    // (updateUserById의 email 변경은 확인 메일이 필요하므로 SECURITY DEFINER RPC 사용)
    const expectedEmail = `${cleanPhone}@sulsung.internal`
    if (authUser.user.email !== expectedEmail) {
      const { error: emailError } = await supabase.rpc('update_auth_email', {
        p_user_id: member.auth_id,
        p_new_email: expectedEmail,
      })
      if (emailError) {
        console.error(`[reset-password] auth 이메일 변경 실패:`, emailError)
      } else {
        console.log(`[reset-password] auth 이메일 변경: ${authUser.user.email} → ${expectedEmail}`)
      }
    }

    console.log(`[reset-password] 비밀번호 변경 성공: auth_id=${member.auth_id}, phone=${cleanPhone}`)

    return NextResponse.json({ success: true })
  } catch (err: any) {
    console.error('[reset-password] 예외 발생:', err)
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
