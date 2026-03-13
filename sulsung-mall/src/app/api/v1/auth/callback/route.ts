import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

export async function GET(req: NextRequest) {
  const { searchParams, origin } = new URL(req.url)
  const code = searchParams.get('code')
  const redirect = searchParams.get('redirect') ?? '/'

  if (code) {
    const supabase = await createClient() as any
    const { data, error } = await supabase.auth.exchangeCodeForSession(code)

    if (!error && data.user) {
      // 신규 소셜 회원이면 members 테이블 생성
      const { data: existing } = await supabase
        .from('members')
        .select('id')
        .eq('auth_id', data.user.id)
        .single()

      if (!existing) {
        const meta = data.user.user_metadata
        await supabase.from('members').insert({
          auth_id:         data.user.id,
          email:           data.user.email ?? '',
          name:            meta.full_name ?? meta.name ?? '회원',
          phone:           meta.phone ?? null,
          social_provider: data.user.app_metadata.provider,
          social_id:       data.user.id,
        })
      }
    }
  }

  return NextResponse.redirect(`${origin}${redirect}`)
}
