import { createAdminClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import { ChevronRight } from 'lucide-react'
import { formatDateTime, formatPrice, ORDER_STATUS_LABEL } from '@/utils/format'
import MemberActionClient from './MemberActionClient'

const GRADE_COLOR: Record<string, string> = {
  bronze: 'bg-gray-100 text-gray-600',
  silver: 'bg-blue-100 text-blue-700',
  gold:   'bg-yellow-100 text-yellow-700',
  vip:    'bg-purple-100 text-purple-700',
}

const GRADE_LABEL: Record<string, string> = {
  bronze: '브론즈',
  silver: '실버',
  gold:   '골드',
  vip:    'VIP',
}

export default async function AdminMemberDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient() as any

  const [{ data: member }, { data: orders }, { data: mileageLogs }] = await Promise.all([
    supabase.from('members').select('*').eq('id', id).single(),
    supabase.from('orders').select('id, order_no, status, total_amount, created_at')
      .eq('member_id', id).order('created_at', { ascending: false }).limit(10),
    supabase.from('mileage_logs').select('*').eq('member_id', id)
      .order('created_at', { ascending: false }).limit(20),
  ])

  if (!member) notFound()

  return (
    <div>
      <nav className="flex items-center gap-1 text-sm text-gray-500 mb-6">
        <Link href="/admin/members" className="hover:text-green-700">회원 관리</Link>
        <ChevronRight className="w-4 h-4" />
        <span className="text-gray-900 font-medium">{member.name}</span>
      </nav>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{member.name}</h1>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          {/* 기본 정보 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">기본 정보</h2>
            <dl className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">이메일</dt>
                <dd className="font-medium text-gray-900">{member.email}</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">연락처</dt>
                <dd className="text-gray-700">{member.phone ?? '-'}</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">등급</dt>
                <dd>
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${GRADE_COLOR[member.grade] ?? 'bg-gray-100'}`}>
                    {GRADE_LABEL[member.grade] ?? member.grade}
                  </span>
                </dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">마일리지</dt>
                <dd className="font-medium text-green-700">{(member.mileage ?? 0).toLocaleString()}P</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">가입일</dt>
                <dd className="text-gray-600">{formatDateTime(member.created_at)}</dd>
              </div>
              <div>
                <dt className="text-gray-500 text-xs mb-0.5">계정 상태</dt>
                <dd className={`text-xs font-medium ${member.is_active ? 'text-green-600' : 'text-red-600'}`}>
                  {member.is_active ? '활성' : '비활성'}
                </dd>
              </div>
            </dl>
          </section>

          {/* 최근 주문 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">최근 주문</h2>
            {(orders ?? []).length > 0 ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100">
                    <th className="pb-2 text-left text-xs text-gray-500 font-medium">주문번호</th>
                    <th className="pb-2 text-left text-xs text-gray-500 font-medium">주문일</th>
                    <th className="pb-2 text-right text-xs text-gray-500 font-medium">금액</th>
                    <th className="pb-2 text-center text-xs text-gray-500 font-medium">상태</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {(orders ?? []).map((o: any) => (
                    <tr key={o.id}>
                      <td className="py-2">
                        <Link href={`/admin/orders/${o.id}`} className="text-blue-600 hover:underline font-mono text-xs">
                          {o.order_no}
                        </Link>
                      </td>
                      <td className="py-2 text-xs text-gray-500">{formatDateTime(o.created_at)}</td>
                      <td className="py-2 text-right font-medium">{formatPrice(o.total_amount)}</td>
                      <td className="py-2 text-center">
                        <span className="text-xs text-gray-600">{ORDER_STATUS_LABEL[o.status] ?? o.status}</span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="text-sm text-gray-400">주문 내역이 없습니다.</p>
            )}
          </section>

          {/* 마일리지 이력 */}
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">마일리지 이력</h2>
            {(mileageLogs ?? []).length > 0 ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100">
                    <th className="pb-2 text-left text-xs text-gray-500 font-medium">일시</th>
                    <th className="pb-2 text-left text-xs text-gray-500 font-medium">사유</th>
                    <th className="pb-2 text-right text-xs text-gray-500 font-medium">변동</th>
                    <th className="pb-2 text-right text-xs text-gray-500 font-medium">잔액</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {(mileageLogs ?? []).map((log: any) => (
                    <tr key={log.id}>
                      <td className="py-2 text-xs text-gray-500">{formatDateTime(log.created_at)}</td>
                      <td className="py-2 text-xs text-gray-700">{log.reason}</td>
                      <td className={`py-2 text-right text-xs font-medium ${log.delta > 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {log.delta > 0 ? '+' : ''}{log.delta.toLocaleString()}P
                      </td>
                      <td className="py-2 text-right text-xs text-gray-700">{log.balance.toLocaleString()}P</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="text-sm text-gray-400">마일리지 이력이 없습니다.</p>
            )}
          </section>
        </div>

        {/* 우측: 관리 액션 */}
        <div>
          <section className="bg-white rounded-xl shadow-sm p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-4">회원 관리</h2>
            <MemberActionClient
              memberId={member.id}
              currentGrade={member.grade}
              currentMileage={member.mileage ?? 0}
              isActive={member.is_active}
            />
          </section>
        </div>
      </div>
    </div>
  )
}
