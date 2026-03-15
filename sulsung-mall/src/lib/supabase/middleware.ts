import { createServerClient } from '@supabase/ssr'
import { NextResponse, type NextRequest } from 'next/server'

export async function updateSession(request: NextRequest) {
  let supabaseResponse = NextResponse.next({ request })

  const supabase = createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      cookies: {
        getAll() { return request.cookies.getAll() },
        setAll(cookiesToSet) {
          cookiesToSet.forEach(({ name, value }) => request.cookies.set(name, value))
          supabaseResponse = NextResponse.next({ request })
          cookiesToSet.forEach(({ name, value, options }) =>
            supabaseResponse.cookies.set(name, value, options)
          )
        },
      },
    }
  )

  // getSession()은 쿠키에서 JWT만 읽으므로 네트워크 호출 없이 빠름
  // (getUser()는 Supabase 서버로 HTTP 요청 → Edge에서 타임아웃 원인)
  // 실제 인증 검증은 각 페이지의 layout/server component에서 getUser()로 수행
  const { data: { session } } = await supabase.auth.getSession()

  // 관리자 경로 보호 (로그인 여부만 체크, 관리자 권한은 layout에서 확인)
  if (request.nextUrl.pathname.startsWith('/admin')) {
    if (!session) {
      return NextResponse.redirect(new URL('/admin/login', request.url))
    }
  }

  // 마이페이지 경로 보호 (로그인 여부만 체크, 휴면 체크는 layout에서 확인)
  if (request.nextUrl.pathname.startsWith('/mypage')) {
    if (!session) {
      return NextResponse.redirect(
        new URL(`/auth/login?redirect=${request.nextUrl.pathname}`, request.url)
      )
    }
  }

  return supabaseResponse
}
