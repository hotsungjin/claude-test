import { NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

export const dynamic = 'force-dynamic'

export async function GET() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const { data: member } = await (supabase as any).from('members').select('id').eq('auth_id', user.id).single()
  if (!member) return NextResponse.json({ error: '회원 없음' }, { status: 403 })

  const { data } = await (supabase as any)
    .from('member_addresses')
    .select('id, name, address1, address2, label')
    .eq('member_id', member.id)
    .eq('is_default', true)
    .single()

  if (!data) return NextResponse.json({ data: null })
  return NextResponse.json({ data })
}
