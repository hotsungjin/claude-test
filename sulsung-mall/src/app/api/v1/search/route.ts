import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { getAuthMember } from '@/lib/supabase/auth'

// 급상승 검색어 조회 (최근 1시간 기준 상위 10개)
export async function GET() {
  const supabase = await createAdminClient()

  const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000).toISOString()

  const { data } = await (supabase as any).rpc('get_trending_keywords', {
    since_at: oneHourAgo,
    limit_count: 10,
  })

  return NextResponse.json({ keywords: data ?? [] })
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
