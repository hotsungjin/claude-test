import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'
import { z } from 'zod'

const Schema = z.object({
  code: z.string().min(1, '쿠폰 코드를 입력하세요'),
})

export async function POST(req: NextRequest) {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인이 필요합니다' }, { status: 401 })

  const { data: member } = await (supabase as any).from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 정보를 찾을 수 없습니다' }, { status: 403 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { code } = parsed.data
  const now = new Date().toISOString()

  // 쿠폰 조회
  const { data: coupon } = await (supabase as any)
    .from('coupons')
    .select('id, is_active, expires_at, max_uses, use_count')
    .eq('code', code)
    .single()

  if (!coupon) return NextResponse.json({ error: '존재하지 않는 쿠폰 코드입니다' }, { status: 404 })
  if (!coupon.is_active) return NextResponse.json({ error: '비활성화된 쿠폰입니다' }, { status: 400 })
  if (coupon.expires_at && new Date(coupon.expires_at) < new Date()) {
    return NextResponse.json({ error: '만료된 쿠폰입니다' }, { status: 400 })
  }
  if (coupon.max_uses && coupon.use_count >= coupon.max_uses) {
    return NextResponse.json({ error: '쿠폰 발급 수량이 초과되었습니다' }, { status: 400 })
  }

  // 이미 발급 받았는지 확인
  const { data: existing } = await (supabase as any)
    .from('coupon_issues')
    .select('id')
    .eq('coupon_id', coupon.id)
    .eq('member_id', member.id)
    .single()

  if (existing) return NextResponse.json({ error: '이미 등록된 쿠폰입니다' }, { status: 400 })

  // 발급
  const { data, error } = await (supabase as any).from('coupon_issues').insert({
    coupon_id: coupon.id,
    member_id: member.id,
    status: 'unused',
  }).select().single()

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })

  // 사용 수 증가
  await (supabase as any).from('coupons').update({ use_count: coupon.use_count + 1 }).eq('id', coupon.id)

  return NextResponse.json({ data }, { status: 201 })
}
