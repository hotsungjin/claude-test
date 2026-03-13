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

  // 관리자 경로 보호
  if (request.nextUrl.pathname.startsWith('/admin')) {
    if (!user) {
      return NextResponse.redirect(new URL('/auth/login?redirect=/admin', request.url))
    }

    // admin_users 테이블에서 관리자 여부 확인
    const { data: adminUser } = await supabase
      .from('admin_users')
      .select('id')
      .eq('auth_id', user.id)
      .single()

    if (!adminUser) {
      // 관리자가 아닌 경우 홈으로 리다이렉트
      return NextResponse.redirect(new URL('/', request.url))
    }
  }

  // 마이페이지 경로 보호
  if (request.nextUrl.pathname.startsWith('/mypage')) {
    if (!user) {
      return NextResponse.redirect(
        new URL(`/auth/login?redirect=${request.nextUrl.pathname}`, request.url)
      )
    }

    // 휴면 회원 체크 → 재활성화 페이지로 리다이렉트
    const { data: member } = await supabase
      .from('members')
      .select('is_dormant')
      .eq('auth_id', user.id)
      .single()

    if (member?.is_dormant) {
      return NextResponse.redirect(new URL('/auth/dormant', request.url))
    }
  }

  return supabaseResponse
}
