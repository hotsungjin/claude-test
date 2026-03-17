import { createAdminClient } from '@/lib/supabase/server'
import PopupsListClient from './PopupsListClient'

export default async function PopupsPage() {
  const supabase = await createAdminClient() as any
  const { data } = await supabase.from('popups').select('*').order('sort_order')
  return <PopupsListClient items={data ?? []} />
}
