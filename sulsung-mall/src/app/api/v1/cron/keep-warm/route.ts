import { NextResponse } from 'next/server'

// 주요 API 함수들을 warm 상태로 유지하기 위한 cron
export async function GET() {
  const base = process.env.NEXT_PUBLIC_SITE_URL || process.env.VERCEL_URL
    ? `https://${process.env.VERCEL_URL}`
    : 'http://localhost:3000'

  // 주요 API + 홈 페이지를 병렬로 호출하여 warm 상태 유지
  await Promise.allSettled([
    fetch(`${base}/`, { headers: { 'x-warm': '1' } }),
    fetch(`${base}/api/v1/cart?count=true`, { headers: { cookie: '' } }),
    fetch(`${base}/api/v1/members/me`, { headers: { cookie: '' } }),
    fetch(`${base}/api/v1/goods?limit=1`),
  ])

  return NextResponse.json({ ok: true, ts: Date.now() })
}
