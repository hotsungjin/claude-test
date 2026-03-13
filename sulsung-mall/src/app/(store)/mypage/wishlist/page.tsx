import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'
import Link from 'next/link'
import { formatPrice, calcDiscountRate } from '@/utils/format'
import WishlistClient from './WishlistClient'
import MypageHeader from '@/components/store/mypage/MypageHeader'

export default async function WishlistPage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) redirect('/auth/login')

  const { data: member } = await supabase.from('members').select('id').eq('auth_id', user.id).single()
  if (!member) redirect('/auth/login')

  const { data: wishlists } = await supabase
    .from('wishlists')
    .select('id, goods_id, goods(id, name, slug, price, sale_price, thumbnail_url, status)')
    .eq('member_id', member.id)
    .order('created_at', { ascending: false })

  const items = (wishlists ?? []) as any[]

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="찜 목록" />

      {items.length === 0 ? (
        <div className="text-center py-20">
          <p className="text-[40px] mb-3">&#x2661;</p>
          <p className="text-[14px] font-medium" style={{ color: '#666' }}>찜한 상품이 없습니다</p>
          <p className="text-[12px] mt-1" style={{ color: '#aaa' }}>마음에 드는 상품을 찜해보세요</p>
          <Link href="/goods"
            className="inline-block mt-4 px-6 py-2.5 rounded-full text-[13px] font-medium"
            style={{ backgroundColor: '#968774', color: '#fff' }}>
            상품 보러가기
          </Link>
        </div>
      ) : (
        <WishlistClient items={items} />
      )}
    </div>
  )
}
