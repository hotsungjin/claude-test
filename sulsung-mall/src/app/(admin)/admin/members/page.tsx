import { createAdminClient } from '@/lib/supabase/server'
import MembersTableClient from './MembersTableClient'

export default async function AdminMembersPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; grade?: string; status?: string; page?: string }>
}) {
  const params = await searchParams
  const supabase = await createAdminClient() as any
  const page = Number(params.page ?? 1)
  const PAGE_SIZE = 30
  const from = (page - 1) * PAGE_SIZE

  let query = supabase
    .from('members')
    .select('id, member_no, email, name, phone, grade, mileage, is_active, created_at', { count: 'exact' })
    .order('created_at', { ascending: false })
    .range(from, from + PAGE_SIZE - 1)

  if (params.q) {
    query = query.or(`name.ilike.%${params.q}%,email.ilike.%${params.q}%,phone.ilike.%${params.q}%`)
  }
  if (params.grade) query = query.eq('grade', params.grade)
  if (params.status === 'inactive') query = query.eq('is_active', false)
  else if (params.status === 'active') query = query.eq('is_active', true)

  const { data: members, count } = await query
  const totalPages = Math.ceil((count ?? 0) / PAGE_SIZE)

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">회원 관리</h1>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-xl p-4 shadow-sm mb-6">
        <form className="flex flex-wrap gap-3">
          <input name="q" defaultValue={params.q} placeholder="이름 / 이메일 / 연락처 검색"
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-48 focus:outline-none focus:border-green-500" />
          <select name="grade" defaultValue={params.grade}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 등급</option>
            <option value="bronze">브론즈</option>
            <option value="silver">실버</option>
            <option value="gold">골드</option>
            <option value="vip">VIP</option>
          </select>
          <select name="status" defaultValue={params.status}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <option value="">전체 상태</option>
            <option value="active">활성</option>
            <option value="inactive">비활성</option>
          </select>
          <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">검색</button>
          {(params.q || params.grade || params.status) && (
            <a href="/admin/members" className="text-sm text-gray-500 self-center hover:underline">초기화</a>
          )}
        </form>
      </div>

      {/* 목록 */}
      <MembersTableClient
        members={members ?? []}
        count={count ?? 0}
        page={page}
        pageSize={PAGE_SIZE}
      />

      {totalPages > 1 && (
        <div className="flex justify-center gap-1 py-4 mt-2">
          {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => i + 1).map(p => (
            <a key={p} href={`/admin/members?page=${p}${params.grade ? `&grade=${params.grade}` : ''}${params.status ? `&status=${params.status}` : ''}`}
              className={`w-8 h-8 flex items-center justify-center rounded text-xs font-medium ${p === page ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100'}`}>
              {p}
            </a>
          ))}
        </div>
      )}
    </div>
  )
}
