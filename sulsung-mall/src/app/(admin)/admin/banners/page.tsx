import { createAdminClient } from '@/lib/supabase/server'
import Link from 'next/link'

const POSITION_LABEL: Record<string, string> = {
  main_top: '메인 상단',
  main_ad: '광고 배너',
  main_middle: '메인 중간',
  main_bottom: '메인 하단',
  popup: '팝업',
  aside: '사이드',
}

const TABS = [
  { key: 'all', label: '전체' },
  { key: 'main_top', label: '상단 배너' },
  { key: 'main_ad', label: '광고 배너' },
]

export default async function AdminBannersPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | undefined }>
}) {
  const params = await searchParams
  const tab = params.tab ?? 'all'

  const supabase = await createAdminClient() as any
  let query = supabase.from('banners').select('*').order('sort_order')
  if (tab !== 'all') {
    query = query.eq('position', tab)
  }
  const { data: banners } = await query

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">배너 관리</h1>
        <Link href="/admin/banners/new"
          className="bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-600">
          + 배너 등록
        </Link>
      </div>

      {/* 탭 필터 */}
      <div className="flex gap-1 mb-4">
        {TABS.map(t => (
          <Link
            key={t.key}
            href={t.key === 'all' ? '/admin/banners' : `/admin/banners?tab=${t.key}`}
            className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
              tab === t.key
                ? 'bg-gray-900 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            {t.label}
          </Link>
        ))}
      </div>

      {tab === 'main_ad' && (
        <div className="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 mb-4 text-sm text-blue-700">
          광고 배너는 메인 페이지 콘텐츠 섹션 사이에 노출됩니다. 순서 1·2·3이 각각 영역 1·2·3에 배치됩니다.
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">이미지</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">배너명</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">위치</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">순서</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">기간</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">관리</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {(banners ?? []).map((banner: any) => (
              <tr key={banner.id} className="hover:bg-gray-50">
                <td className="px-4 py-3">
                  {banner.image_url ? (
                    <img src={banner.image_url} alt={banner.name}
                      className="w-20 h-12 object-cover rounded-lg border border-gray-200" />
                  ) : (
                    <div className="w-20 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-xs text-gray-400">없음</div>
                  )}
                </td>
                <td className="px-4 py-3 font-medium text-gray-800">{banner.name}</td>
                <td className="px-4 py-3">
                  <span className={`text-xs px-2 py-1 rounded-full ${
                    banner.position === 'main_ad'
                      ? 'bg-orange-50 text-orange-700'
                      : 'bg-blue-50 text-blue-700'
                  }`}>
                    {POSITION_LABEL[banner.position] ?? banner.position}
                  </span>
                </td>
                <td className="px-4 py-3 text-gray-500 text-xs">{banner.sort_order}</td>
                <td className="px-4 py-3 text-xs text-gray-500">
                  {banner.starts_at ? banner.starts_at.slice(0, 10) : '∞'} ~{' '}
                  {banner.ends_at ? banner.ends_at.slice(0, 10) : '∞'}
                </td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs px-2 py-1 rounded-full ${banner.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                    {banner.is_active ? '활성' : '비활성'}
                  </span>
                </td>
                <td className="px-4 py-3 text-center">
                  <Link href={`/admin/banners/${banner.id}`}
                    className="text-xs px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">
                    수정
                  </Link>
                </td>
              </tr>
            ))}
            {(banners ?? []).length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-12 text-center text-gray-400 text-sm">
                  등록된 배너가 없습니다.{' '}
                  <Link href="/admin/banners/new" className="text-green-700 underline">첫 배너를 등록하세요</Link>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
