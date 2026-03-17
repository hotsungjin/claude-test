import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDateTime } from '@/utils/format'

const STATUS_LABEL: Record<string, string> = { pending: '답변대기', answered: '답변완료', closed: '종료' }
const STATUS_COLOR: Record<string, string> = {
  pending:  'bg-yellow-100 text-yellow-700',
  answered: 'bg-blue-100 text-blue-700',
  closed:   'bg-gray-100 text-gray-500',
}

export default async function AdminInquiriesPage({
  searchParams,
}: {
  searchParams: Promise<{ status?: string; page?: string }>
}) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 30
  const from = (page - 1) * PAGE_SIZE

  let query = supabase
    .from('inquiries')
    .select(`id, title, category, status, is_secret, created_at, members(name, email)`, { count: 'exact' })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  if (params.status) query = query.eq('status', params.status)

  const { data: inquiries, count } = await query
  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">1:1 문의 관리</h1>
      </div>

      {/* 상태 탭 */}
      <div className="flex gap-2 mb-4">
        {[['', '전체'], ['pending', '답변대기'], ['answered', '답변완료'], ['closed', '종료']].map(([v, l]) => (
          <a key={v} href={v ? `/admin/inquiries?status=${v}` : '/admin/inquiries'}
            className={`px-3 py-1.5 rounded-full text-xs font-medium border ${
              params.status === v || (!params.status && !v)
                ? 'bg-blue-700 text-white border-blue-700'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
            }`}>
            {l}
          </a>
        ))}
      </div>

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100">
          <span className="text-sm text-gray-500">총 <strong>{count ?? 0}</strong>건</span>
        </div>
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">유형</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">제목</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">회원</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">등록일</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {(inquiries ?? []).map((inq: any) => (
              <tr key={inq.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-xs text-gray-500">{inq.category}</td>
                <td className="px-4 py-3 text-gray-800 font-medium max-w-xs truncate">
                  {inq.is_secret && <span className="text-xs text-orange-500 mr-1">[비밀]</span>}
                  {inq.title}
                </td>
                <td className="px-4 py-3 text-xs text-gray-600">
                  {inq.members?.name}<br />
                  <span className="text-gray-400">{inq.members?.email}</span>
                </td>
                <td className="px-4 py-3 text-xs text-gray-500">{formatDateTime(inq.created_at)}</td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs px-2 py-1 rounded-full font-medium ${STATUS_COLOR[inq.status]}`}>
                    {STATUS_LABEL[inq.status]}
                  </span>
                </td>
                <td className="px-4 py-3 text-center">
                  <Link href={`/admin/inquiries/${inq.id}`} className="text-xs text-blue-600 hover:underline">답변</Link>
                </td>
              </tr>
            ))}
            {(inquiries ?? []).length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-gray-400">문의가 없습니다.</td>
              </tr>
            )}
          </tbody>
        </table>
        {totalPages > 1 && (
          <div className="flex justify-center gap-1 py-4 border-t border-gray-50">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
              <a key={p} href={`/admin/inquiries?page=${p}${params.status ? `&status=${params.status}` : ''}`}
                className={`w-8 h-8 flex items-center justify-center rounded text-xs font-medium ${p === page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'}`}>
                {p}
              </a>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
