import { createClient } from '@/lib/supabase/server'
import BrandListClient from './BrandListClient'

export default async function AdminBrandsPage() {
  const supabase = await createClient() as any

  const { data: brands } = await supabase
    .from('brands')
    .select('*')
    .order('sort_order')
    .order('name')

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">브랜드 관리</h1>
      </div>
      <BrandListClient brands={brands ?? []} />
    </div>
  )
}
