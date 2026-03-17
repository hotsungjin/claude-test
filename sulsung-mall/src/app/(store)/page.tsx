import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import BannerSlider from '@/components/store/layout/BannerSlider'
import GoodsCard from '@/components/store/goods/GoodsCard'
import TimeSaleBanner from '@/components/store/home/TimeSaleBanner'
import AdBanner from '@/components/store/home/AdBanner'

export const revalidate = 60 // 60초 캐시 — DB 쿼리를 매 요청마다 실행하지 않음

const CATEGORY_EMOJI: Record<string, string> = {
  '한우': '🐄',
  '한돈': '🐷',
  '간편식': '🍱',
  '간식·유제품': '🥛',
  '베이비·키즈': '👶',
  '선물세트': '🎁',
  '존쿡델리미트': '🥩',
}

const EXTRA_CATEGORIES = [
  { id: 'extra-1', name: '신상품', slug: '_new', emoji: '✨' },
  { id: 'extra-2', name: '베스트', slug: '_best', emoji: '🏆' },
  { id: 'extra-3', name: '전체보기', slug: '_all', emoji: '📋' },
]

const EXTRA_HREF: Record<string, string> = {
  '_new': '/goods?sort=newest',
  '_best': '/goods?sort=sale_count',
  '_all': '/categories',
}

