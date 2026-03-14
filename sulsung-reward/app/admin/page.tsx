'use client'

import { useEffect, useState } from 'react'
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'

interface AdminStats {
  summary: {
    total_members: number
    new_members_this_month: number
    total_sales_this_month: number
    reward_total_this_month: number
    reward_total_last_month: number
    reward_ratio_percent: string
  }
  grade_distribution: Record<string, number>
  top_referrers: Array<{
    id: string
    grade: string
    referral_code: string
    point_balance: { total_earned: number }[]
  }>
  recent_rewards: Array<{
    receiver_id: string
    buyer_id: string
    depth: number
    final_points: number
    created_at: string
    status: string
  }>
  abuse_alerts: Array<{
    member_id: string
    referral_count_24h: number
  }>
}

const GRADE_COLORS: Record<string, string> = {
  씨앗: '#86efac',
  새싹: '#4ade80',
  나무: '#16a34a',
  숲: '#15803d',
  목장: '#14532d',
}

function StatCard({ label, value, sub }: { label: string; value: string | number; sub?: string }) {
  return (
    <div className="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
      <p className="text-sm text-gray-500 mb-1">{label}</p>
      <p className="text-2xl font-bold text-gray-800">{value}</p>
      {sub && <p className="text-xs text-gray-400 mt-1">{sub}</p>}
    </div>
  )
}

export default function AdminDashboard() {
  const [stats, setStats] = useState<AdminStats | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/admin/stats')
      .then((r) => r.json())
      .then((data) => { setStats(data); setLoading(false) })
      .catch(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-400">데이터 불러오는 중...</p>
      </div>
    )
  }

  if (!stats) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-red-400">데이터 로드 실패</p>
      </div>
    )
  }

  const gradeChartData = Object.entries(stats.grade_distribution).map(([name, value]) => ({ name, value }))

  const rewardCompareData = [
    { name: '지난달', value: stats.summary.reward_total_last_month },
    { name: '이번달', value: stats.summary.reward_total_this_month },
  ]

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      {/* 헤더 */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">설성목장 리워드 관리자</h1>
        <p className="text-sm text-gray-400 mt-1">{new Date().toLocaleDateString('ko-KR', { year: 'numeric', month: 'long', day: 'numeric' })} 기준</p>
      </div>

      {/* 어뷰징 알림 */}
      {stats.abuse_alerts.length > 0 && (
        <div className="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
          <p className="text-red-600 font-semibold mb-2">⚠️ 어뷰징 의심 회원 ({stats.abuse_alerts.length}명)</p>
          <div className="flex flex-wrap gap-2">
            {stats.abuse_alerts.map((a) => (
              <span key={a.member_id} className="bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full">
                {a.member_id} — 24시간 내 {a.referral_count_24h}건
              </span>
            ))}
          </div>
        </div>
      )}

      {/* 핵심 지표 */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <StatCard label="전체 회원" value={stats.summary.total_members.toLocaleString() + '명'} />
        <StatCard label="이번달 신규" value={stats.summary.new_members_this_month.toLocaleString() + '명'} />
        <StatCard label="이번달 매출" value={'₩' + stats.summary.total_sales_this_month.toLocaleString()} />
        <StatCard label="이번달 리워드" value={'₩' + stats.summary.reward_total_this_month.toLocaleString()} />
        <StatCard label="지난달 리워드" value={'₩' + stats.summary.reward_total_last_month.toLocaleString()} />
        <StatCard
          label="리워드 비율"
          value={stats.summary.reward_ratio_percent + '%'}
          sub="매출 대비"
        />
      </div>

      {/* 차트 영역 */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {/* 등급 분포 */}
        <div className="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <h2 className="font-semibold text-gray-700 mb-4">등급 분포</h2>
          <ResponsiveContainer width="100%" height={200}>
            <PieChart>
              <Pie data={gradeChartData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={80} label={({ name, value }) => `${name} ${value}명`}>
                {gradeChartData.map((entry) => (
                  <Cell key={entry.name} fill={GRADE_COLORS[entry.name] ?? '#ccc'} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>

        {/* 리워드 비교 */}
        <div className="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <h2 className="font-semibold text-gray-700 mb-4">리워드 지급 비교</h2>
          <ResponsiveContainer width="100%" height={200}>
            <BarChart data={rewardCompareData}>
              <XAxis dataKey="name" />
              <YAxis tickFormatter={(v) => `${(v / 10000).toFixed(0)}만`} />
              <Tooltip formatter={(v) => `₩${Number(v).toLocaleString()}`} />
              <Bar dataKey="value" fill="#4ade80" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* 상위 추천인 */}
      <div className="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
        <h2 className="font-semibold text-gray-700 mb-4">상위 추천인 TOP 10</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-gray-400 border-b">
                <th className="text-left py-2">회원 ID</th>
                <th className="text-left py-2">추천 코드</th>
                <th className="text-left py-2">등급</th>
                <th className="text-right py-2">누적 리워드</th>
              </tr>
            </thead>
            <tbody>
              {stats.top_referrers.map((m) => (
                <tr key={m.id} className="border-b last:border-0 hover:bg-gray-50">
                  <td className="py-2 text-gray-700">{m.id}</td>
                  <td className="py-2 font-mono text-green-600">{m.referral_code}</td>
                  <td className="py-2">
                    <span className="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">{m.grade}</span>
                  </td>
                  <td className="py-2 text-right font-medium">
                    ₩{(m.point_balance?.[0]?.total_earned ?? 0).toLocaleString()}
                  </td>
                </tr>
              ))}
              {stats.top_referrers.length === 0 && (
                <tr><td colSpan={4} className="py-4 text-center text-gray-400">데이터 없음</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* 최근 리워드 내역 */}
      <div className="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <h2 className="font-semibold text-gray-700 mb-4">최근 리워드 내역</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-gray-400 border-b">
                <th className="text-left py-2">수령인</th>
                <th className="text-left py-2">구매자</th>
                <th className="text-left py-2">단계</th>
                <th className="text-right py-2">포인트</th>
                <th className="text-left py-2">상태</th>
                <th className="text-left py-2">일시</th>
              </tr>
            </thead>
            <tbody>
              {stats.recent_rewards.map((r, i) => (
                <tr key={i} className="border-b last:border-0 hover:bg-gray-50">
                  <td className="py-2 text-gray-700">{r.receiver_id}</td>
                  <td className="py-2 text-gray-500">{r.buyer_id}</td>
                  <td className="py-2">
                    <span className="bg-blue-100 text-blue-600 text-xs px-2 py-0.5 rounded-full">L{r.depth}</span>
                  </td>
                  <td className="py-2 text-right font-medium text-green-600">+{r.final_points.toLocaleString()}P</td>
                  <td className="py-2">
                    <span className={`text-xs px-2 py-0.5 rounded-full ${r.status === '지급완료' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'}`}>
                      {r.status}
                    </span>
                  </td>
                  <td className="py-2 text-gray-400">{new Date(r.created_at).toLocaleDateString('ko-KR')}</td>
                </tr>
              ))}
              {stats.recent_rewards.length === 0 && (
                <tr><td colSpan={6} className="py-4 text-center text-gray-400">데이터 없음</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
