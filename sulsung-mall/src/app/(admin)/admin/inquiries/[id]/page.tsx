import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import { ChevronRight } from 'lucide-react'
import { formatDateTime } from '@/utils/format'
import InquiryAnswerClient from './InquiryAnswerClient'

export default async function AdminInquiryDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any

  const { data: inq } = await supabase
    .from('inquiries')
    .select(`*, members(name, email, phone)`)
    .eq('id', id)
    .single()

  if (!inq) notFound()

  return (
    <div className="max-w-3xl">
      <nav className="flex items-center gap-1 text-sm text-gray-500 mb-6">
        <Link href="/admin/inquiries" className="hover:text-blue-700">1:1 문의</Link>
        <ChevronRight className="w-4 h-4" />
        <span className="text-gray-900 font-medium truncate">{inq.title}</span>
      </nav>

      {/* 문의 내용 */}
      <section className="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div className="flex items-start justify-between mb-4">
          <div>
            <span className="text-xs text-gray-400">{inq.category}</span>
            <h1 className="text-lg font-bold text-gray-900 mt-1">{inq.title}</h1>
          </div>
          {inq.is_secret && <span className="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded">비밀글</span>}
        </div>
        <div className="text-xs text-gray-400 mb-4">
          {inq.members?.name} ({inq.members?.email}) · {formatDateTime(inq.created_at)}
        </div>
        <div className="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
          {inq.content}
        </div>
      </section>

      {/* 답변 */}
      <section className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">답변 작성</h2>
        <InquiryAnswerClient
          inquiryId={inq.id}
          existingAnswer={inq.reply}
          existingStatus={inq.status}
        />
      </section>
    </div>
  )
}
