import { NextRequest, NextResponse } from 'next/server'
import { createClient } from '@/lib/supabase/server'

export async function POST(req: NextRequest) {
  const supabase = await createClient()
  await (supabase as any).auth.signOut()

  const origin = req.nextUrl.origin
  return NextResponse.redirect(new URL('/', origin), { status: 302 })
}
