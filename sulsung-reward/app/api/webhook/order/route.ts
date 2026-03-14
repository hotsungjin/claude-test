import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@supabase/supabase-js'

// 서버용 클라이언트 (Secret key 사용 예정, 현재는 publishable key로 임시 사용)
const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!
)

// 단계별 기본 리워드율
const BASE_RATES: Record<number, number> = {
  1: 0.05,   // 5%
  2: 0.02,   // 2%
  3: 0.01,   // 1%
  4: 0.005,  // 0.5%
  5: 0.002,  // 0.2%
}

// 1년 유효기간 체크
function isWithinOneYear(createdAt: string): boolean {
  const referralDate = new Date(createdAt)
  const oneYearLater = new Date(referralDate)
  oneYearLater.setFullYear(oneYearLater.getFullYear() + 1)
  return new Date() <= oneYearLater
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json()

    // 고도몰 Webhook 데이터 파싱
    const { member_id, order_no, amount } = body

    if (!member_id || !order_no || !amount) {
      return NextResponse.json({ error: '필수 파라미터 누락' }, { status: 400 })
    }

    // 1. 주문 저장
    const { data: order, error: orderError } = await supabase
      .from('orders')
      .insert({ member_id, order_no, amount })
      .select()
      .single()

    if (orderError) {
      // 중복 주문 무시
      if (orderError.code === '23505') {
        return NextResponse.json({ message: '이미 처리된 주문' }, { status: 200 })
      }
      throw orderError
    }

    // 2. 구매자의 상위 추천인 조회 (1~5단계)
    const { data: ancestors } = await supabase
      .from('referral_tree')
      .select('ancestor_id, depth, created_at')
      .eq('descendant_id', member_id)
      .order('depth', { ascending: true })

    if (!ancestors || ancestors.length === 0) {
      return NextResponse.json({ message: '추천인 없음, 주문만 저장' }, { status: 200 })
    }

    // 3. 각 추천인에게 리워드 계산 및 지급
    for (const ancestor of ancestors) {
      // 1년 유효기간 체크
      if (!isWithinOneYear(ancestor.created_at)) continue

      // 추천인 등급 조회
      const { data: member } = await supabase
        .from('members')
        .select('grade_multiplier')
        .eq('id', ancestor.ancestor_id)
        .single()

      const multiplier = member?.grade_multiplier ?? 1.0
      const baseRate = BASE_RATES[ancestor.depth]
      const finalPoints = Math.floor(amount * baseRate * multiplier)

      if (finalPoints <= 0) continue

      // 리워드 내역 저장
      await supabase.from('rewards').insert({
        receiver_id: ancestor.ancestor_id,
        order_id: order.id,
        buyer_id: member_id,
        depth: ancestor.depth,
        base_rate: baseRate,
        multiplier,
        final_points: finalPoints,
        status: '지급완료',
      })

      // 포인트 잔액 업데이트 (upsert)
      await supabase.rpc('increment_points', {
        p_member_id: ancestor.ancestor_id,
        p_points: finalPoints,
      })
    }

    // 4. 마일스톤 체크
    await checkMilestones(member_id)

    return NextResponse.json({ message: '리워드 처리 완료' }, { status: 200 })
  } catch (error) {
    console.error('Webhook 처리 오류:', error)
    return NextResponse.json({ error: '서버 오류' }, { status: 500 })
  }
}

// 마일스톤 체크 함수
async function checkMilestones(memberId: string) {
  // 직접 추천 수 조회
  const { count: directCount } = await supabase
    .from('referral_tree')
    .select('*', { count: 'exact', head: true })
    .eq('ancestor_id', memberId)
    .eq('depth', 1)

  // 전체 네트워크 수 조회
  const { count: networkCount } = await supabase
    .from('referral_tree')
    .select('*', { count: 'exact', head: true })
    .eq('ancestor_id', memberId)

  // 이미 달성한 마일스톤 조회
  const { data: achieved } = await supabase
    .from('milestones')
    .select('milestone_type')
    .eq('member_id', memberId)

  const achievedTypes = new Set(achieved?.map((m) => m.milestone_type) ?? [])

  const milestones = [
    { type: 'first_referral', condition: (directCount ?? 0) >= 1, points: 5000 },
    { type: 'network_5', condition: (directCount ?? 0) >= 5, points: 20000 },
    { type: 'network_10', condition: (networkCount ?? 0) >= 10, points: 30000 },
  ]

  for (const milestone of milestones) {
    if (milestone.condition && !achievedTypes.has(milestone.type)) {
      await supabase.from('milestones').insert({
        member_id: memberId,
        milestone_type: milestone.type,
        points_given: milestone.points,
      })
      await supabase.rpc('increment_points', {
        p_member_id: memberId,
        p_points: milestone.points,
      })
    }
  }
}
