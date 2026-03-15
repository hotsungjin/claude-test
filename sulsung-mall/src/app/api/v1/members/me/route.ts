import { NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'

export async function GET() {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ member: null })

  const { data: member } = await supabase
    .from('members')
    .select('id, name, email, phone, grade, mileage, deposit')
    .eq('id', memberId)
    .single()

  return NextResponse.json({ member })
}
