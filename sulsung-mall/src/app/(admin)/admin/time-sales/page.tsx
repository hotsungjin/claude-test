import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { formatPrice, formatDateTime } from '@/utils/format'

export default async function TimeSalesPage() {
  const supabase = await createClient() as any
  const { data } = await supabase
    .from('time_sales')
    .select('*, goods(name, thumbnail_url, price)')
    .order('created_at', { ascending: false })

  const items = (data ?? []) as any[]
  const now = new Date()

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">타임세일 관리</h1>
        <Link href="/admin/time-sales/new"
          className="px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-xl hover:bg-green-800">
          타임세일 등록
        </Link>
      </div>

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b">
            <tr>
              <th className="text-left px-4 py-3 font-medium text-gray-600">세일명</th>
              <th className="text-left px-4 py-3 font-medium text-gray-600">상품</th>
              <th className="text-left px-4 py-3 font-medium text-gray-600">할인</th>
              <th className="text-left px-4 py-3 font-medium text-gray-600">기간</th>
              <th className="text-center px-4 py-3 font-medium text-gray-600">판매/한정</th>
              <th className="text-center px-4 py-3 font-medium text-gray-600">상태</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item: any) => {
              const start = new Date(item.starts_at)
              const end = new Date(item.ends_at)
              const isLive = item.is_active && now >= start && now <= end
              const isUpcoming = item.is_active && now < start
              const isEnded = now > end

              return (
                <tr key={item.id} className="border-b hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <Link href={`/admin/time-sales/${item.id}`} className="text-blue-600 hover:underline font-medium">
                      {item.name}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-gray-700">{item.goods?.name ?? '-'}</td>
                  <td className="px-4 py-3 text-red-600 font-medium">
                    {item.discount_type === 'rate' ? `${item.discount_value}%` : formatPrice(item.discount_value)}
                  </td>
                  <td className="px-4 py-3 text-gray-500 text-xs">
                    {formatDateTime(item.starts_at)}<br/>~ {formatDateTime(item.ends_at)}
                  </td>
                  <td className="px-4 py-3 text-center text-gray-700">
                    {item.sold_qty}{item.max_qty ? ` / ${item.max_qty}` : ''}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className={`text-xs px-2 py-1 rounded-full font-medium ${
                      isLive ? 'bg-red-100 text-red-700' :
                      isUpcoming ? 'bg-blue-100 text-blue-700' :
                      isEnded ? 'bg-gray-100 text-gray-500' :
                      'bg-gray-100 text-gray-400'
                    }`}>
                      {isLive ? '진행중' : isUpcoming ? '예정' : isEnded ? '종료' : '비활성'}
                    </span>
                  </td>
                </tr>
              )
            })}
            {items.length === 0 && (
              <tr><td colSpan={6} className="text-center py-12 text-gray-400">등록된 타임세일이 없습니다</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
