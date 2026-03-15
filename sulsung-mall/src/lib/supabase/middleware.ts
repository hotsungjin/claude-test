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

  const { data: { user } } = await supabase.auth.getUser()

  // 관리자 경로 보호 (로그인 여부만 체크, 관리자 권한은 layout에서 확인)
  if (request.nextUrl.pathname.startsWith('/admin')) {
    if (!user) {
      return NextResponse.redirect(new URL('/admin/login', request.url))
    }
  }

  // 마이페이지 경로 보호 (로그인 여부만 체크, 휴면 체크는 layout에서 확인)
  if (request.nextUrl.pathname.startsWith('/mypage')) {
    if (!user) {
      return NextResponse.redirect(
        new URL(`/auth/login?redirect=${request.nextUrl.pathname}`, request.url)
      )
    }
  }

  return supabaseResponse
}
