import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDateTime } from '@/utils/format'
import { redirect } from 'next/navigation'
import MypageHeader from '@/components/store/mypage/MypageHeader'

const STATUS_LABEL: Record<string, string> = { pending: '답변대기', answered: '답변완료', closed: '종료' }
const STATUS_COLOR: Record<string, string> = {
  pending:  'bg-yellow-100 text-yellow-700',
  answered: '',
  closed:   'bg-gray-100 text-gray-500',
}
const STATUS_STYLE: Record<string, React.CSSProperties | undefined> = {
  pending:  undefined,
  answered: { backgroundColor: '#f0ebe4', color: '#968774' },
  closed:   undefined,
}

export default async function InquiriesPage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) redirect('/auth/login')

  const { data: inquiries } = await supabase
    .from('inquiries')
    .select('id, title, category, status, created_at, answered_at')
    .eq('member_id', member.id)
    .order('created_at', { ascending: false })

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="1:1 문의" />

      <div className="flex justify-end mb-4 px-4">
        <Link href="/mypage/inquiries/new"
          className="text-white text-sm px-4 py-2 rounded-xl font-medium hover:opacity-90 transition-opacity"
          style={{ backgroundColor: '#968774' }}>
          문의하기
        </Link>
      </div>

      {(inquiries ?? []).length > 0 ? (
        <div className="space-y-2">
          {(inquiries ?? []).map((inq: any) => (
            <Link key={inq.id} href={`/mypage/inquiries/${inq.id}`}
              className="block bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow border border-gray-50">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="text-xs text-gray-400 mb-1">{inq.category}</p>
                  <p className="font-medium text-gray-900 truncate">{inq.title}</p>
                  <p className="text-xs text-gray-400 mt-1">{formatDateTime(inq.created_at)}</p>
                </div>
                <span className={`flex-shrink-0 text-xs px-2 py-1 rounded-full font-medium ${STATUS_COLOR[inq.status]}`}
                  style={STATUS_STYLE[inq.status]}>
                  {STATUS_LABEL[inq.status]}
                </span>
              </div>
            </Link>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-xl p-12 text-center shadow-sm">
          <p className="text-gray-400">문의 내역이 없습니다.</p>
          <Link href="/mypage/inquiries/new"
            className="mt-4 inline-block text-sm hover:underline"
            style={{ color: '#968774' }}>
            첫 문의 작성하기
          </Link>
        </div>
      )}
    </div>
  )
}
