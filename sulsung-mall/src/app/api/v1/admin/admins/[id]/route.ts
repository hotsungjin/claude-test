import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { getAdminUser } from '@/lib/admin-auth'
import { z } from 'zod'

const UpdateSchema = z.object({
  name: z.string().min(1).optional(),
  role: z.enum(['super', 'admin']).optional(),
  permissions: z.array(z.string()).optional(),
})

// 관리자 수정
export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin || admin.role !== 'super') {
    return NextResponse.json({ error: '최고 관리자만 접근 가능합니다.' }, { status: 403 })
  }

  try {
    const { id } = await params
    const body = UpdateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any

    // 자기 자신의 role은 변경 불가
    if (id === admin.id && body.role && body.role !== admin.role) {
      return NextResponse.json({ error: '자신의 등급은 변경할 수 없습니다.' }, { status: 400 })
    }

    const { data, error } = await supabase
      .from('admin_users')
      .update(body)
      .eq('id', id)
      .select()
      .single()

    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}

// 관리자 삭제
export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin || admin.role !== 'super') {
    return NextResponse.json({ error: '최고 관리자만 접근 가능합니다.' }, { status: 403 })
  }

  try {
    const { id } = await params

    // 자기 자신은 삭제 불가
    if (id === admin.id) {
      return NextResponse.json({ error: '자기 자신은 삭제할 수 없습니다.' }, { status: 400 })
    }

    const supabase = await createAdminClient() as any
    const { error } = await supabase.from('admin_users').delete().eq('id', id)
    if (error) throw error
    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
