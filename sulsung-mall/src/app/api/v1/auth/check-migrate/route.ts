import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'

export async function POST(req: NextRequest) {
  try {
    const { phone } = await req.json()
    if (!phone) return NextResponse.json({ needActivate: false })

    const supabase = await createAdminClient() as any

    const { data: member } = await supabase
      .from('members')
      .select('id, auth_id')
      .eq('phone', phone)
      .single()

    if (member && !member.auth_id) {
      return NextResponse.json({ needActivate: true })
    }

    return NextResponse.json({ needActivate: false })
  } catch {
    return NextResponse.json({ needActivate: false })
  }
}
