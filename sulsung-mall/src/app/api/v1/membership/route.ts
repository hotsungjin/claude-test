import { NextRequest, NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'

// 멤버십 상태 조회
export async function GET() {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ subscription: null, plans: [] })

  const { data: member } = await supabase
    .from('members').select('id, grade').eq('id', memberId).single()

  const [{ data: subscription }, { data: plans }] = await Promise.all([
    member
      ? supabase
          .from('membership_subscriptions')
          .select('*, membership_plans(name, interval, price)')
          .eq('member_id', memberId)
          .eq('status', 'active')
          .order('created_at', { ascending: false })
          .limit(1)
          .single()
      : { data: null },
    supabase
      .from('membership_plans')
      .select('id, name, interval, price, duration_days, description')
      .eq('is_active', true)
      .order('price'),
  ])

  return NextResponse.json({
    subscription: subscription ?? null,
    grade: member?.grade ?? 'bronze',
    plans: plans ?? [],
  })
}

// 멤버십 가입 (결제 모듈 연동 전 임시 — 바로 활성화)
export async function POST(req: NextRequest) {
  try {
    const { planId } = await req.json()
    if (!planId) return NextResponse.json({ error: 'planId 필요' }, { status: 400 })

    const { supabase, memberId } = await getAuthMember()
    if (!memberId) return NextResponse.json({ error: '로그인이 필요합니다.' }, { status: 401 })

    // 이미 활성 구독이 있는지 확인
    const { data: existing } = await supabase
      .from('membership_subscriptions')
      .select('id')
      .eq('member_id', memberId)
      .eq('status', 'active')
      .limit(1)
      .single()

    if (existing) {
      return NextResponse.json({ error: '이미 활성 멤버십이 있습니다.' }, { status: 400 })
    }

    // 플랜 정보 조회
    const { data: plan } = await supabase
      .from('membership_plans').select('*').eq('id', planId).single()
    if (!plan) return NextResponse.json({ error: '유효하지 않은 플랜' }, { status: 400 })

    // 구독 생성
    const expiresAt = new Date()
    expiresAt.setDate(expiresAt.getDate() + plan.duration_days)

    await supabase.from('membership_subscriptions').insert({
      member_id: memberId,
      plan_id: planId,
      status: 'active',
      expires_at: expiresAt.toISOString(),
    })

    // 회원 등급을 silver로 변경
    await supabase.from('members').update({ grade: 'silver' }).eq('id', memberId)

    return NextResponse.json({ success: true, expiresAt: expiresAt.toISOString() })
  } catch (err: any) {
    return NextResponse.json({ error: err.message ?? '가입 처리 실패' }, { status: 500 })
  }
}

// 멤버십 해지
export async function DELETE() {
  try {
    const { supabase, memberId } = await getAuthMember()
    if (!memberId) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

    // 활성 구독 해지
    await supabase
      .from('membership_subscriptions')
      .update({ status: 'cancelled', cancelled_at: new Date().toISOString() })
      .eq('member_id', memberId)
      .eq('status', 'active')

    // 등급을 bronze로 변경
    await supabase.from('members').update({ grade: 'bronze' }).eq('id', memberId)

    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message ?? '해지 처리 실패' }, { status: 500 })
  }
}
