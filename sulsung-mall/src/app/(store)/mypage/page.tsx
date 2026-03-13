import { createClient } from '@/lib/supabase/server'
import Link from 'next/link'
import { Package, Ticket, Heart, MessageSquare, ChevronRight } from 'lucide-react'

const GRADE_LABEL: Record<string, string> = {
  bronze: '브론즈',
  silver: '실버',
  gold: '골드',
  vip: 'VIP',
}

export default async function MypagePage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  const { data: memberRaw } = await supabase.from('members').select('id, name, grade, mileage, coupon_count').eq('auth_id', user.id).single()
  const member = memberRaw as any

  return (
    <div style={{ backgroundColor: '#fff' }}>
      {/* 인사 + 등급 */}
      <div className="px-6 pt-6 pb-4">
        <p className="text-[13px]" style={{ color: '#968774' }}>
          반가워요! <span className="text-[18px] font-bold" style={{ color: '#222' }}>{member?.name ?? '회원'}님</span>
        </p>
      </div>

      {/* 적립금 / 쿠폰 카드 */}
      <div className="mx-6 rounded-xl overflow-hidden" style={{ border: '1px solid #e8e4df' }}>
        <Link href="/mypage/mileage" className="flex items-center justify-between px-4 py-3.5"
          style={{ borderBottom: '1px solid #f0ece7' }}>
          <span className="text-[14px]" style={{ color: '#555' }}>적립금</span>
          <span className="flex items-center gap-1">
            <span className="text-[15px] font-bold" style={{ color: '#222' }}>{(member?.mileage ?? 0).toLocaleString()}원</span>
            <ChevronRight className="w-4 h-4" style={{ color: '#bbb' }} />
          </span>
        </Link>
        <Link href="/mypage/coupons" className="flex items-center justify-between px-4 py-3.5">
          <span className="text-[14px]" style={{ color: '#555' }}>쿠폰</span>
          <span className="flex items-center gap-1">
            <span className="text-[15px] font-bold" style={{ color: '#222' }}>{member?.coupon_count ?? 0}장</span>
            <ChevronRight className="w-4 h-4" style={{ color: '#bbb' }} />
          </span>
        </Link>
      </div>

      {/* 퀵 메뉴 아이콘 4개 */}
      <div className="flex justify-around px-6 py-6">
        {[
          { href: '/mypage/orders', icon: Package, label: '주문내역' },
          { href: '/mypage/coupons', icon: Ticket, label: '쿠폰' },
          { href: '/mypage/wishlist', icon: Heart, label: '찜' },
          { href: '/mypage/reviews', icon: MessageSquare, label: '후기' },
        ].map(({ href, icon: Icon, label }) => (
          <Link key={href} href={href} className="flex flex-col items-center gap-1.5">
            <div className="w-12 h-12 rounded-full flex items-center justify-center"
              style={{ backgroundColor: '#f5f2ef' }}>
              <Icon className="w-5 h-5" style={{ color: '#968774' }} />
            </div>
            <span className="text-[12px]" style={{ color: '#555' }}>{label}</span>
          </Link>
        ))}
      </div>

      {/* 구분선 */}
      <div style={{ height: '8px', backgroundColor: '#f5f5f5' }} />

      {/* 혜택 섹션 */}
      <Section title="혜택">
        <MenuRow href="/mypage/referral" label="친구초대" sub="리워드 받기" />
        <MenuRow href="/mypage/mileage" label="마일리지" sub="적립금 확인" />
      </Section>

      <Divider />

      {/* 내 정보관리 */}
      <Section title="내 정보관리">
        <MenuRow href="/mypage/addresses" label="배송지 관리" />
        <MenuRow href="/mypage/inquiries" label="1:1 문의" />
      </Section>

      <Divider />

      {/* 고객 지원 */}
      <Section title="고객 지원">
        <MenuRow href="/notice" label="공지사항" />
        <MenuRow href="/faq" label="자주하는 질문" />
        <MenuRow href="/mypage/inquiries/new" label="문의하기" />
      </Section>

      <Divider />

      {/* 로그아웃 */}
      <div className="px-6 py-6">
        <form action="/api/v1/auth/signout" method="POST">
          <button type="submit" className="text-[15px] font-bold" style={{ color: '#333' }}>
            로그아웃
          </button>
        </form>
      </div>

      <div style={{ height: '40px' }} />
    </div>
  )
}

function Divider() {
  return <div className="mx-6" style={{ height: '1px', backgroundColor: '#ebebeb' }} />
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="px-6 py-5">
      <p className="text-[12px] font-medium mb-3" style={{ color: '#aaa' }}>{title}</p>
      <div className="grid grid-cols-2 gap-y-4">{children}</div>
    </div>
  )
}

function MenuRow({ href, label, sub }: { href: string; label: string; sub?: string }) {
  return (
    <Link href={href} className="block">
      <p className="text-[15px] font-medium" style={{ color: '#333' }}>{label}</p>
      {sub && <p className="text-[12px] mt-0.5" style={{ color: '#968774' }}>{sub}</p>}
    </Link>
  )
}
