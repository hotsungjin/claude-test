import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import { formatDateTime } from '@/utils/format'
import MypageHeader from '@/components/store/mypage/MypageHeader'

export default async function NoticeDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any

  const { data: notice } = await supabase
    .from('notices')
    .select('*')
    .eq('id', id)
    .eq('is_visible', true)
    .single()

  if (!notice) notFound()

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="공지사항" />

      <div className="px-5 pt-5 pb-4 border-b" style={{ borderColor: '#f0f0f0' }}>
        {notice.is_pinned && (
          <span className="text-[11px] font-bold px-1.5 py-0.5 rounded inline-block mb-2"
            style={{ color: '#968774', backgroundColor: '#f0ebe4' }}>공지</span>
        )}
        <h1 className="text-[17px] font-bold" style={{ color: '#222' }}>{notice.title}</h1>
        <p className="text-[12px] mt-2" style={{ color: '#aaa' }}>{formatDateTime(notice.created_at)}</p>
      </div>

      <div className="px-5 py-5 text-[14px] leading-relaxed whitespace-pre-wrap" style={{ color: '#444' }}>
        {notice.content}
      </div>
    </div>
  )
}
