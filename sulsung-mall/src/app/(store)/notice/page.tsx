import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDate } from '@/utils/format'
import MypageHeader from '@/components/store/mypage/MypageHeader'

export default async function NoticePage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>
}) {
  const params = await searchParams
  const supabase = await createClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 15
  const from = (page - 1) * PAGE_SIZE

  const { data: notices, count } = await supabase
    .from('notices')
    .select('id, title, is_pinned, created_at', { count: 'exact' })
    .eq('is_visible', true)
    .order('is_pinned', { ascending: false })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="공지사항" />

      <div className="px-4 pt-4">
        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 w-16">번호</th>
                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500">제목</th>
                <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 w-28">작성일</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {(notices ?? []).map((n: any, idx: number) => (
                <tr key={n.id} className="hover:bg-gray-50">
                  <td className="px-5 py-3.5 text-gray-400 text-center">
                    {n.is_pinned
                      ? <span className="text-xs font-bold px-1.5 py-0.5 rounded" style={{ color: '#968774', backgroundColor: '#f0ebe4' }}>공지</span>
                      : count! - from - idx}
                  </td>
                  <td className="px-5 py-3.5">
                    <Link href={`/notice/${n.id}`}
                      className={`hover:underline hover:opacity-80 ${n.is_pinned ? 'font-semibold text-gray-900' : 'text-gray-800'}`}>
                      {n.title}
                    </Link>
                  </td>
                  <td className="px-5 py-3.5 text-right text-gray-400 text-xs">{formatDate(n.created_at)}</td>
                </tr>
              ))}
              {(notices ?? []).length === 0 && (
                <tr>
                  <td colSpan={3} className="px-5 py-12 text-center text-gray-400">등록된 공지사항이 없습니다.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="flex justify-center gap-1 mt-6">
            {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
              <a key={p} href={`/notice?page=${p}`}
                className={`w-8 h-8 flex items-center justify-center rounded text-sm ${p === page ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100'}`}
                style={p === page ? { backgroundColor: '#968774' } : undefined}>
                {p}
              </a>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
