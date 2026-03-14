import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@supabase/supabase-js'

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!
)

const GRADE_NEXT: Record<string, { name: string; required: number }> = {
  '씨앗': { name: '새싹', required: 2 },
  '새싹': { name: '나무', required: 5 },
  '나무': { name: '숲', required: 10 },
  '숲': { name: '목장', required: 20 },
  '목장': { name: '목장', required: 20 },
}

const MILESTONE_LABELS: Record<string, { label: string; points: number; required: number }> = {
  first_referral: { label: '첫 추천 성공', points: 5000, required: 1 },
  network_5:      { label: '직접 추천 5명', points: 20000, required: 5 },
  network_10:     { label: '네트워크 10명', points: 30000, required: 10 },
}

export async function GET(req: NextRequest) {
  try {
    const memberId = req.nextUrl.searchParams.get('member_id')

    if (!memberId) {
      return NextResponse.json({ error: '회원 ID 필요' }, { status: 400 })
    }

    // 병렬로 데이터 조회
    const [memberRes, pointRes, rewardsRes, networkRes, milestonesRes] = await Promise.all([
      // 회원 정보 (등급)
      supabase.from('members').select('grade, grade_multiplier, referral_code').eq('id', memberId).single(),

      // 포인트 잔액
      supabase.from('point_balance').select('total_earned, total_used, balance').eq('member_id', memberId).single(),

      // 이번 달 리워드
      supabase.from('rewards')
        .select('final_points, depth, created_at')
        .eq('receiver_id', memberId)
        .gte('created_at', new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString()),

      // 네트워크 현황 (단계별)
      supabase.from('referral_tree')
        .select('depth')
        .eq('ancestor_id', memberId),

      // 마일스톤 달성 내역
      supabase.from('milestones').select('milestone_type, achieved_at').eq('member_id', memberId),
    ])

    const member = memberRes.data
    const point = pointRes.data
    const rewards = rewardsRes.data ?? []
    const network = networkRes.data ?? []
    const achievedMilestones = new Set(milestonesRes.data?.map((m) => m.milestone_type) ?? [])

    // 단계별 네트워크 수
    const networkByDepth: Record<number, number> = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
    for (const n of network) networkByDepth[n.depth] = (networkByDepth[n.depth] ?? 0) + 1

    const directCount = networkByDepth[1]
    const totalNetwork = network.length

    // 이번 달 리워드 합계
    const thisMonthPoints = rewards.reduce((sum, r) => sum + r.final_points, 0)

    // 다음 등급까지 진행률
    const grade = member?.grade ?? '씨앗'
    const nextGrade = GRADE_NEXT[grade]
    const gradeProgress = Math.min(Math.round((directCount / nextGrade.required) * 100), 100)

    // 마일스톤 현황
    const milestones = Object.entries(MILESTONE_LABELS).map(([type, info]) => ({
      type,
      label: info.label,
      points: info.points,
      achieved: achievedMilestones.has(type),
      progress: type === 'first_referral'
        ? Math.min(directCount, 1)
        : type === 'network_5'
        ? Math.min(directCount, 5)
        : Math.min(totalNetwork, 10),
      required: info.required,
    }))

    return NextResponse.json({
      member: {
        referral_code: member?.referral_code,
        grade,
        grade_multiplier: member?.grade_multiplier ?? 1.0,
        next_grade: nextGrade.name,
        grade_progress: gradeProgress,
      },
      points: {
        balance: point?.balance ?? 0,
        total_earned: point?.total_earned ?? 0,
        total_used: point?.total_used ?? 0,
        this_month: thisMonthPoints,
      },
      network: {
        total: totalNetwork,
        by_depth: networkByDepth,
      },
      milestones,
    })

  } catch (error) {
    console.error('성과 조회 오류:', error)
    return NextResponse.json({ error: '서버 오류' }, { status: 500 })
  }
}
