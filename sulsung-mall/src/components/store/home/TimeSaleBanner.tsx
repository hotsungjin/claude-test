'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { formatPrice, calcDiscountRate } from '@/utils/format'
import { Clock } from 'lucide-react'

function getTimeLeft(endsAt: string) {
  const diff = new Date(endsAt).getTime() - Date.now()
  if (diff <= 0) return null
  const h = Math.floor(diff / 3600000)
  const m = Math.floor((diff % 3600000) / 60000)
  const s = Math.floor((diff % 60000) / 1000)
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

export default function TimeSaleBanner({ timeSales }: { timeSales: any[] }) {
  const [timeLeft, setTimeLeft] = useState<Record<string, string | null>>({})

  useEffect(() => {
    const update = () => {
      const map: Record<string, string | null> = {}
      timeSales.forEach(ts => { map[ts.id] = getTimeLeft(ts.ends_at) })
      setTimeLeft(map)
    }
    update()
    const interval = setInterval(update, 1000)
    return () => clearInterval(interval)
  }, [timeSales])

  const active = timeSales.filter(ts => timeLeft[ts.id])
  if (active.length === 0) return null

  return (
    <section className="mt-2 border-t pt-3" style={{ borderColor: '#f0f0f0' }}>
      <div className="flex items-center gap-2 px-4 pb-3">
        <div className="w-6 h-6 rounded-full flex items-center justify-center" style={{ backgroundColor: '#e84a3b' }}>
          <Clock className="w-3.5 h-3.5 text-white" />
        </div>
        <h2 className="font-bold text-[15px]" style={{ color: '#222' }}>타임세일</h2>
        <span className="text-[12px] font-medium" style={{ color: '#e84a3b' }}>한정 특가!</span>
      </div>

      <div className="flex gap-3 overflow-x-auto scrollbar-hide px-4 pb-4" style={{ scrollSnapType: 'x mandatory' }}>
        {active.map((ts: any) => {
          const g = ts.goods
          if (!g) return null
          const salePrice = ts.discount_type === 'rate'
            ? Math.floor((g.sale_price ?? g.price) * (1 - ts.discount_value / 100))
            : (g.sale_price ?? g.price) - ts.discount_value
          const discountRate = calcDiscountRate(g.price, salePrice)

          return (
            <Link key={ts.id} href={`/goods/${g.slug}`}
              className="flex-shrink-0 w-[160px] rounded-xl overflow-hidden border"
              style={{ scrollSnapAlign: 'start', borderColor: '#f0ece8' }}>
              <div className="relative w-[160px] h-[160px] bg-gray-100">
                {g.thumbnail_url && <img src={g.thumbnail_url} alt={g.name} className="w-full h-full object-cover" />}
                <div className="absolute top-0 left-0 right-0 py-1 text-center text-[11px] font-bold text-white"
                  style={{ backgroundColor: 'rgba(232,74,59,0.9)' }}>
                  {timeLeft[ts.id]} 남음
                </div>
                {ts.max_qty && (
                  <div className="absolute bottom-0 left-0 right-0 h-1.5" style={{ backgroundColor: '#eee' }}>
                    <div className="h-full" style={{
                      backgroundColor: '#e84a3b',
                      width: `${Math.min((ts.sold_qty / ts.max_qty) * 100, 100)}%`
                    }} />
                  </div>
                )}
              </div>
              <div className="p-3">
                <p className="text-[12px] font-medium line-clamp-2 leading-tight mb-1.5" style={{ color: '#333' }}>{g.name}</p>
                <div className="flex items-center gap-1">
                  <span className="text-[13px] font-bold" style={{ color: '#e84a3b' }}>{discountRate}%</span>
                  <span className="text-[14px] font-bold" style={{ color: '#222' }}>{formatPrice(salePrice)}</span>
                </div>
                <p className="text-[11px] line-through mt-0.5" style={{ color: '#bbb' }}>{formatPrice(g.price)}</p>
                {ts.max_qty && (
                  <p className="text-[10px] mt-1" style={{ color: '#e84a3b' }}>
                    {ts.sold_qty}/{ts.max_qty}개 판매
                  </p>
                )}
              </div>
            </Link>
          )
        })}
      </div>
    </section>
  )
}
