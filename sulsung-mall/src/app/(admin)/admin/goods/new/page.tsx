import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { ChevronRight } from 'lucide-react'
import GoodsFormClient from '@/components/admin/goods/GoodsFormClient'

export default async function AdminGoodsNewPage() {
  const supabase = await createClient() as any
  const [{ data: categories }, { data: brands }] = await Promise.all([
    supabase.from('categories').select('id, name, parent_id').eq('is_active', true).order('sort_order'),
    supabase.from('brands').select('id, name').eq('is_active', true).order('sort_order'),
  ])

  return (
    <div>
      {/* 브레드크럼 */}
      <nav className="flex items-center gap-1 text-sm text-gray-500 mb-6">
        <Link href="/admin/goods" className="hover:text-green-700">상품 관리</Link>
        <ChevronRight className="w-4 h-4" />
        <span className="text-gray-900 font-medium">상품 등록</span>
      </nav>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">상품 등록</h1>
      <GoodsFormClient categories={categories ?? []} brands={brands ?? []} />
    </div>
  )
}