export default async function HomePage() {
  const db = await createClient() as any

  const now = new Date().toISOString()
  const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString()
  const goodsFields = 'id, name, slug, price, sale_price, member_price, thumbnail_url, sale_count, stock, view_count, created_at'

  const [
    { data: banners },
    { data: adBanners },
    { data: midBanners },
    { data: btmBanners },
    { data: rawDiscountGoods },
    { data: rawNewestGoods },
    { data: rawBestGoods },
    { data: rawLowStockGoods },
    { data: rawViewedGoods },
    { data: rawRecentWithReviews },
    { data: timeSales },
    { data: categories },
  ] = await Promise.all([
    db.from('banners').select('id, image_url, mobile_image_url, link_url, alt')
      .eq('position', 'main_top').eq('is_active', true).order('sort_order'),
    db.from('banners').select('id, image_url, mobile_image_url, link_url, alt')
      .eq('position', 'main_ad').eq('is_active', true).order('sort_order').limit(3),
    db.from('banners').select('id, image_url, mobile_image_url, link_url, alt')
      .eq('position', 'main_middle').eq('is_active', true).order('sort_order').limit(1),
    db.from('banners').select('id, image_url, mobile_image_url, link_url, alt')
      .eq('position', 'main_bottom').eq('is_active', true).order('sort_order').limit(1),
    // 할인 상품 (sale_price가 있는 상품, 가격순 → 클라이언트에서 할인율 정렬)
    db.from('goods').select(goodsFields).eq('status', 'active').not('sale_price', 'is', null).limit(30),
    // 최신 등록순
    db.from('goods').select(goodsFields).eq('status', 'active').order('created_at', { ascending: false }).limit(20),
    // 판매량순 (인기/랭킹/단골 공용)
    db.from('goods').select(goodsFields).eq('status', 'active').order('sale_count', { ascending: false }).limit(30),
    // 재고 적은 순 (품절임박)
    db.from('goods').select(goodsFields).eq('status', 'active').gt('stock', 0).order('stock', { ascending: true }).limit(30),
    // 조회수 높은 순
    db.from('goods').select(goodsFields).eq('status', 'active').order('view_count', { ascending: false }).limit(20),
    // 최근 30일 상품 + 리뷰 수
    db.from('goods').select(`${goodsFields}, reviews(count)`)
      .eq('status', 'active').gte('created_at', thirtyDaysAgo).limit(20),
    db.from('time_sales').select('*, goods(id, name, slug, price, sale_price, member_price, thumbnail_url)')
      .eq('is_active', true).lte('starts_at', now).gte('ends_at', now).limit(6),
    db.from('categories').select('id, name, slug, image_url')
      .is('parent_id', null).eq('is_active', true).order('sort_order'),
  ])

  const ad1 = (adBanners ?? [])[0] ?? null
  const ad2 = (adBanners ?? [])[1] ?? null
  const ad3 = (adBanners ?? [])[2] ?? null
  const midBanner = (midBanners ?? [])[0] ?? null
  const btmBanner = (btmBanners ?? [])[0] ?? null

  const filterGoods = (arr: any[]) => (arr ?? []).filter((g: any) => g.thumbnail_url && !g.name?.includes('개인결제'))

  const discountGoods = filterGoods(rawDiscountGoods)
  const newestGoods = filterGoods(rawNewestGoods)
  const bestGoods = filterGoods(rawBestGoods)
  const lowStockGoods = filterGoods(rawLowStockGoods)
  const viewedGoods = filterGoods(rawViewedGoods)

  // 전체 풀 (랜덤/폴백용) — 이미 가져온 데이터 합침
  const allPool = new Map<string, any>()
  ;[discountGoods, newestGoods, bestGoods, lowStockGoods, viewedGoods].forEach(arr =>
    arr.forEach((g: any) => { if (!allPool.has(g.id)) allPool.set(g.id, g) })
  )

  // 중복 제거 헬퍼
  const used = new Set<string>()
  function pick(items: any[], count: number) {
    const result = items.filter((g: any) => !used.has(g.id)).slice(0, count)
    if (result.length < count) {
      const remaining = [...allPool.values()].filter((g: any) => !used.has(g.id) && !result.some(r => r.id === g.id))
      const shuffled = seededShuffle(remaining)
      result.push(...shuffled.slice(0, count - result.length))
    }
    result.forEach((g: any) => used.add(g.id))
    return result
  }

  // 일별 시드 랜덤 셔플 (매일 다른 추천)
  function seededShuffle(arr: any[]) {
    const d = new Date()
    let seed = d.getFullYear() * 10000 + (d.getMonth() + 1) * 100 + d.getDate()
    const shuffled = [...arr]
    for (let i = shuffled.length - 1; i > 0; i--) {
      seed = (seed * 9301 + 49297) % 233280
      const j = Math.floor((seed / 233280) * (i + 1))
      ;[shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]]
    }
    return shuffled
  }

  // ③ 할인 특가 — 할인율 높은 순
  const s1 = pick(
    [...discountGoods]
      .filter(g => g.sale_price && g.sale_price < g.price)
      .sort((a, b) => (a.sale_price / a.price) - (b.sale_price / b.price)),
    10
  )

  // ④ 새로 나왔어요 — 이미 DB에서 created_at desc 정렬됨
  const s2 = pick(newestGoods, 10)

  // ⑤ 지금 가장 많이 담고 있어요 — 이미 DB에서 sale_count desc 정렬됨
  const s3 = pick(bestGoods, 10)

  // ⑥ 지금 안사면 후회해요 — 이미 DB에서 stock asc 정렬됨
  const s4 = pick(lowStockGoods, 10)

  // ⑦ 이 상품, 어때요 — 이미 DB에서 view_count desc 정렬됨
  const s5 = pick(viewedGoods, 10)

  // ⑧ 실시간 랭킹 — 판매량 Top 5 (bestGoods에서 미사용 5개)
  const s6 = pick(bestGoods, 5)

  // ⑨ 후기가 좋은 신상품 — 최근 30일 + 리뷰 수 내림차순
  const recentWithReviews = filterGoods(rawRecentWithReviews)
    .map((g: any) => ({
      ...g,
      review_count: g.reviews?.[0]?.count ?? 0,
    }))
  const s7 = pick(
    [...recentWithReviews].sort((a, b) => b.review_count - a.review_count),
    10
  )

  // ⑩ AI가 추천합니다 — 일별 랜덤 셔플
  const s8 = pick(seededShuffle([...allPool.values()]), 10)

  // ⑪ 품절임박 — 재고 적은 순 (s4와 중복 제거됨)
  const s9 = pick(lowStockGoods, 10)

  // ⑫ 단골손님의 장바구니 — 판매량 × 조회수 복합 점수
  const s10 = pick(
    [...bestGoods].sort((a, b) =>
      ((b.sale_count ?? 0) * (b.view_count ?? 1)) - ((a.sale_count ?? 0) * (a.view_count ?? 1))
    ),
    10
  )

  return (
    <div className="bg-white">
      {/* ① 메인 배너 */}
      <BannerSlider banners={banners ?? []} />

      {/* ② 카테고리 아이콘 */}
      <section className="px-3 py-5">
        <div className="grid grid-cols-5 gap-x-1 gap-y-4">
          {[...(categories ?? []), ...EXTRA_CATEGORIES].slice(0, 10).map((cat: any) => {
            const href = EXTRA_HREF[cat.slug] ?? `/goods?category=${cat.slug}`
            const emoji = cat.emoji ?? CATEGORY_EMOJI[cat.name] ?? '📦'
            return (
              <Link key={cat.id} href={href}
                className="flex flex-col items-center gap-2 hover:opacity-75 transition-opacity">
                <div className="w-[60px] h-[60px] rounded-full flex items-center justify-center text-[26px]"
                  style={{ backgroundColor: '#f3f0ed' }}>
                  {cat.image_url ? (
                    <img src={cat.image_url} alt={cat.name} className="w-full h-full rounded-full object-cover" />
                  ) : emoji}
                </div>
                <span className="text-[12px] text-center leading-tight font-medium" style={{ color: '#333' }}>
                  {cat.name}
                </span>
              </Link>
            )
          })}
        </div>
      </section>

      {/* 타임세일 */}
      {(timeSales ?? []).length > 0 && <TimeSaleBanner timeSales={timeSales ?? []} />}

      {/* ③ 할인 특가! 지금 핫한 상품 */}
      <HorizontalSection
        emoji="🔥"
        title="할인 특가! 지금 핫한 상품"
        subtitle="설성목장 인기 상품을 특별한 가격에 만나보세요!"
        href="/goods?sort=sale_count"
        goods={s1}
      />


      {/* ④ 새로 나왔어요 */}
      <HorizontalSection
        emoji="✨"
        title="새로 나왔어요"
        subtitle="설성목장의 새로운 상품을 확인해보세요!"
        href="/goods?sort=newest"
        goods={s2}
      />


      {/* 광고 배너 영역 1 */}
      {ad1 ? (
        <AdBanner banner={ad1} />
      ) : (
        <section style={{ paddingTop: '22px', paddingBottom: '26px' }}>
          <img
            src="/images/banner-factory.jpg"
            alt="THE BETTER FARM, THE SMART FACTORY FOR OUR LIVES"
            className="w-full h-auto object-cover"
          />
        </section>
      )}


      {/* ⑤ 지금 가장 많이 담고 있어요 */}
      <HorizontalSection
        emoji="🛒"
        title="지금 가장 많이 담고 있어요"
        subtitle="실시간 인기 상품을 확인하세요"
        href="/goods?sort=sale_count"
        goods={s3}
      />


      {/* ⑥ 지금 안사면 후회해요 */}
      <HorizontalSection
        emoji="⏰"
        title="지금 안사면 후회해요"
        subtitle="한정 수량, 놓치지 마세요!"
        href="/goods?sort=sale_count"
        goods={s4}
      />

      {/* 광고 배너 영역 2 */}
      {ad2 && <AdBanner banner={ad2} />}

      {/* ⑦ 이 상품, 어때요 */}
      <HorizontalSection
        emoji="💡"
        title="이 상품, 어때요"
        subtitle="설성목장이 추천하는 상품"
        href="/goods?sort=sale_count"
        goods={s5}
      />


      {/* 메인 중간 배너 */}
      {midBanner && <AdBanner banner={midBanner} />}

      {/* ⑧ 실시간 랭킹 */}
      <section style={{ paddingTop: '22px' }}>
        <div className="flex items-center justify-between" style={{ padding: '0 16px 12px' }}>
          <h2 style={{ fontSize: '20px', fontWeight: 700, color: '#000', lineHeight: 1.3 }}>
            <span style={{ marginRight: '4px' }}>🏆</span>실시간 랭킹
          </h2>
          <Link href={`/goods?sort=sale_count&title=${encodeURIComponent('🏆 실시간 랭킹')}`} className="flex-shrink-0" style={{ fontSize: '14px', fontWeight: 600, color: '#968774' }}>
            전체보기 &gt;
          </Link>
        </div>
        <div className="px-4 pb-4 space-y-0">
          {s6.slice(0, 5).map((item: any, idx: number) => (
            <RankItem key={item.id} goods={item} rank={idx + 1} />
          ))}
        </div>
      </section>


      {/* ⑨ 후기가 좋은 신상품 추천해요 */}
      <HorizontalSection
        emoji="⭐"
        title="후기가 좋은 신상품 추천해요"
        subtitle="리뷰 만족도가 높은 상품만 모았어요"
        href="/goods?sort=newest"
        goods={s7}
      />


      {/* ⑩ AI가 추천합니다 */}
      <HorizontalSection
        emoji="🤖"
        title="AI가 추천합니다"
        subtitle="고객님의 취향을 분석했어요"
        href="/goods?sort=sale_count"
        goods={s8}
      />

      {/* 광고 배너 영역 3 */}
      {ad3 && <AdBanner banner={ad3} />}

      {/* 메인 하단 배너 */}
      {btmBanner && <AdBanner banner={btmBanner} />}

      {/* ⑪ 품절임박!!! */}
      <HorizontalSection
        emoji="🚨"
        title="품절임박!!!"
        subtitle="재고가 얼마 남지 않았어요"
        href="/goods?sort=sale_count"
        goods={s9}
      />


      {/* ⑫ 단골손님의 장바구니 구경하기 */}
      <HorizontalSection
        emoji="👀"
        title="단골손님의 장바구니 구경하기"
        subtitle="설성목장 단골들이 담은 상품"
        href="/goods?sort=sale_count"
        goods={s10}
      />

      <div style={{ height: '40px' }} />
    </div>
  )
}

