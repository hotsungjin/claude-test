import { createClient } from './server'

/**
 * API 라우트에서 빠르게 인증된 member_id를 가져오는 헬퍼
 * getSession()을 사용하여 네트워크 호출 없이 쿠키에서 JWT를 읽음
 */
export async function getAuthMember() {
  const supabase = await createClient() as any
  const { data: { session } } = await supabase.auth.getSession()
  if (!session?.user) return { supabase, memberId: null }

  const { data: member } = await supabase
    .from('members')
    .select('id')
    .eq('auth_id', session.user.id)
    .single()

  return { supabase, memberId: member?.id ?? null }
}
