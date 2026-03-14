import { createAdminClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatDateTime } from '@/utils/format'

const PAGE_LABELS: Record<string, string> = {
  '/': '홈',
  '/goods': '상품목록',
  '/categories': '카테고리',
  '/auth/signup': '회원가입',
  '/mypage': '마이페이지',
  'all': '전체',
}

export default async function PopupsPage() {
  const supabase = await createAdminClient() as any
  const { data } = await supabase.from('popups').select('*').order('sort_order')
  const items = (data ?? []) as any[]
  const now = new Date()

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">팝업 관리</h1>
        <Link href="/admin/popups/new"
          className="px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-xl hover:bg-green-800">
          팝업 등록
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {items.map((item: any) => {
          const isLive = item.is_active && (!item.starts_at || new Date(item.starts_at) <= now) && (!item.ends_at || new Date(item.ends_at) >= now)
          const pages = (item.target_pages ?? ['/']).map((p: string) => PAGE_LABELS[p] ?? p).join(', ')
          return (
            <Link key={item.id} href={`/admin/popups/${item.id}`}
              className="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
              <div className="aspect-video bg-gray-100 relative">
                {item.image_url && <img src={item.image_url} alt={item.name} className="w-full h-full object-cover" />}
                <span className={`absolute top-2 right-2 text-xs px-2 py-1 rounded-full font-medium ${
                  isLive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                }`}>
                  {isLive ? '노출중' : '비활성'}
                </span>
              </div>
              <div className="p-4">
                <p className="font-medium text-gray-900 text-sm">{item.name}</p>
                <p className="text-xs text-gray-400 mt-1">
                  {pages}
                  {item.starts_at && ` · ${formatDateTime(item.starts_at)} ~`}
                </p>
              </div>
            </Link>
          )
        })}
        {items.length === 0 && (
          <div className="col-span-3 text-center py-20 text-gray-400 bg-white rounded-xl">등록된 팝업이 없습니다</div>
        )}
      </div>
    </div>
  )
}
