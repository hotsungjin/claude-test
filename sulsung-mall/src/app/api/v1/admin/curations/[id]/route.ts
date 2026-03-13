import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'
import { getAdminUser } from '@/lib/admin-auth'
import { z } from 'zod'

const UpdateSchema = z.object({
  name: z.string().min(1).optional(),
  slug: z.string().min(1).optional(),
  description: z.string().optional(),
  is_active: z.boolean().optional(),
  sort_order: z.number().optional(),
})

const GoodsSchema = z.object({
  goods_ids: z.array(z.string().uuid()),
})

// 큐레이션 상세 (상품 포함)
export async function GET(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin) return NextResponse.json({ error: '권한 없음' }, { status: 403 })

  const { id } = await params
  const supabase = await createAdminClient() as any

  const [{ data: curation }, { data: items }] = await Promise.all([
    supabase.from('curations').select('*').eq('id', id).single(),
    supabase
      .from('curation_goods')
      .select('id, sort_order, goods_id, goods(id, name, slug, price, sale_price, thumbnail_url, status)')
      .eq('curation_id', id)
      .order('sort_order'),
  ])

  return NextResponse.json({ curation, items: items ?? [] })
}

// 큐레이션 수정
export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin) return NextResponse.json({ error: '권한 없음' }, { status: 403 })

  try {
    const { id } = await params
    const body = UpdateSchema.parse(await req.json())
    const supabase = await createAdminClient() as any
    const { data, error } = await supabase.from('curations').update(body).eq('id', id).select().single()
    if (error) throw error
    return NextResponse.json({ data })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}

// 큐레이션 삭제
export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin) return NextResponse.json({ error: '권한 없음' }, { status: 403 })

  const { id } = await params
  const supabase = await createAdminClient() as any
  const { error } = await supabase.from('curations').delete().eq('id', id)
  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  return NextResponse.json({ success: true })
}

// 큐레이션 상품 일괄 설정
export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin) return NextResponse.json({ error: '권한 없음' }, { status: 403 })

  try {
    const { id } = await params
    const body = GoodsSchema.parse(await req.json())
    const supabase = await createAdminClient() as any

    // 기존 상품 삭제 후 새로 추가
    await supabase.from('curation_goods').delete().eq('curation_id', id)

    if (body.goods_ids.length > 0) {
      const rows = body.goods_ids.map((goods_id, idx) => ({
        curation_id: id,
        goods_id,
        sort_order: idx,
      }))
      const { error } = await supabase.from('curation_goods').insert(rows)
      if (error) throw error
    }

    return NextResponse.json({ success: true })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
