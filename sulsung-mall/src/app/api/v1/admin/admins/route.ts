import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { getAdminUser } from '@/lib/admin-auth'
import { z } from 'zod'

const CreateSchema = z.object({
  email: z.string().email('올바른 이메일을 입력하세요'),
  name: z.string().min(1, '이름을 입력하세요'),
  role: z.enum(['super', 'admin']).default('admin'),
  permissions: z.array(z.string()).default([]),
})

// 관리자 목록 조회
export async function GET() {
  const admin = await getAdminUser()
  if (!admin || admin.role !== 'super') {
    return NextResponse.json({ error: '최고 관리자만 접근 가능합니다.' }, { status: 403 })
  }

  const supabase = await createAdminClient() as any
  const { data } = await supabase
    .from('admin_users')
    .select('id, auth_id, email, name, role, permissions, created_at')
    .order('created_at')

  return NextResponse.json({ data: data ?? [] })
}

// 관리자 추가
export async function POST(req: NextRequest) {
  const admin = await getAdminUser()
  if (!admin || admin.role !== 'super') {
    return NextResponse.json({ error: '최고 관리자만 접근 가능합니다.' }, { status: 403 })
  }

  try {
    const body = CreateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any

    // 이미 등록된 관리자인지 확인
    const { data: existing } = await supabase
      .from('admin_users')
      .select('id')
      .eq('email', body.email)
      .single()

    if (existing) {
      return NextResponse.json({ error: '이미 등록된 관리자입니다.' }, { status: 400 })
    }

    // auth_id 찾기 (이미 가입한 유저인 경우)
    const { data: { users } } = await supabase.auth.admin.listUsers()
    const authUser = users?.find((u: any) => u.email === body.email)

    const { data, error } = await supabase.from('admin_users').insert({
      email: body.email,
      name: body.name,
      role: body.role,
      permissions: body.permissions,
      auth_id: authUser?.id ?? null,
    }).select().single()

    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
