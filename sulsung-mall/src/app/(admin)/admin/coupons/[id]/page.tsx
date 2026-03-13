import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import CouponFormClient from '../CouponFormClient'
import CouponIssueClient from '../CouponIssueClient'

export default async function EditCouponPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data: coupon } = await supabase.from('coupons').select('*').eq('id', id).single()
  if (!coupon) notFound()

  const [
    { count: issueCount },
    { count: usedCount },
    { data: grades },
  ] = await Promise.all([
    supabase.from('coupon_issues').select('*', { count: 'exact', head: true }).eq('coupon_id', id),
    supabase.from('coupon_issues').select('*', { count: 'exact', head: true }).eq('coupon_id', id).eq('status', 'used'),
    supabase.from('members').select('grade').neq('grade', null),
  ])

  const uniqueGrades = [...new Set((grades ?? []).map((m: any) => m.grade).filter(Boolean))] as string[]

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-2">쿠폰 수정</h1>
      <p className="text-sm text-gray-500 mb-6">
        발급 {issueCount ?? 0}건 · 사용 {usedCount ?? 0}건
      </p>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div className="lg:col-span-2">
          <CouponFormClient coupon={coupon} />
        </div>
        <div>
          <CouponIssueClient couponId={id} grades={uniqueGrades} />
        </div>
      </div>
    </div>
  )
}
