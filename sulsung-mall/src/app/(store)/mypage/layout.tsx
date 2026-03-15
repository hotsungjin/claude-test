import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'

export default async function MypageLayout({ children }: { children: React.ReactNode }) {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login?redirect=/mypage')

  // 휴면 회원 체크
  const { data: member } = await supabase
    .from('members')
    .select('is_dormant')
    .eq('auth_id', user.id)
    .single()

  if (member?.is_dormant) redirect('/auth/dormant')

  return <>{children}</>
}
