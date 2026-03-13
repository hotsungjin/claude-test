import { createClient } from '@/lib/supabase/server'
import PurchaseFormClient from './PurchaseFormClient'

export default async function NewPurchasePage() {
  const supabase = await createClient() as any

  const [{ data: suppliers }, { data: goods }] = await Promise.all([
    supabase.from('suppliers').select('id, name, lead_days').eq('is_active', true).order('name'),
    supabase.from('goods').select('id, name, stock, thumbnail_url, cost_price').neq('status', 'deleted').order('name'),
  ])

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">발주서 작성</h1>
      <PurchaseFormClient suppliers={suppliers ?? []} goods={goods ?? []} />
    </div>
  )
}
