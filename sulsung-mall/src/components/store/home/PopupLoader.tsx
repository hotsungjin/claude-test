import { createClient } from '@/lib/supabase/server'
import PopupModal from './PopupModal'

export default async function PopupLoader() {
  const db = await createClient() as any
  const now = new Date().toISOString()
  const { data: popups } = await db
    .from('popups')
    .select('*')
    .eq('is_active', true)
    .or(`starts_at.is.null,starts_at.lte.${now}`)
    .or(`ends_at.is.null,ends_at.gte.${now}`)
    .order('sort_order')
    .limit(5)

  if (!popups || popups.length === 0) return null
  return <PopupModal popups={popups} />
}
