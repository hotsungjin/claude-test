import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'
import { formatDate } from '@/utils/format'
import CouponRegisterClient from './CouponRegisterClient'
import MypageHeader from '@/components/store/mypage/MypageHeader'

function formatDiscount(coupon: any): string {
  if (coupon.type === 'amount') return `${coupon.discount_amount.toLocaleString()}원 할인`
  if (coupon.type === 'rate') {
    const s = `${coupon.discount_rate}% 할인`
    return coupon.max_discount ? `${s} (최대 ${coupon.max_discount.toLocaleString()}원)` : s
  }
  return '배송비 무료'
}

export default async function CouponsPage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) redirect('/auth/login')

  const now = new Date().toISOString()

  const { data: issues } = await supabase
    .from('coupon_issues')
    .select('id, status, issued_at, used_at, coupons(name, type, discount_amount, discount_rate, max_discount, min_order_amount, expires_at)')
    .eq('member_id', member.id)
    .order('issued_at', { ascending: false })

  const unused = (issues ?? []).filter((i: any) => i.status === 'unused')
  const used = (issues ?? []).filter((i: any) => i.status === 'used' || i.status === 'expired')

  const isExpired = (issue: any) =>
    issue.coupons?.expires_at && new Date(issue.coupons.expires_at) < new Date()

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="쿠폰함" />

      {/* 쿠폰 코드 등록 */}
      <CouponRegisterClient />

      {/* 사용 가능한 쿠폰 */}
      <div className="mt-6">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">사용 가능한 쿠폰 ({unused.length})</h3>
        {unused.length > 0 ? (
          <div className="space-y-3">
            {unused.map((issue: any) => {
              const expired = isExpired(issue)
              return (
                <div key={issue.id}
                  className={`bg-white rounded-xl p-5 shadow-sm border-l-4 ${expired ? 'border-gray-200 opacity-60' : ''}`}
                  style={!expired ? { borderLeftColor: '#968774' } : undefined}>
                  <div className="flex justify-between items-start">
                    <div>
                      <p className="font-bold text-lg text-gray-900">{formatDiscount(issue.coupons)}</p>
                      <p className="text-sm text-gray-700 mt-0.5">{issue.coupons?.name}</p>
                      {issue.coupons?.min_order_amount > 0 && (
                        <p className="text-xs text-gray-400 mt-1">
                          {issue.coupons.min_order_amount.toLocaleString()}원 이상 구매 시
                        </p>
                      )}
                    </div>
                    <div className="text-right flex-shrink-0 ml-4">
                      {issue.coupons?.expires_at ? (
                        <p className={`text-xs font-medium ${expired ? 'text-red-500' : 'text-gray-500'}`}>
                          {expired ? '만료됨' : `~${formatDate(issue.coupons.expires_at)} 까지`}
                        </p>
                      ) : (
                        <p className="text-xs text-gray-400">기간 무제한</p>
                      )}
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        ) : (
          <div className="bg-white rounded-xl p-8 text-center shadow-sm">
            <p className="text-gray-400 text-sm">사용 가능한 쿠폰이 없습니다.</p>
          </div>
        )}
      </div>

      {/* 사용/만료된 쿠폰 */}
      {used.length > 0 && (
        <div className="mt-8">
          <h3 className="text-sm font-semibold text-gray-400 mb-3">사용 완료 / 만료</h3>
          <div className="space-y-2">
            {used.map((issue: any) => (
              <div key={issue.id} className="bg-gray-50 rounded-xl p-4 border border-gray-100 opacity-60">
                <div className="flex justify-between">
                  <div>
                    <p className="font-medium text-gray-600">{formatDiscount(issue.coupons)}</p>
                    <p className="text-xs text-gray-400 mt-0.5">{issue.coupons?.name}</p>
                  </div>
                  <span className={`text-xs font-medium ${issue.status === 'used' ? 'text-blue-500' : 'text-red-400'}`}>
                    {issue.status === 'used' ? '사용완료' : '만료'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
