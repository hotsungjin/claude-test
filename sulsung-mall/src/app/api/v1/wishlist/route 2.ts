import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

// 찜 목록 조회
export async function GET() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인이 필요합니다' }, { status: 401 })

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ items: [] })

  const { data } = await supabase
    .from('wishlists')
    .select('id, goods_id, created_at, goods(id, name, slug, price, sale_price, thumbnail_url, status)')
    .eq('member_id', member.id)
    .order('created_at', { ascending: false })

  return NextResponse.json({ items: data ?? [] })
}

// 찜 토글 (추가/삭제)
export async function POST(req: NextRequest) {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인이 필요합니다' }, { status: 401 })

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 정보를 찾을 수 없습니다' }, { status: 404 })

  const { goodsId } = await req.json()
  if (!goodsId) return NextResponse.json({ error: '상품 ID가 필요합니다' }, { status: 400 })

  // 이미 찜했는지 확인
  const { data: existing } = await supabase
    .from('wishlists')
    .select('id')
    .eq('member_id', member.id)
    .eq('goods_id', goodsId)
    .single()

  if (existing) {
    // 이미 있으면 삭제
    await supabase.from('wishlists').delete().eq('id', existing.id)
    return NextResponse.json({ wishlisted: false })
  } else {
    // 없으면 추가
    await supabase.from('wishlists').insert({ member_id: member.id, goods_id: goodsId })
    return NextResponse.json({ wishlisted: true })
  }
}
