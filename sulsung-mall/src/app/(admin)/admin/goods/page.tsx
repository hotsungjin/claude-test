import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import GoodsTableClient from './GoodsTableClient'

export default async function AdminGoodsPage({ searchParams }: { searchParams: Promise<{ q?: string; status?: string; category?: string; brand?: string; page?: string }> }) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 30
  const from = (page - 1) * PAGE_SIZE

  // 카테고리 & 브랜드 목록
  const [{ data: categories }, { data: brands }] = await Promise.all([
    supabase.from('categories').select('id, name, parent_id').eq('is_active', true).order('sort_order'),
    supabase.from('brands').select('id, name').eq('is_active', true).order('name'),
  ])

  let query = supabase
    .from('goods')
    .select('id, name, price, sale_price, stock, status, thumbnail_url, sale_count, created_at, category_id, brand_id, godomall_code, categories(name)', { count: 'exact' })
    .neq('status', 'deleted')
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  if (params.q) query = query.ilike('name', `%${params.q}%`)
  if (params.status) query = query.eq('status', params.status)
  if (params.category) query = query.eq('category_id', Number(params.category))
  if (params.brand) query = query.eq('brand_id', Number(params.brand))

  const { data: goods, count } = await query
  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  const parentCategories = (categories ?? []).filter((c: any) => !c.parent_id)
  const childCategories = (categories ?? []).filter((c: any) => c.parent_id)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">상품 관리</h1>
        <Link href="/admin/goods/new"
          className="flex items-center gap-2 bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-800">
          <Plus className="w-4 h-4" /> 상품 등록
        </Link>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-xl p-4 shadow-sm mb-6">
        <form className="flex flex-wrap gap-2">
          <input name="q" defaultValue={params.q} placeholder="상품명 검색"
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-[150px] focus:outline-none focus:border-blue-500" />
          <select name="category" defaultValue={params.category}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 카테고리</option>
            {parentCategories.map((p: any) => {
              const children = childCategories.filter((c: any) => c.parent_id === p.id)
              if (children.length > 0) {
                return [
                  <optgroup key={`g-${p.id}`} label={p.name}>
                    {children.map((c: any) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </optgroup>
                ]
              }
              return <option key={p.id} value={p.id}>{p.name}</option>
            })}
          </select>
          <select name="brand" defaultValue={params.brand}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 브랜드</option>
            {(brands ?? []).map((b: any) => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
          <select name="status" defaultValue={params.status}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 상태</option>
            <option value="active">판매중</option>
            <option value="inactive">비공개</option>
            <option value="soldout">품절</option>
          </select>
          <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">검색</button>
        </form>
      </div>

      {/* 목록 */}
      <div className="bg-white rounded-xl shadow-sm overflow-x-auto">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <span className="text-sm text-gray-500">총 <strong>{count ?? 0}</strong>개</span>
        </div>
        <GoodsTableClient goods={goods ?? []} totalCount={count ?? 0} page={page} pageSize={PAGE_SIZE} />

        {/* 페이지네이션 */}
        {totalPages > 1 && (
          <div className="flex justify-center gap-1 py-4 border-t border-gray-50">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => {
              const qs = new URLSearchParams()
              qs.set('page', String(p))
              if (params.q) qs.set('q', params.q)
              if (params.status) qs.set('status', params.status)
              if (params.category) qs.set('category', params.category)
              if (params.brand) qs.set('brand', params.brand)
              return (
                <a key={p} href={`/admin/goods?${qs.toString()}`}
                  className={`w-8 h-8 flex items-center justify-center rounded text-xs font-medium ${p === page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'}`}>
                  {p}
                </a>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}
