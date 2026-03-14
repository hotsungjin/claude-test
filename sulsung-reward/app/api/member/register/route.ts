import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@supabase/supabase-js'

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!
)

// 고유 추천 코드 생성 (6자리 영문+숫자)
function generateReferralCode(): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  return Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}

export async function POST(req: NextRequest) {
  try {
    const { member_id, referral_code } = await req.json()

    if (!member_id) {
      return NextResponse.json({ error: '회원 ID 필요' }, { status: 400 })
    }

    // 이미 등록된 회원인지 확인
    const { data: existing } = await supabase
      .from('members')
      .select('id')
      .eq('id', member_id)
      .single()

    if (existing) {
      return NextResponse.json({ error: '이미 등록된 회원' }, { status: 409 })
    }

    // 고유 추천 코드 생성 (중복 시 재시도)
    let newCode = ''
    for (let i = 0; i < 5; i++) {
      const candidate = generateReferralCode()
      const { data } = await supabase
        .from('members')
        .select('id')
        .eq('referral_code', candidate)
        .single()
      if (!data) { newCode = candidate; break }
    }

    if (!newCode) {
      return NextResponse.json({ error: '코드 생성 실패, 재시도 필요' }, { status: 500 })
    }

    // 추천인 조회
    let referrerId: string | null = null
    if (referral_code) {
      const { data: referrer } = await supabase
        .from('members')
        .select('id')
        .eq('referral_code', referral_code)
        .single()

      if (!referrer) {
        return NextResponse.json({ error: '유효하지 않은 추천 코드' }, { status: 400 })
      }
      referrerId = referrer.id
    }

    // 회원 등록
    const { error: memberError } = await supabase.from('members').insert({
      id: member_id,
      referral_code: newCode,
      referred_by: referrerId,
    })

    if (memberError) throw memberError

    // 추천인이 있으면 트리 등록
    if (referrerId) {
      await buildReferralTree(member_id, referrerId)
    }

    // 포인트 잔액 초기화
    await supabase.from('point_balance').insert({
      member_id,
      total_earned: 0,
      total_used: 0,
      balance: 0,
    })

    return NextResponse.json({
      message: '회원 등록 완료',
      referral_code: newCode,
    }, { status: 201 })

  } catch (error) {
    console.error('회원 등록 오류:', error)
    return NextResponse.json({ error: '서버 오류' }, { status: 500 })
  }
}

// 추천 트리 구성 (신규 회원 기준으로 상위 5단계까지 등록)
async function buildReferralTree(newMemberId: string, referrerId: string) {
  // 직접 추천 관계 (depth 1)
  const rows = [{ ancestor_id: referrerId, descendant_id: newMemberId, depth: 1 }]

  // 추천인의 상위 조상들 조회 (depth 1~4 → 신규 회원 기준 2~5)
  const { data: ancestors } = await supabase
    .from('referral_tree')
    .select('ancestor_id, depth')
    .eq('descendant_id', referrerId)
    .lte('depth', 4)
    .order('depth', { ascending: true })

  for (const ancestor of ancestors ?? []) {
    rows.push({
      ancestor_id: ancestor.ancestor_id,
      descendant_id: newMemberId,
      depth: ancestor.depth + 1,
    })
  }

  await supabase.from('referral_tree').insert(rows)

  // 추천인 등급 업데이트
  await updateGrade(referrerId)
}

// 직접 추천 수 기반 등급 업데이트
async function updateGrade(memberId: string) {
  const { count } = await supabase
    .from('referral_tree')
    .select('*', { count: 'exact', head: true })
    .eq('ancestor_id', memberId)
    .eq('depth', 1)

  const directCount = count ?? 0
  let grade = '씨앗'
  let multiplier = 1.0

  if (directCount >= 20) { grade = '목장'; multiplier = 2.0 }
  else if (directCount >= 10) { grade = '숲'; multiplier = 1.75 }
  else if (directCount >= 5) { grade = '나무'; multiplier = 1.5 }
  else if (directCount >= 2) { grade = '새싹'; multiplier = 1.25 }

  await supabase
    .from('members')
    .update({ grade, grade_multiplier: multiplier })
    .eq('id', memberId)
}
