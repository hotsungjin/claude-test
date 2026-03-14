import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'

export async function GET(req: NextRequest) {
  const q = req.nextUrl.searchParams.get('q')?.trim()
  if (!q) return NextResponse.json({ members: [] })

  const supabase = await createAdminClient() as any

  const { data: members } = await supabase
    .from('members')
    .select('id, name, email, phone, grade, member_no')
    .or(`name.ilike.%${q}%,email.ilike.%${q}%,phone.ilike.%${q}%`)
    .order('name')
    .limit(20)

  return NextResponse.json({ members: members ?? [] })
}
