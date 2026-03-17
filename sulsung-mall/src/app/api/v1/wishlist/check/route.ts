import { NextRequest, NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'

// 단일 상품 찜 여부 확인 (경량)
export async function GET(req: NextRequest) {
  const goodsId = req.nextUrl.searchParams.get('goodsId')
  if (!goodsId) return NextResponse.json({ wishlisted: false })

  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ wishlisted: false })

  const { data } = await supabase
    .from('wishlists')
    .select('id')
    .eq('member_id', memberId)
    .eq('goods_id', goodsId)
    .limit(1)
    .maybeSingle()

  return NextResponse.json({ wishlisted: !!data })
}
