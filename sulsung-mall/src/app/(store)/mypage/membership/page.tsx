'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import MypageHeader from '@/components/store/mypage/MypageHeader'
import { formatPrice } from '@/utils/format'
import { GRADE_BENEFITS } from '@/constants'
import { Crown, Truck, Percent, Gift, ShoppingBag } from 'lucide-react'

interface Subscription {
  id: number
  status: string
  started_at: string
  expires_at: string
  cancelled_at: string | null
  membership_plans: {
    name: string
    interval: string
    price: number
  }
}

export default function MembershipPage() {
  const router = useRouter()
  const [subscription, setSubscription] = useState<Subscription | null>(null)
  const [grade, setGrade] = useState('bronze')
  const [loading, setLoading] = useState(true)
  const [cancelling, setCancelling] = useState(false)

  useEffect(() => {
    fetch('/api/v1/membership')
      .then(r => r.json())
      .then(d => {
        setSubscription(d.subscription)
        setGrade(d.grade ?? 'bronze')
        setLoading(false)
      })
  }, [])

  const isMember = grade === 'silver' || grade === 'gold' || grade === 'vip'

  async function handleCancel() {
    if (!confirm('멤버십을 해지하시겠습니까?\n해지 시 멤버십 혜택이 즉시 중단됩니다.')) return
    setCancelling(true)
    try {
      const res = await fetch('/api/v1/membership', { method: 'DELETE' })
      if (res.ok) {
        alert('멤버십이 해지되었습니다.')
        router.refresh()
        setSubscription(null)
        setGrade('bronze')
      } else {
        const data = await res.json()
        alert(data.error ?? '해지 처리에 실패했습니다.')
      }
    } catch {
      alert('오류가 발생했습니다.')
    } finally {
      setCancelling(false)
    }
  }

  if (loading) {
    return (
      <div style={{ backgroundColor: '#fff', minHeight: '60vh' }}>
        <MypageHeader title="오늘도설성" />
        <div className="flex items-center justify-center" style={{ height: '40vh' }}>
          <div className="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin" style={{ borderColor: '#6B9E6B', borderTopColor: 'transparent' }} />
        </div>
      </div>
    )
  }

  // 멤버십 미가입 상태
  if (!isMember || !subscription) {
    return (
      <div style={{ backgroundColor: '#fff', minHeight: '60vh' }}>
        <MypageHeader title="오늘도설성" />
        <div className="px-6 py-12 text-center">
          <Crown className="w-14 h-14 mx-auto mb-4" style={{ color: '#ddd' }} />
          <h2 className="text-[18px] font-bold mb-2" style={{ color: '#333' }}>멤버십에 가입해보세요</h2>
          <p className="text-[14px] mb-8" style={{ color: '#999' }}>
            멤버십 전용가, 무료배송 혜택 등<br />
            다양한 혜택을 누릴 수 있습니다
          </p>
          <Link href="/membership"
            className="inline-block px-10 py-3.5 rounded-xl font-bold text-[15px] text-white"
            style={{ backgroundColor: '#6B9E6B' }}>
            멤버십 가입하기
          </Link>
        </div>
      </div>
    )
  }

  const benefits = GRADE_BENEFITS[grade as keyof typeof GRADE_BENEFITS] ?? GRADE_BENEFITS.silver
  const plan = subscription.membership_plans

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '60vh' }}>
      <MypageHeader title="오늘도설성" />

      {/* 멤버십 카드 */}
      <div className="mx-5 mt-4 p-5 rounded-2xl" style={{ background: 'linear-gradient(135deg, #6B9E6B 0%, #8BBF8B 100%)' }}>
        <div className="flex items-center gap-2 mb-3">
          <Crown className="w-5 h-5 text-white" />
          <span className="text-[14px] font-bold text-white/80">오늘도설성 멤버십</span>
        </div>
        <p className="text-[20px] font-extrabold text-white mb-1">{plan.name}</p>
        <p className="text-[13px] text-white/70">
          {new Date(subscription.started_at).toLocaleDateString('ko-KR')} ~ {new Date(subscription.expires_at).toLocaleDateString('ko-KR')}
        </p>
        <div className="mt-4 pt-3 border-t border-white/20 flex justify-between items-center">
          <span className="text-[14px] text-white/80">
            {plan.interval === 'monthly' ? '월간' : '연간'} {formatPrice(plan.price)}
          </span>
          <span className="text-[12px] px-3 py-1 rounded-full bg-white/20 text-white font-medium">
            이용중
          </span>
        </div>
      </div>

      {/* 나의 혜택 */}
      <div className="px-5 py-6">
        <h3 className="text-[16px] font-bold mb-4" style={{ color: '#333' }}>나의 혜택</h3>
        <div className="grid grid-cols-2 gap-3">
          {[
            { icon: ShoppingBag, label: '멤버십 전용가', value: '적용 중' },
            { icon: Truck, label: '무료배송 기준', value: formatPrice(benefits.freeShippingThreshold) },
            { icon: Percent, label: '포인트 적립률', value: `${benefits.mileageRate * 100}%` },
            { icon: Gift, label: '생일 쿠폰', value: formatPrice(benefits.birthdayCoupon) },
          ].map(({ icon: Icon, label, value }) => (
            <div key={label} className="p-4 rounded-xl" style={{ backgroundColor: '#f0f7f0' }}>
              <Icon className="w-5 h-5 mb-2" style={{ color: '#6B9E6B' }} />
              <p className="text-[12px] mb-0.5" style={{ color: '#999' }}>{label}</p>
              <p className="text-[15px] font-bold" style={{ color: '#333' }}>{value}</p>
            </div>
          ))}
        </div>
      </div>

      {/* 해지 */}
      <div className="px-5 pb-8">
        <button
          onClick={handleCancel}
          disabled={cancelling}
          className="w-full py-3 rounded-xl text-[14px] border"
          style={{ borderColor: '#e5e5e5', color: '#999' }}
        >
          {cancelling ? '처리 중...' : '멤버십 해지'}
        </button>
        <p className="text-center text-[12px] mt-2" style={{ color: '#ccc' }}>
          해지 시 멤버십 혜택이 즉시 중단됩니다
        </p>
      </div>
    </div>
  )
}
