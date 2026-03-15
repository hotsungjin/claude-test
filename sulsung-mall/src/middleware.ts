import { type NextRequest, NextResponse } from 'next/server'
import { updateSession } from '@/lib/supabase/middleware'

export async function middleware(request: NextRequest) {
  const pathname = request.nextUrl.pathname

  // auth 경로, 관리자 로그인은 항상 허용
  if (pathname.startsWith('/auth') || pathname === '/admin/login') {
    return NextResponse.next()
  }

  // admin, mypage 경로 → Supabase 세션 체크 (쿠키 기반, 네트워크 호출 없음)
  return await updateSession(request)
}

export const config = {
  matcher: ['/admin/:path*', '/mypage/:path*'],
}
