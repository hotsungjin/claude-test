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
