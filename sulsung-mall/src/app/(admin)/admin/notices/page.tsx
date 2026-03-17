import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDateTime } from '@/utils/format'
import { Plus, Pin } from 'lucide-react'

export default async function AdminNoticesPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>
}) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 30
  const from = (page - 1) * PAGE_SIZE

  const { data: notices, count } = await supabase
    .from('notices')
    .select('id, title, is_pinned, is_visible, created_at', { count: 'exact' })
    .order('is_pinned', { ascending: false })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">공지사항 관리</h1>
        <Link href="/admin/notices/new"
          className="flex items-center gap-2 bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-800">
          <Plus className="w-4 h-4" /> 공지 등록
        </Link>
      </div>

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100">
          <span className="text-sm text-gray-500">총 <strong>{count ?? 0}</strong>개</span>
        </div>
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">제목</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-20">공지고정</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-20">공개</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-40">등록일</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-20">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {(notices ?? []).map((n: any) => (
              <tr key={n.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-900">
                  {n.is_pinned && <Pin className="w-3 h-3 inline text-blue-600 mr-1 -mt-0.5" />}
                  {n.title}
                </td>
                <td className="px-4 py-3 text-center">
                  {n.is_pinned ? <span className="text-xs text-blue-600 font-medium">고정</span> : <span className="text-xs text-gray-300">-</span>}
                </td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs font-medium ${n.is_visible ? 'text-blue-600' : 'text-gray-400'}`}>
                    {n.is_visible ? '공개' : '비공개'}
                  </span>
                </td>
                <td className="px-4 py-3 text-xs text-gray-500">{formatDateTime(n.created_at)}</td>
                <td className="px-4 py-3 text-center">
                  <Link href={`/admin/notices/${n.id}`} className="text-xs text-blue-600 hover:underline">수정</Link>
                </td>
              </tr>
            ))}
            {(notices ?? []).length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-12 text-center text-gray-400">공지사항이 없습니다.</td>
              </tr>
            )}
          </tbody>
        </table>
        {totalPages > 1 && (
          <div className="flex justify-center gap-1 py-4 border-t border-gray-50">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
              <a key={p} href={`/admin/notices?page=${p}`}
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
