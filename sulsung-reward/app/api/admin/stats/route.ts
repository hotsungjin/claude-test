import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@supabase/supabase-js'

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!
)

export async function GET(req: NextRequest) {
  try {
    const now = new Date()
    const thisMonthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString()
    const lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString()

    const [
      totalMembersRes,
      thisMonthMembersRes,
      thisMonthOrdersRes,
      thisMonthRewardsRes,
      lastMonthRewardsRes,
      gradeDistRes,
      topReferrersRes,
      recentRewardsRes,
      abuseRes,
    ] = await Promise.all([
      // 전체 회원 수
      supabase.from('members').select('*', { count: 'exact', head: true }),

      // 이번 달 신규 회원
      supabase.from('members').select('*', { count: 'exact', head: true }).gte('created_at', thisMonthStart),

      // 이번 달 주문 합계
      supabase.from('orders').select('amount').gte('ordered_at', thisMonthStart),

      // 이번 달 리워드 지급 합계
      supabase.from('rewards').select('final_points').gte('created_at', thisMonthStart),

      // 지난 달 리워드 지급 합계
      supabase.from('rewards').select('final_points').gte('created_at', lastMonthStart).lt('created_at', thisMonthStart),

      // 등급 분포
      supabase.from('members').select('grade'),

      // 상위 추천인 10명
      supabase.from('members')
        .select('id, grade, referral_code, point_balance(total_earned)')
        .order('grade_multiplier', { ascending: false })
        .limit(10),

      // 최근 리워드 내역 20건
      supabase.from('rewards')
        .select('receiver_id, buyer_id, depth, final_points, created_at, status')
        .order('created_at', { ascending: false })
        .limit(20),

      // 어뷰징 감지: 24시간 내 동일 IP에서 3건 이상 가입 (referral_tree 기준)
      supabase.from('referral_tree')
        .select('ancestor_id, descendant_id, created_at')
        .gte('created_at', new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString())
        .eq('depth', 1),
    ])

    // 매출 합계
    const totalSales = (thisMonthOrdersRes.data ?? []).reduce((sum, o) => sum + o.amount, 0)

    // 리워드 합계
    const thisMonthRewardTotal = (thisMonthRewardsRes.data ?? []).reduce((sum, r) => sum + r.final_points, 0)
    const lastMonthRewardTotal = (lastMonthRewardsRes.data ?? []).reduce((sum, r) => sum + r.final_points, 0)

    // 리워드 비율 (매출 대비)
    const rewardRatio = totalSales > 0 ? ((thisMonthRewardTotal / totalSales) * 100).toFixed(2) : '0.00'

    // 등급 분포 집계
    const grades = gradeDistRes.data ?? []
    const gradeCount: Record<string, number> = { 씨앗: 0, 새싹: 0, 나무: 0, 숲: 0, 목장: 0 }
    for (const m of grades) gradeCount[m.grade] = (gradeCount[m.grade] ?? 0) + 1

    // 어뷰징 감지: 특정 추천인이 24시간 내 5명 이상 추천한 경우
    const referralCounts: Record<string, number> = {}
    for (const r of abuseRes.data ?? []) {
      referralCounts[r.ancestor_id] = (referralCounts[r.ancestor_id] ?? 0) + 1
    }
    const suspiciousMembers = Object.entries(referralCounts)
      .filter(([, count]) => count >= 5)
      .map(([id, count]) => ({ member_id: id, referral_count_24h: count }))

    return NextResponse.json({
      summary: {
        total_members: totalMembersRes.count ?? 0,
        new_members_this_month: thisMonthMembersRes.count ?? 0,
        total_sales_this_month: totalSales,
        reward_total_this_month: thisMonthRewardTotal,
        reward_total_last_month: lastMonthRewardTotal,
        reward_ratio_percent: rewardRatio,
      },
      grade_distribution: gradeCount,
      top_referrers: topReferrersRes.data ?? [],
      recent_rewards: recentRewardsRes.data ?? [],
      abuse_alerts: suspiciousMembers,
    })

  } catch (error) {
    console.error('관리자 통계 오류:', error)
    return NextResponse.json({ error: '서버 오류' }, { status: 500 })
  }
}