function Divider() {
  return <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />
}

function HorizontalSection({ emoji, title, subtitle, href, goods }: { emoji?: string; title: string; subtitle?: string; href: string; goods: any[] }) {
  if (goods.length === 0) return null
  const displayTitle = emoji ? `${emoji} ${title}` : title
  const fullHref = `${href}${href.includes('?') ? '&' : '?'}title=${encodeURIComponent(displayTitle)}`
  return (
    <section style={{ paddingTop: '22px' }}>
      <div className="flex items-start justify-between" style={{ padding: '0 16px 12px' }}>
        <div>
          <h2 style={{ fontSize: '20px', fontWeight: 700, color: '#000', lineHeight: 1.3 }}>
            {emoji && <span style={{ marginRight: '4px' }}>{emoji}</span>}
            {title}
          </h2>
          {subtitle && (
            <p style={{ fontSize: '14px', color: '#999', marginTop: '2px' }}>{subtitle}</p>
          )}
        </div>
        <Link href={fullHref} className="flex-shrink-0" style={{ fontSize: '14px', fontWeight: 600, color: '#968774', marginTop: '2px' }}>
          전체보기 &gt;
        </Link>
      </div>
      <div style={{ overflow: 'hidden' }}>
        <div className="scrollbar-hide" style={{ display: 'flex', gap: '10px', paddingLeft: '16px', paddingRight: '16px', paddingBottom: '26px', overflowX: 'auto', WebkitOverflowScrolling: 'touch' }}>
          {goods.map((item: any) => (
            <GoodsCard key={item.id} goods={item} variant="scroll" />
          ))}
        </div>
      </div>
    </section>
  )
}

