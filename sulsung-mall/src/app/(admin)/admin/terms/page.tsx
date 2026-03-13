import { createAdminClient } from '@/lib/supabase/server'
import TermsEditorClient from './TermsEditorClient'

export default async function AdminTermsPage() {
  const supabase = await createAdminClient() as any

  const { data: terms } = await supabase
    .from('terms')
    .select('*')
    .order('id')

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">약관 관리</h1>
      <TermsEditorClient terms={terms ?? []} />
    </div>
  )
}
