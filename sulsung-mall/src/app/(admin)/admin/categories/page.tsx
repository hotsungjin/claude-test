import { createClient } from '@/lib/supabase/server'
import CategoryListClient from './CategoryListClient'

export default async function AdminCategoriesPage() {
  const supabase = await createClient() as any

  const { data: categories } = await supabase
    .from('categories')
    .select('*')
    .order('sort_order')
    .order('name')

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">카테고리 관리</h1>
      </div>
      <CategoryListClient categories={categories ?? []} />
    </div>
  )
}