function RankItem({ goods, rank }: { goods: any; rank: number }) {
  const price = goods.sale_price ?? goods.price
  const discountRate = goods.sale_price
    ? Math.round((1 - goods.sale_price / goods.price) * 100) : 0

  return (
    <Link href={`/goods/${goods.slug}`}
      className="flex items-center gap-3 py-3 border-b" style={{ borderColor: '#f5f5f5' }}>
      <span className="w-6 text-center font-bold text-[15px] flex-shrink-0"
        style={{ color: rank <= 3 ? '#968774' : '#bbb' }}>
        {rank}
      </span>
      <div className="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
        {goods.thumbnail_url && (
          <img src={goods.thumbnail_url} alt={goods.name} className="w-full h-full object-cover" />
        )}
      </div>
      <div className="flex-1 min-w-0">
        <p style={{ fontSize: '14px', lineHeight: 1.35, color: '#333' }} className="line-clamp-2">{goods.name}</p>
        <div className="flex items-center gap-1.5 mt-1">
          {discountRate > 0 && (
            <span style={{ fontSize: '13px', fontWeight: 800, color: '#e84a3b' }}>{discountRate}%</span>
          )}
          <span style={{ fontSize: '14px', fontWeight: 800, color: '#222' }}>
            {price.toLocaleString()}원
          </span>
        </div>
      </div>
    </Link>
  )
}
