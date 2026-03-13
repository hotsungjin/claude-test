import dayjs from 'dayjs'
import 'dayjs/locale/ko'

dayjs.locale('ko')

// 금액 포맷 (12345 → "12,345원")
export function formatPrice(amount: number): string {
  return `${amount.toLocaleString('ko-KR')}원`
}

// 날짜 포맷
export function formatDate(date: string | Date, format = 'YYYY.MM.DD'): string {
  return dayjs(date).format(format)
}

export function formatDateTime(date: string | Date): string {
  return dayjs(date).format('YYYY.MM.DD HH:mm')
}

// 주문번호 생성 (ORD20240101-0001 형식)
export function generateOrderNo(): string {
  const date = dayjs().format('YYYYMMDD')
  const random = Math.floor(Math.random() * 9000 + 1000)
  return `ORD${date}-${random}`
}

// 전화번호 포맷 (01012345678 → "010-1234-5678")
export function formatPhone(phone: string): string {
  return phone.replace(/(\d{3})(\d{3,4})(\d{4})/, '$1-$2-$3')
}

// 주문 상태 한국어
export const ORDER_STATUS_LABEL: Record<string, string> = {
  pending_payment:   '입금대기',
  paid:              '결제완료',
  preparing:         '배송준비중',
  shipped:           '배송중',
  delivered:         '배송완료',
  confirmed:         '구매확정',
  cancel_requested:  '취소요청',
  cancelled:         '취소완료',
  return_requested:  '반품요청',
  returning:         '반품중',
  returned:          '반품완료',
  exchange_requested:'교환요청',
  exchanged:         '교환완료',
}

// 할인율 계산
export function calcDiscountRate(price: number, salePrice: number): number {
  return Math.round((1 - salePrice / price) * 100)
}
