import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import NoticeFormClient from '../NoticeFormClient'

export default async function AdminNoticeEditPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data: notice } = await supabase.from('notices').select('*').eq('id', id).single()
  if (!notice) notFound()

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">공지사항 수정</h1>
      <NoticeFormClient initialData={notice} />
    </div>
  )
}
