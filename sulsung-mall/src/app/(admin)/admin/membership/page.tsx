import { createClient } from '@/lib/supabase/server'
import { formatPrice, formatDateTime } from '@/utils/format'
import { ADMIN_ITEMS_PER_PAGE, GRADE_BENEFITS, GRADE_LABEL } from '@/constants'
import Link from 'next/link'

export default async function AdminMembershipPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | undefined }>
}) {
  const params = await searchParams
  const page = Number(params.page ?? '1')
  const search = params.search ?? ''
  const tab = params.tab ?? 'overview'
  const statusFilter = params.status ?? 'all'

  const supabase = await createClient() as any

  let totalMembers = 0
  let activeSilver = 0
  let subActive = 0
  let subCancelled = 0
  let gradeCounts: Record<string, number> = {}
  let subscriptions: any[] = []
  let plans: any[] = []
  let totalCount = 0

  if (tab === 'overview') {
    const [
      { count: _totalMembers },
      { count: _activeSilver },
      { count: _subActive },
      { count: _subCancelled },
      { data: gradeData },
    ] = await Promise.all([
      supabase.from('members').select('id', { count: 'exact', head: true }).in('grade', ['silver', 'gold', 'vip']),
      supabase.from('members').select('id', { count: 'exact', head: true }).eq('grade', 'silver'),
      supabase.from('membership_subscriptions').select('id', { count: 'exact', head: true }).eq('status', 'active'),
      supabase.from('membership_subscriptions').select('id', { count: 'exact', head: true }).eq('status', 'cancelled'),
      supabase.from('members').select('grade').in('grade', ['silver', 'gold', 'vip']),
    ])

    totalMembers = _totalMembers ?? 0
    activeSilver = _activeSilver ?? 0
    subActive = _subActive ?? 0
    subCancelled = _subCancelled ?? 0

    for (const g of (gradeData ?? [])) {
      gradeCounts[g.grade] = (gradeCounts[g.grade] ?? 0) + 1
    }

  } else if (tab === 'subscribers') {
    let query = supabase
      .from('members')
      .select('id, name, phone, email, grade, mileage, created_at', { count: 'exact' })
      .in('grade', ['silver', 'gold', 'vip'])
      .order('created_at', { ascending: false })

    if (search) {
      query = query.or(`name.ilike.%${search}%,phone.ilike.%${search}%`)
    }

    const { data, count } = await query
      .range((page - 1) * ADMIN_ITEMS_PER_PAGE, page * ADMIN_ITEMS_PER_PAGE - 1)

    subscriptions = data ?? []
    totalCount = count ?? 0

  } else if (tab === 'plans') {
    const { data } = await supabase
      .from('membership_plans')
      .select('*')
      .order('price')

    plans = data ?? []
  }

  const totalPages = Math.ceil(totalCount / ADMIN_ITEMS_PER_PAGE)

  function buildUrl(overrides: Record<string, string | number>) {
    const p = new URLSearchParams()
    if (params.tab) p.set('tab', params.tab)
    if (params.search) p.set('search', params.search)
    if (params.page) p.set('page', params.page)
    if (params.status) p.set('status', params.status)
    for (const [k, v] of Object.entries(overrides)) {
      p.set(k, String(v))
    }
    return `/admin/membership?${p.toString()}`
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">멤버십 관리</h1>

      {/* 탭 네비게이션 */}
      <div className="flex gap-1 mb-6 border-b border-gray-200">
        {[
          { key: 'overview', label: '현황' },
          { key: 'subscribers', label: '멤버십 회원' },
          { key: 'plans', label: '플랜 관리' },
          { key: 'manual', label: '운영 메뉴얼' },
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
              { label: '멤버십 회원', value: `${totalMembers.toLocaleString()}명`, color: 'text-blue-700' },
              { label: '활성 (실버)', value: `${activeSilver.toLocaleString()}명`, color: 'text-green-700' },
              { label: '활성 구독', value: `${subActive.toLocaleString()}건`, color: 'text-gray-900' },
              { label: '해지 누적', value: `${subCancelled.toLocaleString()}건`, color: 'text-red-600' },
            ].map((kpi, i) => (
              <div key={i} className="bg-white rounded-xl shadow-sm p-5">
                <p className="text-xs text-gray-500 mb-1">{kpi.label}</p>
                <p className={`text-xl font-bold ${kpi.color}`}>{kpi.value}</p>
              </div>
            ))}
          </div>

          {/* 등급 분포 */}
          <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">멤버십 등급 분포</h3>
            <div className="grid grid-cols-3 gap-3">
              {[
                { key: 'silver', label: GRADE_LABEL.silver, desc: '월간/연간 멤버십' },
                { key: 'gold', label: GRADE_LABEL.gold, desc: '미사용' },
                { key: 'vip', label: GRADE_LABEL.vip, desc: '미사용' },
              ].map(g => (
                <div key={g.key} className="text-center p-4 rounded-lg bg-gray-50">
                  <p className="text-2xl font-bold text-gray-900">{gradeCounts[g.key] ?? 0}</p>
                  <p className="text-sm font-medium text-gray-700 mt-1">{g.label}</p>
                  <p className="text-[11px] text-gray-400">{g.desc}</p>
                </div>
              ))}
            </div>
          </div>

          {/* 등급별 혜택 비교 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">등급별 혜택 비교</h3>
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100">
                  <th className="pb-2 text-left text-xs text-gray-500 font-medium">혜택</th>
                  <th className="pb-2 text-center text-xs text-gray-500 font-medium">{GRADE_LABEL.bronze}</th>
                  <th className="pb-2 text-center text-xs text-gray-500 font-medium">{GRADE_LABEL.silver}</th>
                  <th className="pb-2 text-center text-xs text-gray-500 font-medium">{GRADE_LABEL.gold}</th>
                  <th className="pb-2 text-center text-xs text-gray-500 font-medium">{GRADE_LABEL.vip}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                <tr>
                  <td className="py-2.5 text-gray-700">무료배송 기준</td>
                  {(['bronze', 'silver', 'gold', 'vip'] as const).map(g => (
                    <td key={g} className="py-2.5 text-center text-gray-600">
                      {GRADE_BENEFITS[g].freeShippingThreshold === 0 ? '무료' : formatPrice(GRADE_BENEFITS[g].freeShippingThreshold)}
                    </td>
                  ))}
                </tr>
                <tr>
                  <td className="py-2.5 text-gray-700">포인트 적립률</td>
                  {(['bronze', 'silver', 'gold', 'vip'] as const).map(g => (
                    <td key={g} className="py-2.5 text-center text-gray-600">{GRADE_BENEFITS[g].mileageRate * 100}%</td>
                  ))}
                </tr>
                <tr>
                  <td className="py-2.5 text-gray-700">생일 쿠폰</td>
                  {(['bronze', 'silver', 'gold', 'vip'] as const).map(g => (
                    <td key={g} className="py-2.5 text-center text-gray-600">{formatPrice(GRADE_BENEFITS[g].birthdayCoupon)}</td>
                  ))}
                </tr>
                <tr>
                  <td className="py-2.5 text-gray-700">멤버십 전용가</td>
                  <td className="py-2.5 text-center text-gray-400">-</td>
                  <td className="py-2.5 text-center text-blue-700 font-medium">적용</td>
                  <td className="py-2.5 text-center text-blue-700 font-medium">적용</td>
                  <td className="py-2.5 text-center text-blue-700 font-medium">적용</td>
                </tr>
              </tbody>
            </table>
          </div>
        </>
      )}

      {/* 멤버십 회원 탭 */}
      {tab === 'subscribers' && (
        <>
          <form className="flex gap-2 mb-4">
            <input type="hidden" name="tab" value="subscribers" />
            <input name="search" defaultValue={search} placeholder="이름, 전화번호 검색"
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
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이메일</th>
                  <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">등급</th>
                  <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">포인트</th>
                  <th className="px-4 py-3 text-right text-xs text-gray-500 font-medium">가입일</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {subscriptions.map((m: any) => (
                  <tr key={m.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-900">
                      <Link href={`/admin/members/${m.id}`} className="hover:underline">{m.name}</Link>
                    </td>
                    <td className="px-4 py-3 text-gray-500">{m.phone}</td>
                    <td className="px-4 py-3 text-gray-500">{m.email ?? '-'}</td>
                    <td className="px-4 py-3 text-center">
                      <span className="inline-block px-2 py-0.5 text-xs rounded-full bg-blue-50 text-blue-700">
                        {GRADE_LABEL[m.grade] ?? m.grade}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right text-gray-700">{(m.mileage ?? 0).toLocaleString()}P</td>
                    <td className="px-4 py-3 text-right text-xs text-gray-400">{formatDateTime(m.created_at)}</td>
                  </tr>
                ))}
                {subscriptions.length === 0 && (
                  <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">멤버십 회원이 없습니다.</td></tr>
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
        </>
      )}

      {/* 플랜 관리 탭 */}
      {tab === 'plans' && (
        <div className="space-y-4">
          {plans.map((plan: any) => (
            <div key={plan.id} className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="text-lg font-bold text-gray-900">{plan.name}</h3>
                    <span className={`text-xs px-2 py-0.5 rounded-full ${
                      plan.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'
                    }`}>
                      {plan.is_active ? '활성' : '비활성'}
                    </span>
                  </div>
                  {plan.description && (
                    <p className="text-sm text-gray-500 mt-1">{plan.description}</p>
                  )}
                </div>
                <div className="text-right">
                  <p className="text-2xl font-extrabold text-gray-900">{formatPrice(plan.price)}</p>
                  <p className="text-sm text-gray-500">/ {plan.interval === 'monthly' ? '월' : '년'}</p>
                </div>
              </div>
              <div className="mt-4 pt-4 border-t border-gray-100 grid grid-cols-3 gap-4 text-sm">
                <div>
                  <p className="text-xs text-gray-400">구독 기간</p>
                  <p className="text-gray-700 font-medium">{plan.duration_days}일</p>
                </div>
                <div>
                  <p className="text-xs text-gray-400">결제 주기</p>
                  <p className="text-gray-700 font-medium">{plan.interval === 'monthly' ? '월간' : '연간'}</p>
                </div>
                {plan.interval === 'yearly' && (
                  <div>
                    <p className="text-xs text-gray-400">월 환산 금액</p>
                    <p className="text-gray-700 font-medium">{formatPrice(Math.floor(plan.price / 12))}</p>
                  </div>
                )}
              </div>
            </div>
          ))}
          {plans.length === 0 && (
            <div className="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400">
              등록된 플랜이 없습니다.
            </div>
          )}
        </div>
      )}

      {/* 운영 메뉴얼 탭 */}
      {tab === 'manual' && (
        <div className="space-y-6">

          {/* 1. 멤버십 개요 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">1. 멤버십 개요</h3>
            <div className="space-y-3 text-sm text-gray-700 leading-relaxed">
              <p>
                <strong>&quot;오늘도설성&quot; 멤버십</strong>은 설성목장몰의 유료 구독형 서비스입니다.
                고객이 멤버십 상품을 구매하면 <strong>자동으로 일반 회원에서 오늘도설성(silver) 등급으로 전환</strong>됩니다.
              </p>
              <div className="bg-blue-50 rounded-lg p-4 mt-3">
                <p className="text-blue-800 font-medium mb-2">멤버십 상품 정보</p>
                <ul className="text-blue-700 space-y-1">
                  <li>상품 페이지: <code className="bg-blue-100 px-1.5 py-0.5 rounded text-xs">/goods/godomall-2636</code></li>
                  <li>이 상품을 구매 &rarr; 결제 완료 &rarr; 자동으로 멤버십 활성화</li>
                  <li>별도의 수동 작업이 필요 없습니다</li>
                </ul>
              </div>
            </div>
          </div>

          {/* 2. 등급별 혜택 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">2. 등급별 혜택 비교</h3>
            <table className="w-full text-sm border-collapse">
              <thead>
                <tr className="bg-gray-50">
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium border-b">혜택 항목</th>
                  <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium border-b">일반 (bronze)</th>
                  <th className="px-4 py-3 text-center text-xs font-medium border-b bg-amber-50 text-amber-800">오늘도설성 (silver)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                <tr>
                  <td className="px-4 py-3 text-gray-700">무료배송 기준</td>
                  <td className="px-4 py-3 text-center text-gray-600">50,000원 이상</td>
                  <td className="px-4 py-3 text-center font-medium bg-amber-50 text-amber-800">30,000원 이상</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 text-gray-700">포인트 적립률</td>
                  <td className="px-4 py-3 text-center text-gray-600">1%</td>
                  <td className="px-4 py-3 text-center font-medium bg-amber-50 text-amber-800">3%</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 text-gray-700">생일 쿠폰</td>
                  <td className="px-4 py-3 text-center text-gray-600">3,000원</td>
                  <td className="px-4 py-3 text-center font-medium bg-amber-50 text-amber-800">10,000원</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 text-gray-700">멤버십 전용가</td>
                  <td className="px-4 py-3 text-center text-gray-400">적용 안됨</td>
                  <td className="px-4 py-3 text-center font-medium bg-amber-50 text-amber-800">적용</td>
                </tr>
              </tbody>
            </table>
            <p className="text-xs text-gray-400 mt-3">
              * 멤버십 전용가는 상품별로 설정되며, 관리자 &gt; 상품관리에서 &quot;멤버십가(member_price)&quot; 필드에 입력된 상품에만 적용됩니다.
            </p>
          </div>

          {/* 3. 자동 처리 흐름 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">3. 자동 처리 흐름</h3>
            <div className="space-y-4">

              <div>
                <h4 className="text-sm font-semibold text-green-700 mb-2">가입 (자동)</h4>
                <div className="bg-green-50 rounded-lg p-4">
                  <ol className="text-sm text-green-800 space-y-2 list-decimal list-inside">
                    <li>고객이 멤버십 상품(godomall-2636) 장바구니에 담기</li>
                    <li>주문서 작성 &rarr; 결제 진행</li>
                    <li>결제 완료 (카드/가상계좌 입금)</li>
                    <li><strong>시스템이 자동으로 처리:</strong>
                      <ul className="ml-6 mt-1 space-y-1 list-disc">
                        <li>회원 등급: 일반 &rarr; 오늘도설성(silver) 변경</li>
                        <li>멤버십 구독 레코드 생성 (시작일, 만료일 자동 설정)</li>
                        <li>결제 금액에 맞는 플랜(월/연) 자동 매칭</li>
                      </ul>
                    </li>
                  </ol>
                </div>
              </div>

              <div>
                <h4 className="text-sm font-semibold text-amber-700 mb-2">연장 (자동)</h4>
                <div className="bg-amber-50 rounded-lg p-4">
                  <p className="text-sm text-amber-800">
                    이미 멤버십 회원인 고객이 다시 멤버십 상품을 구매하면, <strong>기존 만료일에서 추가 기간이 연장</strong>됩니다.
                    (예: 만료일이 4/15인데 30일 플랜 재구매 &rarr; 만료일 5/15로 연장)
                  </p>
                </div>
              </div>

              <div>
                <h4 className="text-sm font-semibold text-red-700 mb-2">해지 (자동)</h4>
                <div className="bg-red-50 rounded-lg p-4">
                  <p className="text-sm text-red-800 mb-2">아래 상황에서 멤버십이 <strong>자동으로 해지</strong>됩니다:</p>
                  <ul className="text-sm text-red-800 space-y-1 list-disc list-inside">
                    <li>멤버십 상품 주문이 <strong>취소</strong>되었을 때</li>
                    <li>멤버십 상품 주문이 <strong>환불/반품 완료</strong>되었을 때</li>
                    <li>고객이 마이페이지에서 <strong>직접 해지</strong>했을 때</li>
                  </ul>
                  <p className="text-sm text-red-800 mt-2">해지 시 등급은 일반(bronze)으로 복원됩니다.</p>
                </div>
              </div>
            </div>
          </div>

          {/* 4. 운영자 주의사항 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">4. 운영자 주의사항</h3>
            <div className="space-y-4 text-sm text-gray-700">

              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-bold">!</span>
                <div>
                  <p className="font-semibold text-gray-900">멤버십 상품 주문 취소 시</p>
                  <p className="text-gray-600 mt-1">
                    관리자가 멤버십 상품이 포함된 주문을 취소하면 해당 고객의 멤버십도 자동 해지됩니다.
                    취소 전에 고객에게 멤버십 해지 사실을 안내해주세요.
                  </p>
                </div>
              </div>

              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">!</span>
                <div>
                  <p className="font-semibold text-gray-900">수동 등급 변경 시</p>
                  <p className="text-gray-600 mt-1">
                    관리자 &gt; 회원관리에서 회원 등급을 수동으로 변경할 수 있지만,
                    멤버십 구독 레코드는 별도로 관리되므로 <strong>등급만 변경하면 멤버십 상태와 불일치</strong>가 발생할 수 있습니다.
                    가급적 등급은 시스템이 자동으로 관리하도록 두세요.
                  </p>
                </div>
              </div>

              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">i</span>
                <div>
                  <p className="font-semibold text-gray-900">멤버십 전용가 설정</p>
                  <p className="text-gray-600 mt-1">
                    상품의 멤버십 전용가는 관리자 &gt; 상품관리 &gt; 상품 수정 화면에서 <strong>&quot;멤버십가&quot;</strong> 필드에 금액을 입력하면 됩니다.
                    멤버십가가 설정된 상품만 오늘도설성 회원에게 할인가가 적용됩니다.
                  </p>
                </div>
              </div>

              <div className="flex gap-3 items-start">
                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">i</span>
                <div>
                  <p className="font-semibold text-gray-900">플랜과 상품 가격 매칭</p>
                  <p className="text-gray-600 mt-1">
                    멤버십 상품의 판매가(또는 옵션 가격)와 플랜 관리 탭의 플랜 가격이 <strong>정확히 일치</strong>해야 올바른 플랜이 매칭됩니다.
                    플랜 가격을 변경할 경우, 멤버십 상품의 가격도 함께 수정해주세요.
                  </p>
                </div>
              </div>

            </div>
          </div>

          {/* 5. 고객 문의 대응 가이드 */}
          <div className="bg-white rounded-xl shadow-sm p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">5. 고객 문의 대응 가이드</h3>
            <div className="space-y-4">

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-sm font-semibold text-gray-900 mb-2">Q. &quot;멤버십 가입했는데 혜택이 안 보여요&quot;</p>
                <div className="text-sm text-gray-600 space-y-1">
                  <p><strong>확인 순서:</strong></p>
                  <ol className="list-decimal list-inside space-y-1 ml-2">
                    <li>관리자 &gt; 회원관리에서 해당 고객의 등급이 &quot;오늘도설성&quot;인지 확인</li>
                    <li>멤버십 회원 탭에서 구독 상태가 &quot;활성&quot;인지 확인</li>
                    <li>주문 내역에서 멤버십 상품 결제가 정상 완료되었는지 확인</li>
                    <li>등급이 bronze로 되어있다면 &rarr; 결제 상태 확인 (미결제/취소 가능성)</li>
                  </ol>
                </div>
              </div>

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-sm font-semibold text-gray-900 mb-2">Q. &quot;멤버십 해지하고 싶어요&quot;</p>
                <div className="text-sm text-gray-600 space-y-1">
                  <p>고객이 마이페이지 &gt; 멤버십에서 직접 해지 가능합니다.</p>
                  <p>해지 즉시 등급이 일반으로 변경되며, 남은 기간에 대한 환불은 별도 정책에 따릅니다.</p>
                </div>
              </div>

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-sm font-semibold text-gray-900 mb-2">Q. &quot;멤버십 만료일이 언제예요?&quot;</p>
                <div className="text-sm text-gray-600 space-y-1">
                  <p>고객: 마이페이지 &gt; 멤버십에서 만료일 확인 가능</p>
                  <p>관리자: 멤버십 회원 탭에서 해당 고객 클릭 &rarr; 구독 상세 확인</p>
                </div>
              </div>

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-sm font-semibold text-gray-900 mb-2">Q. &quot;멤버십가가 적용이 안 돼요&quot;</p>
                <div className="text-sm text-gray-600 space-y-1">
                  <p>모든 상품에 멤버십가가 적용되는 것은 아닙니다.</p>
                  <p>관리자 &gt; 상품관리에서 해당 상품의 &quot;멤버십가(member_price)&quot; 필드가 설정되어 있는지 확인해주세요.</p>
                </div>
              </div>

            </div>
          </div>

        </div>
      )}
    </div>
  )
}
