import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import PopupFormClient from '../PopupFormClient'

export default async function EditPopupPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data } = await supabase.from('popups').select('*').eq('id', id).single()
  if (!data) notFound()

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">팝업 수정</h1>
      <PopupFormClient initialData={data} />
    </div>
  )
}
