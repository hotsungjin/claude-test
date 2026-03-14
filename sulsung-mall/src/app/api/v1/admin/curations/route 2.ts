import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { getAdminUser } from '@/lib/admin-auth'
import { z } from 'zod'

const CreateSchema = z.object({
  name: z.string().min(1),
  slug: z.string().min(1),
  description: z.string().optional(),
})

export async function GET() {
  const admin = await getAdminUser()
  if (!admin) return NextResponse.json({ error: '권한 없음' }, { status: 403 })

  const supabase = await createAdminClient() as any
  const { data } = await supabase
    .from('curations')
    .select('*, curation_goods(count)')
    .order('sort_order')

  return NextResponse.json({ data: data ?? [] })
}

export async function POST(req: NextRequest) {
  const admin = await getAdminUser()
  if (!admin) return NextResponse.json({ error: '권한 없음' }, { status: 403 })

  try {
    const body = CreateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any
    const { data, error } = await supabase.from('curations').insert(body).select().single()
    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
