import { createClient } from '@/lib/supabase/server'
import { formatPrice, formatDateTime } from '@/utils/format'
import { REFERRAL_RATES } from '@/constants'
import { ADMIN_ITEMS_PER_PAGE } from '@/constants'
import Link from 'next/link'

export default async function AdminReferralPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | undefined }>
}) {
  const params = await searchParams
  const page = Number(params.page ?? '1')
  const search = params.search ?? ''
  const tab = params.tab ?? 'overview'

  const supabase = await createClient() as any

  // 탭별로 필요한 데이터만 조회
  let totalReferrals = 0
  let thisMonthReferrals = 0
  let totalRewardPoints = 0
  let thisMonthRewardPoints = 0
  let gradeCounts: Record<string, number> = {}
  let members: any[] = []
  let rewards: any[] = []
  let totalCount = 0

  if (tab === 'overview') {
    const now = new Date()
    const thisMonthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString()

    // DB에서 합계를 직접 계산하는 RPC 대신, count만 사용하고 합계는 SQL로
    const [
      { count: _totalReferrals },
      { count: _thisMonthReferrals },
      { data: gradeStats },
    ] = await Promise.all([
      supabase.from('referral_members').select('id', { count: 'exact', head: true }),
      supabase.from('referral_members').select('id', { count: 'exact', head: true }).gte('created_at', thisMonthStart),
      supabase.from('referral_members').select('grade'),
    ])

    totalReferrals = _totalReferrals ?? 0
    thisMonthReferrals = _thisMonthReferrals ?? 0

    // 등급별 회원 수
    for (const g of (gradeStats ?? [])) {
      gradeCounts[g.grade] = (gradeCounts[g.grade] ?? 0) + 1
    }

    // 리워드 합계는 SQL RPC로 조회 (전체 데이터를 가져오지 않음)
    const [{ data: totalRewardData }, { data: monthRewardData }] = await Promise.all([
      supabase.rpc('sum_referral_rewards', { p_status: '지급완료' }),
      supabase.rpc('sum_referral_rewards_since', { p_status: '지급완료', p_since: thisMonthStart }),
    ]).catch(() => [{ data: null }, { data: null }])

    totalRewardPoints = totalRewardData ?? 0
    thisMonthRewardPoints = monthRewardData ?? 0

  } else if (tab === 'members') {
    // 실제로 추천한 회원만 조회 (RPC)
    const { data: referrers } = await supabase.rpc('get_referrers', {
      p_search: search,
      p_limit: ADMIN_ITEMS_PER_PAGE,
      p_offset: (page - 1) * ADMIN_ITEMS_PER_PAGE,
    })

    const rows = referrers ?? []
    totalCount = rows.length > 0 ? Number(rows[0].total_count) : 0

    // 등급 일괄 조회
    const memberIds = rows.map((r: any) => r.id)
    const { data: gradeData } = memberIds.length > 0
      ? await supabase.from('referral_members').select('member_id, grade').in('member_id', memberIds)
      : { data: [] }

    const gradeMap: Record<string, string> = {}
    for (const g of (gradeData ?? [])) {
      gradeMap[g.member_id] = g.grade
    }

    members = rows.map((r: any) => ({
      id: r.id,
      name: r.name,
      phone: r.phone,
      referral_code: r.referral_code,
      mileage: r.mileage,
      created_at: r.created_at,
      grade: gradeMap[r.id] ?? '씨앗',
      _invitedCount: Number(r.invite_count),
    }))

  } else if (tab === 'rewards') {
    const { data, count } = await supabase
      .from('referral_rewards')
      .select('*, receiver:receiver_id(name, phone, referral_code), buyer:buyer_id(name, phone)', { count: 'exact' })
      .order('created_at', { ascending: false })
      .range((page - 1) * ADMIN_ITEMS_PER_PAGE, page * ADMIN_ITEMS_PER_PAGE - 1)

    rewards = data ?? []
    totalCount = count ?? 0
  }

  const totalPages = Math.ceil(totalCount / ADMIN_ITEMS_PER_PAGE)

  function buildUrl(overrides: Record<string, string | number>) {
    const p = new URLSearchParams()
    if (params.tab) p.set('tab', params.tab)
    if (params.search) p.set('search', params.search)
    if (params.page) p.set('page', params.page)
    for (const [k, v] of Object.entries(overrides)) {
      p.set(k, String(v))
    }
    return `/admin/referral?${p.toString()}`
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">친구추천 관리</h1>

      {/* 탭 네비게이션 */}
      <div className="flex gap-1 mb-6 border-b border-gray-200">
        {[
          { key: 'overview', label: '현황' },
          { key: 'members', label: '추천 회원' },
          { key: 'rewards', label: '리워드 이력' },
          { key: 'manual', label: '운영 매뉴얼' },
        ].map(t => (
          <Link key={t.key} href={buildUrl({ tab: t.key, page: '1' })}
            className={`px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors ${
              tab === t.key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'
            }`}>
            {t.label}
          </Link>
        ))}
      </div>

      {/* 현황 탭 */}
      {tab === 'overview' && (
        <>
          {/* KPI 카드 */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {[
              { label: '총 추천 건수', value: `${totalReferrals.toLocaleString()}건` },
              { label: '이번 달 추천', value: `${thisMonthReferrals.toLocaleString()}건` },
              { label: '총 지급 리워드', value: `${totalRewardPoints.toLocaleString()}P` },
              { label: '이번 달 리워드', value: `${thisMonthRewardPoints.toLocaleString()}P` },
            ].map((kpi, i) => (
              <div key={i} className="bg-white rounded-xl shadow-sm p-5">
                <p className="text-xs text-gray-500 mb-1">{kpi.label}</p>
                <p className="text-xl font-bold text-gray-900">{kpi.value}</p>
              </div>
            ))}
          </div>

          {/* 등급 분포 */}
          <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">추천 등급 분포</h3>
            <div className="grid grid-cols-5 gap-3">
              {['씨앗', '새싹', '나무', '숲', '목장'].map((g, i) => (
                <div key={g} className="text-center p-3 rounded-lg bg-gray-50">
                  <p className="text-lg font-bold text-gray-900">{gradeCounts[g] ?? 0}</p>
                  <p className="text-xs text-gray-500">{g}</p>
                  <p className="text-[10px] text-gray-400">×{[1.0, 1.25, 1.5, 1.75, 2.0][i]}</p>
                </div>
              ))}
            </div>
          </div>

          {/* 리워드 구조 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">리워드 구조</h3>
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100">
                  <th className="pb-2 text-left text-xs text-gray-500 font-medium">단계</th>
                  <th className="pb-2 text-right text-xs text-gray-500 font-medium">기본 비율</th>
                  <th className="pb-2 text-right text-xs text-gray-500 font-medium">예시 (10만원 주문)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {REFERRAL_RATES.map((rate, idx) => (
                  <tr key={idx}>
                    <td className="py-2.5 text-gray-700">L{idx + 1} {idx === 0 ? '(직접 추천)' : `(${idx + 1}단계)`}</td>
                    <td className="py-2.5 text-right font-medium text-blue-700">{(rate * 100).toFixed(1)}%</td>
                    <td className="py-2.5 text-right text-gray-500">{formatPrice(Math.floor(100000 * rate))}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}

      {/* 추천 회원 탭 */}
      {tab === 'members' && (
        <>
          <form className="flex gap-2 mb-4">
            <input type="hidden" name="tab" value="members" />
            <input name="search" defaultValue={search} placeholder="이름, 전화번호, 추천코드 검색"
              className="flex-1 max-w-md border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-gray-400" />
            <button type="submit"
              className="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">
              검색
            </button>
          </form>

          <div className="bg-white rounded-xl shadow-sm overflow-hidden">
            <div className="px-6 py-3 border-b border-gray-100 text-sm text-gray-500">
              총 {totalCount.toLocaleString()}명
            </div>
            <table className="w-full text-sm">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이름</th>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">전화번호</th>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">추천코드</th>
                  <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">등급</th>
                  <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">추천수</th>
                  <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">포인트</th>
                  <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">가입일</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {members.map((m: any) => (
                  <tr key={m.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-900">
                      <Link href={`/admin/members/${m.id}`} className="hover:underline">{m.name}</Link>
                    </td>
                    <td className="px-4 py-3 text-gray-500">{m.phone}</td>
                    <td className="px-4 py-3 font-mono text-xs text-gray-700">{m.referral_code}</td>
                    <td className="px-4 py-3 text-center">
                      <span className="inline-block px-2 py-0.5 text-xs rounded-full bg-blue-50 text-blue-700">
                        {m.grade}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center text-gray-700">{m._invitedCount}</td>
                    <td className="px-4 py-3 text-right text-gray-700">{m.mileage?.toLocaleString() ?? 0}P</td>
                    <td className="px-4 py-3 text-right text-xs text-gray-400">{formatDateTime(m.created_at)}</td>
                  </tr>
                ))}
                {members.length === 0 && (
                  <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">데이터가 없습니다.</td></tr>
                )}
              </tbody>
            </table>

            {/* 페이지네이션 */}
            {totalPages > 1 && (
              <div className="flex justify-center gap-1 px-4 py-3 border-t border-gray-100">
                {Array.from({ length: totalPages }, (_, i) => i + 1).slice(
                  Math.max(0, page - 3), Math.min(totalPages, page + 2)
                ).map(p => (
                  <Link key={p} href={buildUrl({ page: p })}
                    className={`px-3 py-1 rounded text-sm ${p === page ? 'bg-gray-800 text-white' : 'text-gray-500 hover:bg-gray-100'}`}>
                    {p}
                  </Link>
                ))}
              </div>
            )}
          </div>
        </>
      )}

      {/* 리워드 이력 탭 */}
      {tab === 'rewards' && (
        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <div className="px-6 py-3 border-b border-gray-100 text-sm text-gray-500">
            총 {totalCount.toLocaleString()}건
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">일시</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">수령자</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">구매자</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">단계</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">주문금액</th>
                <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">리워드</th>
                <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {rewards.map((r: any) => (
                <tr key={r.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-xs text-gray-500">{formatDateTime(r.created_at)}</td>
                  <td className="px-4 py-3 text-gray-900">{r.receiver?.name ?? '-'}</td>
                  <td className="px-4 py-3 text-gray-500">{r.buyer?.name ?? '-'}</td>
                  <td className="px-4 py-3 text-center text-gray-700">L{r.depth}</td>
                  <td className="px-4 py-3 text-right text-gray-500">{formatPrice(r.order_amount)}</td>
                  <td className="px-4 py-3 text-right font-medium text-blue-700">+{r.final_points.toLocaleString()}P</td>
                  <td className="px-4 py-3 text-center">
                    <span className={`inline-block px-2 py-0.5 text-xs rounded-full ${
                      r.status === '지급완료' ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-600'
                    }`}>
                      {r.status}
                    </span>
                  </td>
                </tr>
              ))}
              {rewards.length === 0 && (
                <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">리워드 이력이 없습니다.</td></tr>
              )}
            </tbody>
          </table>

          {totalPages > 1 && (
            <div className="flex justify-center gap-1 px-4 py-3 border-t border-gray-100">
              {Array.from({ length: totalPages }, (_, i) => i + 1).slice(
                Math.max(0, page - 3), Math.min(totalPages, page + 2)
              ).map(p => (
                <Link key={p} href={buildUrl({ page: p })}
                  className={`px-3 py-1 rounded text-sm ${p === page ? 'bg-gray-800 text-white' : 'text-gray-500 hover:bg-gray-100'}`}>
                  {p}
                </Link>
              ))}
            </div>
          )}
        </div>
      )}
      {/* 운영 매뉴얼 탭 */}
      {tab === 'manual' && (
        <div className="space-y-6">
          {/* 1. 시스템 개요 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-1">친구추천 리워드 시스템 개요</h3>
            <p className="text-sm text-gray-500 mb-4">회원이 친구를 추천하고, 추천받은 친구가 구매하면 추천인에게 포인트가 지급되는 시스템입니다.</p>
            <div className="bg-blue-50 rounded-lg p-4 text-sm text-blue-800 leading-relaxed">
              <p className="font-semibold mb-1">핵심 흐름</p>
              <p>회원가입 시 추천코드 입력 &rarr; 추천 관계 등록 &rarr; 추천받은 회원이 결제 완료 &rarr; 추천인(최대 5단계)에게 포인트 자동 지급</p>
            </div>
          </div>

          {/* 2. 5단계 추천 구조 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">5단계 추천 리워드 구조</h3>
            <p className="text-sm text-gray-500 mb-4">추천받은 회원이 구매하면, 그 위로 최대 5단계 상위 추천인에게 리워드가 분배됩니다.</p>
            <table className="w-full text-sm mb-4">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="pb-2 text-left text-xs text-gray-500 font-medium">단계</th>
                  <th className="pb-2 text-left text-xs text-gray-500 font-medium">설명</th>
                  <th className="pb-2 text-right text-xs text-gray-500 font-medium">리워드 비율</th>
                  <th className="pb-2 text-right text-xs text-gray-500 font-medium">10만원 주문 시</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {[
                  { level: 'L1', desc: '내가 직접 추천한 친구', rate: '5.0%', example: '5,000P' },
                  { level: 'L2', desc: '내 친구가 추천한 친구', rate: '2.0%', example: '2,000P' },
                  { level: 'L3', desc: '3단계 하위 회원', rate: '1.0%', example: '1,000P' },
                  { level: 'L4', desc: '4단계 하위 회원', rate: '0.5%', example: '500P' },
                  { level: 'L5', desc: '5단계 하위 회원', rate: '0.2%', example: '200P' },
                ].map(r => (
                  <tr key={r.level}>
                    <td className="py-2.5 font-medium text-blue-700">{r.level}</td>
                    <td className="py-2.5 text-gray-700">{r.desc}</td>
                    <td className="py-2.5 text-right font-medium text-gray-900">{r.rate}</td>
                    <td className="py-2.5 text-right text-gray-500">{r.example}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <div className="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
              <p className="font-semibold text-gray-800 mb-2">예시) A &rarr; B &rarr; C &rarr; D 추천 관계일 때, D가 10만원 구매하면:</p>
              <ul className="space-y-1 ml-4 list-disc">
                <li>C (L1, 직접 추천인): <span className="font-medium text-blue-700">5,000P</span></li>
                <li>B (L2, 2단계 상위): <span className="font-medium text-blue-700">2,000P</span></li>
                <li>A (L3, 3단계 상위): <span className="font-medium text-blue-700">1,000P</span></li>
              </ul>
            </div>
          </div>

          {/* 3. 등급 시스템 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">추천인 등급 시스템</h3>
            <p className="text-sm text-gray-500 mb-4">직접 추천(L1) 수에 따라 등급이 결정되며, 등급이 높을수록 리워드에 배율이 적용됩니다.</p>
            <table className="w-full text-sm mb-4">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="pb-2 text-left text-xs text-gray-500 font-medium">등급</th>
                  <th className="pb-2 text-center text-xs text-gray-500 font-medium">조건 (직접 추천 수)</th>
                  <th className="pb-2 text-right text-xs text-gray-500 font-medium">리워드 배율</th>
                  <th className="pb-2 text-right text-xs text-gray-500 font-medium">L1 실질 비율</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {[
                  { emoji: '🌱', grade: '씨앗', cond: '0명 이상', mult: '×1.0', effective: '5.0%' },
                  { emoji: '🌿', grade: '새싹', cond: '2명 이상', mult: '×1.25', effective: '6.25%' },
                  { emoji: '🌳', grade: '나무', cond: '5명 이상', mult: '×1.5', effective: '7.5%' },
                  { emoji: '🌲', grade: '숲', cond: '10명 이상', mult: '×1.75', effective: '8.75%' },
                  { emoji: '🐄', grade: '목장', cond: '20명 이상', mult: '×2.0', effective: '10.0%' },
                ].map(g => (
                  <tr key={g.grade}>
                    <td className="py-2.5 text-gray-700">{g.emoji} {g.grade}</td>
                    <td className="py-2.5 text-center text-gray-700">{g.cond}</td>
                    <td className="py-2.5 text-right font-medium text-blue-700">{g.mult}</td>
                    <td className="py-2.5 text-right text-gray-500">{g.effective}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <div className="bg-amber-50 rounded-lg p-4 text-sm text-amber-800">
              <p className="font-semibold mb-1">배율 적용 예시</p>
              <p>숲 등급(×1.75) 회원의 L1 추천인이 10만원 구매 시: 100,000 × 5% × 1.75 = <span className="font-bold">8,750P</span></p>
            </div>
          </div>

          {/* 4. 리워드 지급/취소 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">리워드 지급 및 취소 규칙</h3>
            <div className="space-y-4">
              <div className="bg-green-50 rounded-lg p-4 text-sm">
                <p className="font-semibold text-green-800 mb-2">지급 조건</p>
                <ul className="space-y-1 ml-4 list-disc text-green-700">
                  <li>추천받은 회원의 <span className="font-medium">결제 완료</span> 시점에 자동 지급</li>
                  <li>추천 관계 등록일로부터 <span className="font-medium">1년 이내</span>에 발생한 주문만 유효</li>
                  <li>리워드는 <span className="font-medium">포인트(마일리지)</span>로 즉시 적립</li>
                  <li><code className="bg-green-100 px-1 rounded">referral_rewards</code> 테이블에 기록 + 회원 마일리지에 합산</li>
                </ul>
              </div>
              <div className="bg-red-50 rounded-lg p-4 text-sm">
                <p className="font-semibold text-red-800 mb-2">취소 조건</p>
                <ul className="space-y-1 ml-4 list-disc text-red-700">
                  <li>해당 주문이 <span className="font-medium">결제 취소</span>되면 리워드도 자동 취소</li>
                  <li>리워드 상태가 &apos;지급완료&apos; &rarr; &apos;취소&apos;로 변경</li>
                  <li>지급된 포인트만큼 회원 마일리지에서 <span className="font-medium">차감</span></li>
                </ul>
              </div>
            </div>
          </div>

          {/* 5. 추천코드 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">추천코드 안내</h3>
            <div className="space-y-3 text-sm text-gray-700">
              <div className="flex gap-3">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">1</span>
                <p>회원가입 시 <span className="font-medium">6자리 영숫자 추천코드</span>가 자동 생성됩니다 (예: <code className="bg-gray-100 px-1 rounded">K3NP7V</code>)</p>
              </div>
              <div className="flex gap-3">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">2</span>
                <p>신규 회원이 가입 시 추천코드를 입력하면 <span className="font-medium">추천 관계가 등록</span>됩니다</p>
              </div>
              <div className="flex gap-3">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">3</span>
                <p>추천 관계는 <span className="font-medium">가입일로부터 1년간 유효</span>하며, 1년 후에는 리워드가 발생하지 않습니다</p>
              </div>
              <div className="flex gap-3">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">4</span>
                <p>혼동 방지를 위해 <span className="font-medium">0, 1, I, O 문자는 제외</span>됩니다</p>
              </div>
            </div>
          </div>

          {/* 6. 관련 DB 테이블 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">관련 데이터 구조</h3>
            <div className="space-y-4 text-sm">
              <div>
                <p className="font-semibold text-gray-800 mb-2">주요 테이블</p>
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="pb-2 text-left text-xs text-gray-500 font-medium">테이블</th>
                      <th className="pb-2 text-left text-xs text-gray-500 font-medium">역할</th>
                      <th className="pb-2 text-left text-xs text-gray-500 font-medium">주요 필드</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-50">
                    <tr>
                      <td className="py-2 font-mono text-xs text-blue-700">members</td>
                      <td className="py-2 text-gray-700">회원 정보</td>
                      <td className="py-2 text-gray-500">referral_code, referred_by, mileage</td>
                    </tr>
                    <tr>
                      <td className="py-2 font-mono text-xs text-blue-700">referral_members</td>
                      <td className="py-2 text-gray-700">추천인 등급 관리</td>
                      <td className="py-2 text-gray-500">member_id, grade, grade_multiplier</td>
                    </tr>
                    <tr>
                      <td className="py-2 font-mono text-xs text-blue-700">referral_tree</td>
                      <td className="py-2 text-gray-700">추천 계보 (조상-자손 관계)</td>
                      <td className="py-2 text-gray-500">ancestor_member_id, descendant_member_id, depth</td>
                    </tr>
                    <tr>
                      <td className="py-2 font-mono text-xs text-blue-700">referral_rewards</td>
                      <td className="py-2 text-gray-700">리워드 지급/취소 이력</td>
                      <td className="py-2 text-gray-500">receiver_id, buyer_id, depth, final_points, status</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          {/* 7. 고객 문의 대응 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">자주 묻는 질문 (고객 문의 대응)</h3>
            <div className="space-y-4 text-sm">
              {[
                {
                  q: '추천 리워드가 지급되지 않아요',
                  a: '1) 추천 관계가 등록되어 있는지 확인 (members.referred_by)\n2) 추천일로부터 1년이 경과했는지 확인\n3) 주문 상태가 결제 완료인지 확인\n4) referral_tree에 해당 관계가 있는지 확인',
                },
                {
                  q: '리워드가 갑자기 사라졌어요',
                  a: '해당 주문이 취소/환불 처리되면 리워드도 자동 취소됩니다. 리워드 이력 탭에서 해당 건의 상태를 확인하세요.',
                },
                {
                  q: '추천인 등급이 올라가지 않아요',
                  a: '등급은 직접 추천(L1) 수 기준입니다. 2단계 이하(L2~L5) 추천인은 등급 산정에 포함되지 않습니다.',
                },
                {
                  q: '추천코드를 변경하고 싶어요',
                  a: '추천코드는 가입 시 자동 생성되며, 변경 기능은 제공되지 않습니다.',
                },
                {
                  q: '1년이 지나면 어떻게 되나요?',
                  a: '추천 관계 등록일(가입일)로부터 1년이 지나면, 해당 추천인의 구매에 대한 리워드가 더 이상 발생하지 않습니다. 기존에 지급된 리워드는 유지됩니다.',
                },
              ].map((faq, idx) => (
                <div key={idx} className="border border-gray-100 rounded-lg p-4">
                  <p className="font-semibold text-gray-900 mb-2">Q. {faq.q}</p>
                  <p className="text-gray-600 whitespace-pre-line">A. {faq.a}</p>
                </div>
              ))}
            </div>
          </div>

          {/* 8. 운영 시 주의사항 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">운영 시 주의사항</h3>
            <div className="space-y-3 text-sm">
              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 mt-0.5 w-5 h-5 rounded bg-red-100 text-red-600 text-xs font-bold flex items-center justify-center">!</span>
                <p className="text-gray-700">리워드 비율(L1~L5)이나 등급 배율을 변경하려면 <span className="font-medium">소스 코드 수정</span>이 필요합니다. (<code className="bg-gray-100 px-1 rounded text-xs">src/constants/index.ts</code>의 REFERRAL_RATES, <code className="bg-gray-100 px-1 rounded text-xs">src/lib/referral.ts</code>의 calculateGrade)</p>
              </div>
              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 mt-0.5 w-5 h-5 rounded bg-red-100 text-red-600 text-xs font-bold flex items-center justify-center">!</span>
                <p className="text-gray-700">회원의 마일리지를 수동으로 조정할 경우, <span className="font-medium">referral_rewards 기록과 불일치</span>가 발생할 수 있으니 주의하세요.</p>
              </div>
              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 mt-0.5 w-5 h-5 rounded bg-amber-100 text-amber-600 text-xs font-bold flex items-center justify-center">i</span>
                <p className="text-gray-700">추천 리워드는 <span className="font-medium">결제 완료 시 자동</span>으로 처리되며, 수동 지급 기능은 없습니다.</p>
              </div>
              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 mt-0.5 w-5 h-5 rounded bg-amber-100 text-amber-600 text-xs font-bold flex items-center justify-center">i</span>
                <p className="text-gray-700">실시간 랭킹은 <span className="font-medium">고객 마이페이지</span>에서 확인 가능하며, 리워드 총액 기준으로 순위가 매겨집니다.</p>
              </div>
              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 mt-0.5 w-5 h-5 rounded bg-amber-100 text-amber-600 text-xs font-bold flex items-center justify-center">i</span>
                <p className="text-gray-700">부정 추천(자기 추천, 대량 허위 가입 등)이 의심될 경우, <span className="font-medium">추천 회원 탭</span>에서 해당 회원의 추천 수와 패턴을 확인하세요.</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
