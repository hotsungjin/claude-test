import { NextRequest, NextResponse } from 'next/server'
import { getAuthMember } from '@/lib/supabase/auth'
import { z } from 'zod'

const Schema = z.object({
  category: z.string().min(1),
  title: z.string().min(1, '제목을 입력하세요'),
  content: z.string().min(10, '내용을 10자 이상 입력하세요'),
  is_secret: z.boolean().default(false),
  order_id: z.string().uuid().optional(),
})

export async function POST(req: NextRequest) {
  const { supabase, memberId } = await getAuthMember()
  if (!memberId) return NextResponse.json({ error: '로그인이 필요합니다' }, { status: 401 })

  const body = await req.json()
  const parsed = Schema.safeParse(body)
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues[0].message }, { status: 400 })

  const { data, error } = await (supabase as any).from('inquiries').insert({
    ...parsed.data,
    member_id: memberId,
    status: 'pending',
  }).select().single()

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ data }, { status: 201 })
}
