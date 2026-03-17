'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { ChevronLeft, Check, Truck, Percent, Gift, ShoppingBag } from 'lucide-react'
import { formatPrice } from '@/utils/format'
import { GRADE_BENEFITS } from '@/constants'

interface Plan {
  id: number
  name: string
  interval: 'monthly' | 'yearly'
  price: number
  duration_days: number
  description: string | null
}

export default function MembershipPage() {
  const router = useRouter()
  const [plans, setPlans] = useState<Plan[]>([])
  const [selectedPlan, setSelectedPlan] = useState<number | null>(null)
  const [subscription, setSubscription] = useState<any>(null)
  const [grade, setGrade] = useState('bronze')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    fetch('/api/v1/membership')
      .then(r => r.json())
      .then(d => {
        setPlans(d.plans ?? [])
        setSubscription(d.subscription)
        setGrade(d.grade ?? 'bronze')
        if (d.plans?.length > 0) setSelectedPlan(d.plans[0].id)
        setLoading(false)
      })
  }, [])

  const isMember = grade === 'silver' || grade === 'gold' || grade === 'vip'

  async function handleSubscribe() {
    if (!selectedPlan || submitting) return
    setSubmitting(true)
    try {
      const res = await fetch('/api/v1/membership', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ planId: selectedPlan }),
      })
      const data = await res.json()
      if (res.ok) {
        alert('멤버십 가입이 완료되었습니다!')
        router.push('/mypage/membership')
      } else {
        alert(data.error ?? '가입 처리에 실패했습니다.')
      }
    } catch {
      alert('오류가 발생했습니다.')
    } finally {
      setSubmitting(false)
    }
  }

  const benefits = GRADE_BENEFITS.silver

  if (loading) {
    return (
      <div className="flex items-center justify-center" style={{ height: '60vh' }}>
        <div className="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin" style={{ borderColor: '#6B9E6B', borderTopColor: 'transparent' }} />
      </div>
    )
  }

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <style>{`
        [data-store-header], [data-bottom-nav], [data-store-footer] { display: none !important; }
        .flex-1.pb-\\[52px\\] { padding-bottom: 0 !important; }
      `}</style>

      {/* 헤더 */}
      <div className="sticky top-0 z-50 bg-white border-b" style={{ borderColor: '#f0f0f0' }}>
        <div className="flex items-center h-[52px] px-4">
          <button onClick={() => router.back()} className="-ml-1 mr-2">
            <ChevronLeft className="w-6 h-6" style={{ color: '#222' }} />
          </button>
          <h1 className="text-[18px] font-bold" style={{ color: '#333' }}>오늘도설성 멤버십</h1>
        </div>
      </div>

      {/* 히어로 영역 */}
      <div className="px-6 pt-8 pb-6 text-center" style={{ background: 'linear-gradient(180deg, #f0f7f0 0%, #fff 100%)' }}>
        <p className="text-[13px] font-medium mb-2" style={{ color: '#6B9E6B' }}>MEMBERSHIP</p>
        <h2 className="text-[24px] font-extrabold leading-tight mb-3" style={{ color: '#333' }}>
          오늘도설성<br />멤버십 혜택
        </h2>
        <p className="text-[14px] leading-relaxed" style={{ color: '#888' }}>
          설성목장의 프리미엄 제품을<br />
          더 합리적인 가격으로 만나보세요
        </p>
      </div>

      {/* 혜택 카드 */}
      <div className="px-5 py-6">
        <h3 className="text-[16px] font-bold mb-4" style={{ color: '#333' }}>멤버십 전용 혜택</h3>
        <div className="space-y-3">
          {[
            { icon: ShoppingBag, title: '멤버십 전용가', desc: '일반가 대비 할인된 멤버십 전용 가격' },
            { icon: Truck, title: `무료배송 ${formatPrice(benefits.freeShippingThreshold)} 이상`, desc: `일반 ${formatPrice(GRADE_BENEFITS.bronze.freeShippingThreshold)} → 멤버십 ${formatPrice(benefits.freeShippingThreshold)}` },
            { icon: Percent, title: `포인트 ${benefits.mileageRate * 100}% 적립`, desc: `일반 ${GRADE_BENEFITS.bronze.mileageRate * 100}% → 멤버십 ${benefits.mileageRate * 100}%` },
            { icon: Gift, title: `생일 쿠폰 ${formatPrice(benefits.birthdayCoupon)}`, desc: `일반 ${formatPrice(GRADE_BENEFITS.bronze.birthdayCoupon)} → 멤버십 ${formatPrice(benefits.birthdayCoupon)}` },
          ].map(({ icon: Icon, title, desc }) => (
            <div key={title} className="flex items-start gap-4 p-4 rounded-xl" style={{ backgroundColor: '#f0f7f0' }}>
              <div className="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style={{ backgroundColor: '#6B9E6B' }}>
                <Icon className="w-5 h-5 text-white" />
              </div>
              <div>
                <p className="text-[15px] font-bold" style={{ color: '#333' }}>{title}</p>
                <p className="text-[13px] mt-0.5" style={{ color: '#999' }}>{desc}</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* 이미 멤버십 회원인 경우 */}
      {isMember && subscription && (
        <div className="px-5 pb-8">
          <div className="p-5 rounded-xl text-center" style={{ backgroundColor: '#f9f6f3', border: '1px solid #e8e0d8' }}>
            <Check className="w-8 h-8 mx-auto mb-2" style={{ color: '#6B9E6B' }} />
            <p className="text-[16px] font-bold mb-1" style={{ color: '#333' }}>멤버십 이용 중</p>
            <p className="text-[13px]" style={{ color: '#999' }}>
              {new Date(subscription.expires_at).toLocaleDateString('ko-KR')}까지
            </p>
          </div>
        </div>
      )}

      {/* 플랜 선택 (비회원/일반회원만) */}
      {!isMember && plans.length > 0 && (
        <div className="px-5 pb-6">
          <h3 className="text-[16px] font-bold mb-4" style={{ color: '#333' }}>플랜 선택</h3>
          <div className="space-y-3">
            {plans.map(plan => {
              const isSelected = selectedPlan === plan.id
              const monthlyPrice = plan.interval === 'yearly'
                ? Math.floor(plan.price / 12)
                : plan.price
              return (
                <button
                  key={plan.id}
                  onClick={() => setSelectedPlan(plan.id)}
                  className="w-full text-left p-4 rounded-xl border-2 transition-colors"
                  style={{
                    borderColor: isSelected ? '#968774' : '#e5e5e5',
                    backgroundColor: isSelected ? '#faf8f6' : '#fff',
                  }}
                >
                  <div className="flex items-center justify-between mb-1">
                    <div className="flex items-center gap-2">
                      <span className="text-[16px] font-bold" style={{ color: '#333' }}>{plan.name}</span>
                      {plan.interval === 'yearly' && (
                        <span className="text-[11px] font-bold px-2 py-0.5 rounded-full text-white" style={{ backgroundColor: '#e84a3b' }}>
                          추천
                        </span>
                      )}
                    </div>
                    <div className="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                      style={{ borderColor: isSelected ? '#968774' : '#ddd' }}>
                      {isSelected && <div className="w-3 h-3 rounded-full" style={{ backgroundColor: '#6B9E6B' }} />}
                    </div>
                  </div>
                  <p className="text-[20px] font-extrabold" style={{ color: '#333' }}>
                    {formatPrice(plan.price)}
                    <span className="text-[14px] font-normal" style={{ color: '#999' }}>
                      /{plan.interval === 'monthly' ? '월' : '년'}
                    </span>
                  </p>
                  {plan.interval === 'yearly' && (
                    <p className="text-[13px] mt-1" style={{ color: '#6B9E6B' }}>
                      월 {formatPrice(monthlyPrice)} (월간 대비 17% 할인)
                    </p>
                  )}
                  {plan.description && (
                    <p className="text-[13px] mt-1" style={{ color: '#999' }}>{plan.description}</p>
                  )}
                </button>
              )
            })}
          </div>
        </div>
      )}

      {/* 가입 버튼 */}
      {!isMember && (
        <div className="sticky bottom-0 z-40 bg-white px-5 py-4 border-t" style={{ borderColor: '#f0f0f0' }}>
          <button
            onClick={handleSubscribe}
            disabled={!selectedPlan || submitting}
            className="w-full py-4 rounded-xl font-bold text-[16px] text-white disabled:opacity-50"
            style={{ backgroundColor: '#6B9E6B' }}
          >
            {submitting ? '처리 중...' : '멤버십 가입하기'}
          </button>
          <p className="text-center text-[12px] mt-2" style={{ color: '#bbb' }}>
            결제 모듈 연동 전 테스트 — 바로 활성화됩니다
          </p>
        </div>
      )}
    </div>
  )
}
