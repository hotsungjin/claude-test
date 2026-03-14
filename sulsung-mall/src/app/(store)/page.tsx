import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import MobileBannerSlider from '@/components/store/layout/MobileBannerSlider'
import MobileGoodsCard from '@/components/store/goods/MobileGoodsCard'
import TimeSaleBanner from '@/components/store/home/TimeSaleBanner'
import AdBanner from '@/components/store/home/AdBanner'

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
  const [
    { data: banners },
    { data: adBanners },
    { data: allGoods },
    { data: newGoods },
    { data: timeSales },
    { data: categories },
  ] = await Promise.all([
    db.from('banners').select('id, image_url, mobile_image_url, link_url, alt')
      .eq('position', 'main_top').eq('is_active', true).order('sort_order'),
    db.from('banners').select('id, image_url, mobile_image_url, link_url, alt')
      .eq('position', 'main_ad').eq('is_active', true).order('sort_order').limit(3),
    db.from('goods').select('id, name, slug, price, sale_price, thumbnail_url, sale_count')
      .eq('status', 'active').order('sale_count', { ascending: false }).limit(30),
    db.from('goods').select('id, name, slug, price, sale_price, thumbnail_url, sale_count')
      .eq('status', 'active').order('created_at', { ascending: false }).limit(30),
    db.from('time_sales').select('*, goods(id, name, slug, price, sale_price, thumbnail_url)')
      .eq('is_active', true).lte('starts_at', now).gte('ends_at', now).limit(6),
    db.from('categories').select('id, name, slug, image_url')
      .is('parent_id', null).eq('is_active', true).order('sort_order'),
  ])

  const ad1 = (adBanners ?? [])[0] ?? null
  const ad2 = (adBanners ?? [])[1] ?? null
  const ad3 = (adBanners ?? [])[2] ?? null

  const best = allGoods ?? []
  const fresh = newGoods ?? []

  // 섹션별로 다른 상품 슬라이스 사용
  const s1 = best.slice(0, 10)          // 할인 특가
  const s2 = fresh.slice(0, 10)         // 새로 나왔어요
  const s3 = best.slice(3, 13)          // 지금 가장 많이 담고 있어요
  const s4 = fresh.slice(3, 13)         // 지금 안사면 후회해요
  const s5 = best.slice(5, 15)          // 이 상품, 어때요
  const s6 = best.slice(0, 10)          // 실시간 랭킹 (rank list)
  const s7 = fresh.slice(5, 15)         // 후기가 좋은 신상품
  const s8 = best.slice(7, 17)          // AI가 추천합니다
  const s9 = fresh.slice(0, 10)         // 품절임박!!!
  const s10 = best.slice(2, 12)         // 단골손님의 장바구니

  return (
    <div className="bg-white">
      {/* ① 메인 배너 */}
      <MobileBannerSlider banners={banners ?? []} />

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
        <section>
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
            <MobileGoodsCard key={item.id} goods={item} />
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
