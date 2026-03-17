import { NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'

export async function GET() {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ coupons: [] })

  const { data: issues } = await supabase
    .from('coupon_issues')
    .select('id, status, coupons(id, name, type, discount_amount, discount_rate, max_discount, min_order_amount, expires_at)')
    .eq('member_id', memberId)
    .eq('status', 'active')

  const now = new Date()
  const coupons = (issues ?? [])
    .filter(i => {
      const c = i.coupons as any
      if (!c) return false
      if (c.expires_at && new Date(c.expires_at) < now) return false
      return true
    })
    .map(i => {
      const c = i.coupons as any
      return { ...c, couponIssueId: i.id }
    })

  return NextResponse.json({ coupons })
}
