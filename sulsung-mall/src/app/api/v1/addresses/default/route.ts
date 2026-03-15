import { NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'

export const dynamic = 'force-dynamic'

export async function GET() {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ error: '로그인 필요' }, { status: 401 })

  const { data } = await (supabase as any)
    .from('member_addresses')
    .select('id, name, phone, zipcode, address1, address2, label')
    .eq('member_id', memberId)
    .eq('is_default', true)
    .single()

  if (!data) return NextResponse.json({ data: null })
  return NextResponse.json({ data })
}
