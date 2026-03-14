import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'
import { formatDateTime } from '@/utils/format'
import { REFERRAL_RATES } from '@/constants'
import ReferralShareClient from './ReferralShareClient'
import MypageHeader from '@/components/store/mypage/MypageHeader'
import Link from 'next/link'

const GRADES = [
  { name: '씨앗', emoji: '🌱', condition: '0명', multiplier: 1.0 },
  { name: '새싹', emoji: '🌿', condition: '2명 이상', multiplier: 1.25 },
  { name: '나무', emoji: '🌳', condition: '5명 이상', multiplier: 1.5 },
  { name: '숲', emoji: '🌲', condition: '10명 이상', multiplier: 1.75 },
  { name: '목장', emoji: '🐄', condition: '20명 이상', multiplier: 2.0 },
]

const MILESTONES = [
  { emoji: '🎉', title: '첫 추천 성공', desc: '추천 코드로 첫 번째 친구가 가입하면 지급', points: 5000 },
  { emoji: '👥', title: '직접 추천 5명', desc: 'L1 추천인이 5명이 되면 지급', points: 20000 },
  { emoji: '🌐', title: '네트워크 10명', desc: 'L1~L5 전체 합산 10명이 되면 지급', points: 30000 },
]

export default async function ReferralPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | undefined }>
}) {
  const params = await searchParams
  const tab = params.tab ?? 'status'

  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase
    .from('members')
    .select('id, name, mileage, referral_code')
    .eq('auth_id', user.id)
    .single()
  if (!member) redirect('/auth/login')

  // 추천인 등급 정보
  const { data: referralInfo } = await supabase
    .from('referral_members')
    .select('grade, grade_multiplier')
    .eq('member_id', member.id)
    .single()

  // 직접 추천 수 (referred_by 기준)
  const { count: invitedCount } = await supabase
    .from('members')
    .select('id', { count: 'exact', head: true })
    .eq('referred_by', member.referral_code)

  // 총 적립 리워드
  const { data: totalEarnedData } = await supabase
    .from('referral_rewards')
    .select('final_points')
    .eq('receiver_id', member.id)
    .eq('status', '지급완료')

  const totalEarned = (totalEarnedData ?? []).reduce((sum: number, r: any) => sum + r.final_points, 0)

  // 추천 리워드 이력
  const { data: rewards } = await supabase
    .from('referral_rewards')
    .select('depth, base_rate, multiplier, order_amount, final_points, status, created_at')
    .eq('receiver_id', member.id)
    .order('created_at', { ascending: false })
    .limit(20)

  const referralCode = member.referral_code
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung-mall.com'
  const referralUrl = referralCode ? `${siteUrl}/auth/signup?ref=${referralCode}` : null

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="친구초대하고 리워드받기" />

      <div className="px-4 pb-8">
        {/* 탭 */}
        <div className="flex mb-4 rounded-xl overflow-hidden" style={{ border: '1px solid #e8e4df' }}>
          <Link
            href="/mypage/referral?tab=status"
            className={`flex-1 py-3 text-center text-[18px] transition-colors ${tab === 'status' ? 'font-bold' : 'font-medium'}`}
            style={{
              backgroundColor: tab === 'status' ? '#fff' : 'transparent',
              color: tab === 'status' ? '#333' : '#999',
            }}
          >
            내 현황
          </Link>
          <Link
            href="/mypage/referral?tab=guide"
            className={`flex-1 py-3 text-center text-[18px] transition-colors ${tab === 'guide' ? 'font-bold' : 'font-medium'}`}
            style={{
              backgroundColor: tab === 'guide' ? '#fff' : 'transparent',
              color: tab === 'guide' ? '#333' : '#999',
            }}
          >
            혜택 안내
          </Link>
        </div>

        {tab === 'status' && (
          <>
            {/* 내 추천 코드 카드 */}
            <div className="bg-green-50 rounded-2xl p-6 mb-4" style={{ border: '1px solid #d4edda' }}>
              <p className="text-sm font-medium mb-1" style={{ color: '#2d6a2e', opacity: 0.75 }}>내 추천 코드</p>
              <p className="text-3xl font-bold tracking-widest mb-4" style={{ color: '#2d6a2e' }}>{referralCode ?? '-'}</p>
              {referralUrl && <ReferralShareClient url={referralUrl} code={referralCode} />}
              <div className="mt-4 grid grid-cols-3 gap-4 pt-4" style={{ borderTop: '1px solid #b8ddb8' }}>
                <div>
                  <p className="text-[14px]" style={{ color: '#5a8a5a' }}>추천 등급</p>
                  <p className="text-lg font-bold" style={{ color: '#2d6a2e' }}>{referralInfo?.grade ?? '씨앗'}</p>
                </div>
                <div>
                  <p className="text-[14px]" style={{ color: '#5a8a5a' }}>추천인 수</p>
                  <p className="text-xl font-bold" style={{ color: '#2d6a2e' }}>{invitedCount ?? 0}명</p>
                </div>
                <div>
                  <p className="text-[14px]" style={{ color: '#5a8a5a' }}>총 리워드</p>
                  <p className="text-xl font-bold" style={{ color: '#2d6a2e' }}>{totalEarned.toLocaleString()}P</p>
                </div>
              </div>
            </div>

            {/* 리워드 구조 */}
            <div className="rounded-xl p-5 mb-4" style={{ border: '1px solid #eee' }}>
              <h3 className="text-[20px] font-semibold text-gray-900 mb-3">추천 리워드 구조</h3>
              <div className="space-y-1">
                {REFERRAL_RATES.map((rate, idx) => (
                  <div key={idx} className="flex justify-between items-center py-2.5 border-b border-gray-50 last:border-0">
                    <span className="text-[18px] text-gray-700">L{idx + 1} {idx === 0 ? '(직접 추천)' : `(${idx + 1}단계)`}</span>
                    <span className="text-[18px] font-semibold" style={{ color: '#2d6a2e' }}>
                      {(rate * 100).toFixed(1)}%{referralInfo?.grade_multiplier > 1 ? ` × ${referralInfo.grade_multiplier}` : ''}
                    </span>
                  </div>
                ))}
              </div>
              <p className="text-[14px] text-gray-400 mt-3">추천인의 구매 금액에 비례하여 포인트가 지급됩니다. (추천일로부터 1년간 유효)</p>
            </div>

            {/* 리워드 이력 */}
            <div className="rounded-xl p-5" style={{ border: '1px solid #eee' }}>
              <h3 className="text-[20px] font-semibold text-gray-900 mb-3">리워드 적립 이력</h3>
              {(rewards ?? []).length > 0 ? (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-gray-100">
                      <th className="pb-2 text-left text-xs text-gray-500 font-medium">일시</th>
                      <th className="pb-2 text-left text-xs text-gray-500 font-medium">단계</th>
                      <th className="pb-2 text-right text-xs text-gray-500 font-medium">리워드</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-50">
                    {(rewards ?? []).map((r: any, idx: number) => (
                      <tr key={idx} className={r.status === '취소' ? 'opacity-50' : ''}>
                        <td className="py-2 text-xs text-gray-500">{formatDateTime(r.created_at)}</td>
                        <td className="py-2 text-xs text-gray-700">L{r.depth}</td>
                        <td className="py-2 text-right font-medium" style={{ color: r.status === '취소' ? '#999' : '#2d6a2e' }}>
                          {r.status === '취소' ? '-' : '+'}{r.final_points.toLocaleString()}P
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <p className="text-[14px] text-gray-400">아직 리워드 이력이 없습니다.</p>
              )}
            </div>
          </>
        )}

        {tab === 'guide' && (
          <>
            {/* 추천 리워드 시스템 설명 */}
            <div className="rounded-xl p-5 mb-4" style={{ border: '1px solid #eee' }}>
              <h3 className="text-[20px] font-bold text-gray-900 mb-2">추천 리워드 시스템</h3>
              <p className="text-[14px] text-gray-600 leading-relaxed">
                친구에게 설성목장을 소개하고 구매가 발생하면 포인트를 적립해 드립니다.
                추천이 이어질수록 더 많은 혜택을 누릴 수 있어요.
              </p>
            </div>

            {/* 5단계 추천 리워드 */}
            <div className="bg-green-50 rounded-xl p-5 mb-4" style={{ border: '1px solid #d4edda' }}>
              <h4 className="text-[18px] font-bold mb-2" style={{ color: '#2d6a2e' }}>🌿 5단계 추천 리워드</h4>
              <p className="text-[14px] text-gray-600 mb-4">
                내가 추천한 친구가 구매하면 리워드를 받고, 그 친구가 또 다른 친구를 추천해 구매가 발생하면 추가 리워드를 받습니다.
              </p>
              <div className="grid grid-cols-5 gap-2">
                {REFERRAL_RATES.map((rate, idx) => (
                  <div key={idx} className="bg-white rounded-lg py-3 text-center">
                    <p className="text-[14px] text-gray-500 mb-1">L{idx + 1}</p>
                    <p className="text-[18px] font-bold" style={{ color: '#2d6a2e' }}>{(rate * 100)}%</p>
                  </div>
                ))}
              </div>
              <p className="text-[14px] text-gray-400 mt-3">※ 추천 관계는 가입 후 1년간 유효합니다.</p>
            </div>

            {/* 등급 가속 보너스 */}
            <div className="bg-green-50 rounded-xl p-5 mb-4" style={{ border: '1px solid #d4edda' }}>
              <h4 className="text-[18px] font-bold mb-2" style={{ color: '#2d6a2e' }}>⭐ 등급 가속 보너스</h4>
              <p className="text-[14px] text-gray-600">
                추천 네트워크가 커질수록 등급이 올라가고, 모든 리워드에 배율이 적용됩니다.
              </p>
            </div>

            {/* 마일스톤 보너스 */}
            <div className="bg-green-50 rounded-xl p-5 mb-4" style={{ border: '1px solid #d4edda' }}>
              <h4 className="text-[18px] font-bold mb-2" style={{ color: '#2d6a2e' }}>🎯 마일스톤 보너스</h4>
              <p className="text-[14px] text-gray-600">
                특정 목표를 달성하면 일회성 보너스 포인트가 즉시 지급됩니다.
              </p>
            </div>

            {/* 등급 기준 */}
            <div className="rounded-xl p-5 mb-4" style={{ border: '1px solid #eee' }}>
              <h3 className="text-[20px] font-bold text-gray-900 mb-4">등급 기준</h3>
              <table className="w-full text-[18px]">
                <thead>
                  <tr className="border-b border-gray-100">
                    <th className="pb-2 text-left text-[14px] text-gray-500 font-medium">등급</th>
                    <th className="pb-2 text-center text-[14px] text-gray-500 font-medium">조건 (직접 추천)</th>
                    <th className="pb-2 text-right text-[14px] text-gray-500 font-medium">리워드 배율</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {GRADES.map((g) => (
                    <tr key={g.name}>
                      <td className="py-3 text-gray-700">{g.emoji} {g.name}</td>
                      <td className="py-3 text-center text-gray-500">{g.condition}</td>
                      <td className="py-3 text-right font-semibold" style={{ color: '#2d6a2e' }}>×{g.multiplier}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* 마일스톤 보너스 목록 */}
            <div className="rounded-xl p-5" style={{ border: '1px solid #eee' }}>
              <h3 className="text-[20px] font-bold text-gray-900 mb-4">마일스톤 보너스 목록</h3>
              <div className="space-y-3">
                {MILESTONES.map((m, idx) => (
                  <div key={idx} className="flex items-center gap-3 p-4 rounded-xl" style={{ backgroundColor: '#f0faf0', border: '1px solid #d4edda' }}>
                    <span className="text-2xl">{m.emoji}</span>
                    <div className="flex-1">
                      <p className="text-[18px] font-bold" style={{ color: '#2d6a2e' }}>{m.title}</p>
                      <p className="text-[14px] text-gray-500">{m.desc}</p>
                    </div>
                    <p className="text-[18px] font-bold" style={{ color: '#2d6a2e' }}>+{m.points.toLocaleString()}P</p>
                  </div>
                ))}
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  )
}
