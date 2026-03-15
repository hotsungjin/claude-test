import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'
import { Suspense } from 'react'
import AddressesClient from './AddressesClient'
import MypageHeader from '@/components/store/mypage/MypageHeader'

export default async function AddressesPage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) redirect('/auth/login')

  const { data: addresses } = await supabase
    .from('member_addresses')
    .select('*')
    .eq('member_id', member.id)
    .order('is_default', { ascending: false })
    .order('created_at')

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="배송지 관리" />
      <Suspense>
        <AddressesClient memberId={member.id} initialAddresses={addresses ?? []} />
      </Suspense>
    </div>
  )
}
