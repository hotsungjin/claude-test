import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { getAuthMember } from '@/lib/supabase/auth'

// 급상승 검색어 (최근 1주일) + 추천 검색어 (전체 누적 인기)
export async function GET() {
  const supabase = await createAdminClient()

  const oneWeekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString()
  const allTime = new Date('2020-01-01').toISOString()

  const [{ data: trending }, { data: popular }] = await Promise.all([
    (supabase as any).rpc('get_trending_keywords', {
      since_at: oneWeekAgo,
      limit_count: 10,
    }),
    (supabase as any).rpc('get_trending_keywords', {
      since_at: allTime,
      limit_count: 10,
    }),
  ])

  return NextResponse.json({
    keywords: trending ?? [],
    popular: popular ?? [],
  })
}

// 검색 로그 기록
export async function POST(req: NextRequest) {
  try {
    const { keyword } = await req.json()
    if (!keyword || typeof keyword !== 'string') {
      return NextResponse.json({ error: 'keyword required' }, { status: 400 })
    }

    const { memberId } = await getAuthMember()

    const adminClient = await createAdminClient()
    await (adminClient as any).from('search_logs').insert({
      keyword: keyword.trim().toLowerCase(),
      member_id: memberId ?? null,
    })

    return NextResponse.json({ success: true })
  } catch {
    return NextResponse.json({ error: 'failed' }, { status: 500 })
  }
}
