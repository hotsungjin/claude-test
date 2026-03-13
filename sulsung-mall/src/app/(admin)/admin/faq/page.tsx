import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDateTime } from '@/utils/format'

export default async function AdminFaqPage() {
  const supabase = await createClient() as any

  const { data: faqs } = await supabase
    .from('faqs')
    .select('*')
    .order('sort_order')
    .order('created_at', { ascending: false })

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">자주 묻는 질문</h1>
        <Link href="/admin/faq/new"
          className="px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-lg hover:bg-green-800">
          FAQ 등록
        </Link>
      </div>

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-3 bg-gray-50 border-b border-gray-100">
          <span className="text-sm text-gray-500">총 <strong>{(faqs ?? []).length}</strong>건</span>
        </div>
        <table className="w-full text-sm">
          <thead className="border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-16">순서</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-24">카테고리</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">질문</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-20">상태</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-36">등록일</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium w-16">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {(faqs ?? []).map((faq: any) => (
              <tr key={faq.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-gray-500 text-xs">{faq.sort_order}</td>
                <td className="px-4 py-3 text-xs text-gray-600">{faq.category}</td>
                <td className="px-4 py-3 text-gray-800 font-medium truncate max-w-md">{faq.question}</td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    faq.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {faq.is_active ? '활성' : '비활성'}
                  </span>
                </td>
                <td className="px-4 py-3 text-xs text-gray-500">{formatDateTime(faq.created_at)}</td>
                <td className="px-4 py-3 text-center">
                  <Link href={`/admin/faq/${faq.id}`} className="text-xs text-blue-600 hover:underline">수정</Link>
                </td>
              </tr>
            ))}
            {(faqs ?? []).length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-gray-400">등록된 FAQ가 없습니다.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
