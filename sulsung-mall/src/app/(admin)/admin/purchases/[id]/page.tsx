import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import PurchaseDetailClient from './PurchaseDetailClient'

export default async function PurchaseDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any

  const { data: order, error } = await supabase
    .from('purchase_orders')
    .select('*, suppliers(name, lead_days), purchase_order_items(*, goods(id, name, thumbnail_url, stock))')
    .eq('id', Number(id))
    .single()

  if (error || !order) notFound()

  return (
    <div>
      <PurchaseDetailClient order={order} />
    </div>
  )
}
