import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'

// POST /api/v1/admin/coupons/[id]/issue
// body: { target: 'all' | 'grade', grade?: string, memberIds?: string[] }
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: couponId } = await params
  const supabase = await createAdminClient() as any
  const body = await req.json()
  const { target, grade, memberIds } = body

  // 쿠폰 존재 확인
  const { data: coupon, error: couponErr } = await supabase.from('coupons').select('id, max_uses, use_count').eq('id', couponId).single()
  if (couponErr || !coupon) return NextResponse.json({ error: '쿠폰을 찾을 수 없습니다.' }, { status: 404 })

  // 대상 회원 조회
  let membersQuery = supabase.from('members').select('id')
  if (target === 'grade' && grade) membersQuery = membersQuery.eq('grade', grade)
  if (target === 'specific' && memberIds?.length) membersQuery = membersQuery.in('id', memberIds)

  const { data: members, error: membersErr } = await membersQuery
  if (membersErr) return NextResponse.json({ error: membersErr.message }, { status: 500 })

  if (!members?.length) return NextResponse.json({ error: '발급 대상 회원이 없습니다.' }, { status: 400 })

  // 최대 발급 수 확인
  if (coupon.max_uses && coupon.use_count + members.length > coupon.max_uses) {
    return NextResponse.json({
      error: `발급 한도 초과: 잔여 ${coupon.max_uses - coupon.use_count}장, 대상 ${members.length}명`
    }, { status: 400 })
  }

  // 이미 발급된 회원 제외하여 upsert
  const rows = members.map((m: { id: string }) => ({
    coupon_id: couponId,
    member_id: m.id,
    status: 'unused',
  }))

  const { data: issued, error: issueErr } = await supabase
    .from('coupon_issues')
    .upsert(rows, { onConflict: 'coupon_id,member_id', ignoreDuplicates: true })
    .select()

  if (issueErr) return NextResponse.json({ error: issueErr.message }, { status: 500 })

  return NextResponse.json({ issued: issued?.length ?? 0, total: members.length })
}
