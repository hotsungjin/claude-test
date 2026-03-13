import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import FaqFormClient from '../FaqFormClient'

export default async function AdminFaqEditPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any

  const { data: faq } = await supabase.from('faqs').select('*').eq('id', Number(id)).single()
  if (!faq) notFound()

  return (
    <div className="max-w-2xl">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">FAQ 수정</h1>
      <FaqFormClient faq={faq} />
    </div>
  )
}
