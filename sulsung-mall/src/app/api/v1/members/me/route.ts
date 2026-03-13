import { NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

export async function GET() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return NextResponse.json({ member: null })

  const { data: member } = await supabase
    .from('members')
    .select('id, name, email, phone, grade, mileage, deposit')
    .eq('auth_id', user.id)
    .single()

  return NextResponse.json({ member })
}
