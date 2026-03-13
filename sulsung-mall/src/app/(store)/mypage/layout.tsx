import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'

export default async function MypageLayout({ children }: { children: React.ReactNode }) {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login?redirect=/mypage')

  return <>{children}</>
}
