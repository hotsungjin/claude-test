'use client'

import { useEffect, useState } from 'react'

interface MemberStats {
  member: {
    referral_code: string
    grade: string
    grade_multiplier: number
    next_grade: string
    grade_progress: number
  }
  points: {
    balance: number
    total_earned: number
    total_used: number
    this_month: number
  }
  network: {
    total: number
    by_depth: Record<string, number>
  }
  milestones: Array<{
    type: string
    label: string
    points: number
    achieved: boolean
    progress: number
    required: number
  }>
}

const GRADE_EMOJI: Record<string, string> = {
  씨앗: '🌱', 새싹: '🌿', 나무: '🌳', 숲: '🌲', 목장: '🏔️',
}

const GRADE_COLOR: Record<string, string> = {
  씨앗: 'bg-green-100 text-green-700',
  새싹: 'bg-green-200 text-green-800',
  나무: 'bg-emerald-200 text-emerald-800',
  숲: 'bg-emerald-300 text-emerald-900',
  목장: 'bg-green-800 text-white',
}

// 고도몰 마이페이지에서 넘겨주는 회원 ID (실제 연동 시 URL 파라미터 또는 고도몰 JS 변수 사용)
const DEMO_MEMBER_ID = 'demo_user_001'

export default function MyPage() {
  const [stats, setStats] = useState<MemberStats | null>(null)
  const [loading, setLoading] = useState(true)
  const [copied, setCopied] = useState(false)

  useEffect(() => {
    fetch(`/api/member/stats?member_id=${DEMO_MEMBER_ID}`)
      .then((r) => r.json())
      .then((data) => { setStats(data); setLoading(false) })
      .catch(() => setLoading(false))
  }, [])

  const copyReferralLink = () => {
    const link = `${window.location.origin}/join?ref=${stats?.member.referral_code}`
    navigator.clipboard.writeText(link)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-400">불러오는 중...</p>
      </div>
    )
  }

  if (!stats || !stats.member) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-400">리워드 프로그램에 등록되지 않은 회원입니다.</p>
      </div>
    )
  }

  const { member, points, network, milestones } = stats

  return (
    <div className="min-h-screen bg-gray-50 p-4 max-w-2xl mx-auto">

      {/* 등급 카드 */}
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
        <div className="flex items-center justify-between mb-4">
          <div>
            <span className={`text-sm font-semibold px-3 py-1 rounded-full ${GRADE_COLOR[member.grade]}`}>
              {GRADE_EMOJI[member.grade]} {member.grade}
            </span>
            <p className="text-xs text-gray-400 mt-1">리워드 배율 ×{member.grade_multiplier}</p>
          </div>
          <div className="text-right">
            <p className="text-xs text-gray-400">내 추천 코드</p>
            <p className="text-2xl font-bold font-mono tracking-widest text-green-600">{member.referral_code}</p>
          </div>
        </div>

        {/* 등급 진행률 */}
        {member.grade !== '목장' && (
          <div>
            <div className="flex justify-between text-xs text-gray-400 mb-1">
              <span>다음 등급: {GRADE_EMOJI[member.next_grade]} {member.next_grade}</span>
              <span>{member.grade_progress}%</span>
            </div>
            <div className="w-full bg-gray-100 rounded-full h-2">
              <div
                className="bg-green-500 h-2 rounded-full transition-all duration-500"
                style={{ width: `${member.grade_progress}%` }}
              />
            </div>
          </div>
        )}

        {/* 추천 링크 공유 버튼 */}
        <button
          onClick={copyReferralLink}
          className="mt-4 w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors"
        >
          {copied ? '✓ 링크 복사됨!' : '친구에게 추천 링크 공유하기'}
        </button>
      </div>

      {/* 포인트 현황 */}
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
        <h2 className="font-semibold text-gray-700 mb-4">포인트 현황</h2>
        <div className="grid grid-cols-2 gap-4">
          <div className="bg-green-50 rounded-xl p-4">
            <p className="text-xs text-gray-500 mb-1">사용 가능 포인트</p>
            <p className="text-2xl font-bold text-green-600">{points.balance.toLocaleString()}P</p>
          </div>
          <div className="bg-gray-50 rounded-xl p-4">
            <p className="text-xs text-gray-500 mb-1">이번 달 적립</p>
            <p className="text-2xl font-bold text-gray-700">{points.this_month.toLocaleString()}P</p>
          </div>
          <div className="rounded-xl p-3 border border-gray-100">
            <p className="text-xs text-gray-400">누적 적립</p>
            <p className="text-lg font-semibold text-gray-600">{points.total_earned.toLocaleString()}P</p>
          </div>
          <div className="rounded-xl p-3 border border-gray-100">
            <p className="text-xs text-gray-400">누적 사용</p>
            <p className="text-lg font-semibold text-gray-600">{points.total_used.toLocaleString()}P</p>
          </div>
        </div>
      </div>

      {/* 네트워크 현황 */}
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="font-semibold text-gray-700">내 추천 네트워크</h2>
          <span className="text-sm text-gray-400">총 {network.total}명</span>
        </div>
        <div className="space-y-3">
          {[1, 2, 3, 4, 5].map((depth) => {
            const count = network.by_depth[depth] ?? 0
            const rates: Record<number, string> = { 1: '5%', 2: '2%', 3: '1%', 4: '0.5%', 5: '0.2%' }
            return (
              <div key={depth} className="flex items-center gap-3">
                <span className="text-xs text-gray-400 w-14">L{depth} ({rates[depth]})</span>
                <div className="flex-1 bg-gray-100 rounded-full h-2">
                  <div
                    className="bg-green-400 h-2 rounded-full transition-all duration-500"
                    style={{ width: count > 0 ? `${Math.min((count / 20) * 100, 100)}%` : '0%' }}
                  />
                </div>
                <span className="text-sm font-medium text-gray-600 w-8 text-right">{count}명</span>
              </div>
            )
          })}
        </div>
      </div>

      {/* 마일스톤 */}
      <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h2 className="font-semibold text-gray-700 mb-4">마일스톤</h2>
        <div className="space-y-3">
          {milestones.map((m) => (
            <div key={m.type} className={`flex items-center gap-4 p-3 rounded-xl border ${m.achieved ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-100'}`}>
              <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm ${m.achieved ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'}`}>
                {m.achieved ? '✓' : '○'}
              </div>
              <div className="flex-1">
                <p className={`text-sm font-medium ${m.achieved ? 'text-green-700' : 'text-gray-500'}`}>{m.label}</p>
                {!m.achieved && (
                  <div className="mt-1">
                    <div className="w-full bg-gray-200 rounded-full h-1.5">
                      <div
                        className="bg-green-400 h-1.5 rounded-full"
                        style={{ width: `${(m.progress / m.required) * 100}%` }}
                      />
                    </div>
                    <p className="text-xs text-gray-400 mt-0.5">{m.progress} / {m.required}</p>
                  </div>
                )}
              </div>
              <span className={`text-sm font-bold ${m.achieved ? 'text-green-600' : 'text-gray-400'}`}>
                +{m.points.toLocaleString()}P
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
