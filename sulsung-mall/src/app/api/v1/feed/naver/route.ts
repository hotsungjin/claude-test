import { NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/server'

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung-mall.com'
const SITE_NAME = '설성목장몰'

function escapeXml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
}

export async function GET() {
  const supabase = await createAdminClient()

  const { data: goods } = await (supabase as any)
    .from('goods')
    .select('id, name, slug, summary, price, sale_price, thumbnail_url, stock, naver_category, brand, categories(name)')
    .eq('status', 'active')
    .gt('stock', 0)
    .order('created_at', { ascending: false })
    .limit(5000)

  const items = (goods ?? []).map((item: any) => {
    const price = item.sale_price ?? item.price
    const imageUrl = item.thumbnail_url ?? ''
    const categoryName = item.categories?.name ?? '식품'
    const productUrl = `${SITE_URL}/goods/${item.slug}`

    return `    <item>
      <title>${escapeXml(item.name)}</title>
      <link>${escapeXml(productUrl)}</link>
      <image>${escapeXml(imageUrl)}</image>
      <price>${price}</price>
      <g:price>${price} KRW</g:price>
      <normal_price>${item.price}</normal_price>
      ${item.sale_price ? `<sale_price>${item.sale_price}</sale_price>` : ''}
      <category_name>${escapeXml(categoryName)}</category_name>
      ${item.naver_category ? `<naver_category>${escapeXml(item.naver_category)}</naver_category>` : ''}
      ${item.brand ? `<brand>${escapeXml(item.brand)}</brand>` : ''}
      <stock_status>${item.stock > 0 ? 'instock' : 'outofstock'}</stock_status>
      <delivery_fee>3000</delivery_fee>
      <product_id>${escapeXml(item.id)}</product_id>
    </item>`
  }).join('\n')

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>${SITE_NAME}</title>
    <link>${SITE_URL}</link>
    <description>${SITE_NAME} 상품 피드</description>
    <lastBuildDate>${new Date().toUTCString()}</lastBuildDate>
${items}
  </channel>
</rss>`

  return new NextResponse(xml, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, max-age=3600',
    },
  })
}
