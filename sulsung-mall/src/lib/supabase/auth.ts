import { createClient } from './server'

/**
 * API 라우트에서 인증된 member_id를 가져오는 헬퍼
 * getUser()를 사용하여 토큰 검증 및 자동 갱신 보장
 * (미들웨어는 Edge 타임아웃 방지를 위해 getSession() 사용,
 *  API 라우트는 Node.js 런타임이므로 getUser() 사용)
 */
export async function getAuthMember() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return { supabase, memberId: null }

  const { data: member } = await supabase
    .from('members')
    .select('id')
    .eq('auth_id', user.id)
    .single()

  return { supabase, memberId: member?.id ?? null }
}
