export const SITE_NAME = '설성목장몰'
export const SITE_DESCRIPTION = '설성목장에서 직접 키운 신선한 제품을 만나보세요'

export const MILEAGE_RATE = 0.01         // 기본 포인트 1%
export const REVIEW_MILEAGE = 500         // 리뷰 작성 포인트
export const PHOTO_REVIEW_MILEAGE = 1000  // 포토 리뷰 포인트

// 배송비
export const BASE_SHIPPING_FEE = 4000
export const FREE_SHIPPING_THRESHOLD = 50000
export const JEJU_EXTRA_FEE = 3000
export const ISLAND_EXTRA_FEE = 5000

// 등급 라벨
export const GRADE_LABEL: Record<string, string> = {
  bronze: '일반',
  silver: '오늘도설성',
  gold:   '골드',
  vip:    'VIP',
} as const

export const GRADE_COLOR: Record<string, string> = {
  bronze: 'bg-gray-100 text-gray-600',
  silver: 'bg-blue-100 text-blue-700',
  gold:   'bg-yellow-100 text-yellow-700',
  vip:    'bg-purple-100 text-purple-700',
} as const

// 등급별 혜택
export const GRADE_BENEFITS = {
  bronze: { freeShippingThreshold: 50000, mileageRate: 0.01, birthdayCoupon: 3000 },
  silver: { freeShippingThreshold: 30000, mileageRate: 0.03, birthdayCoupon: 10000 },
  gold:   { freeShippingThreshold: 30000, mileageRate: 0.03, birthdayCoupon: 10000 },
  vip:    { freeShippingThreshold: 0,     mileageRate: 0.05, birthdayCoupon: 30000 },
} as const

// 주문 자동 확정 (배송완료 후 N일)
export const AUTO_CONFIRM_DAYS = 7

// 포인트 만료 (적립 후 N년)
export const MILEAGE_EXPIRE_YEARS = 1

// 페이지당 아이템 수
export const ITEMS_PER_PAGE = 20
export const ADMIN_ITEMS_PER_PAGE = 30

// 회원 등급 기준 (누적 구매금액)
export const GRADE_THRESHOLDS = {
  일반: 0,
  우수: 100000,
  VIP: 500000,
  VVIP: 1000000,
} as const

// 추천 리워드 비율
export const REFERRAL_RATES = [0.05, 0.02, 0.01, 0.005, 0.002] // L1~L5

// 멤버십 상품 slug (이 상품 구매 시 멤버십 자동 활성화)
export const MEMBERSHIP_GOODS_SLUG = 'godomall-2636'
