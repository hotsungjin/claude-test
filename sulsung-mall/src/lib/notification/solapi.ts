// 솔라피 API - 카카오 알림톡 + SMS 직접 연동

import crypto from 'crypto'

const SOLAPI_BASE = 'https://api.solapi.com'

function getAuthHeaders() {
  const apiKey = process.env.SOLAPI_API_KEY!
  const apiSecret = process.env.SOLAPI_API_SECRET!
  const date = new Date().toISOString()
  const salt = crypto.randomBytes(16).toString('hex')
  const signature = crypto
    .createHmac('sha256', apiSecret)
    .update(`${date}${salt}`)
    .digest('hex')

  return {
    Authorization: `HMAC-SHA256 apiKey=${apiKey}, date=${date}, salt=${salt}, signature=${signature}`,
    'Content-Type': 'application/json',
  }
}

// 카카오 알림톡 발송
export async function sendAlimtalk(params: {
  to: string
  templateId: string
  variables: Record<string, string>
  fallbackText?: string
}) {
  const res = await fetch(`${SOLAPI_BASE}/messages/v4/send`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: JSON.stringify({
      message: {
        to: params.to,
        from: process.env.SOLAPI_SENDER_PHONE,
        kakaoOptions: {
          pfId: process.env.SOLAPI_PFID,
          templateId: params.templateId,
          variables: params.variables,
          disableSms: false,                // 실패 시 SMS 대체 발송
        },
      },
    }),
  })
  const data = await res.json()
  if (!res.ok) throw new Error(data.message ?? '알림톡 발송 실패')
  return data
}

// SMS 발송
export async function sendSms(params: { to: string; text: string }) {
  const res = await fetch(`${SOLAPI_BASE}/messages/v4/send`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: JSON.stringify({
      message: {
        to: params.to,
        from: process.env.SOLAPI_SENDER_PHONE,
        text: params.text,
        type: params.text.length > 90 ? 'LMS' : 'SMS',
      },
    }),
  })
  const data = await res.json()
  if (!res.ok) throw new Error(data.message ?? 'SMS 발송 실패')
  return data
}

// ── 알림 유형별 발송 함수 ──

// 주문 확인
export async function sendOrderConfirm(params: {
  phone: string
  orderNo: string
  goodsName: string
  totalAmount: string
}) {
  return sendAlimtalk({
    to: params.phone,
    templateId: 'ORDER_CONFIRM',               // 카카오 비즈 승인 후 템플릿 ID 입력
    variables: {
      '#{주문번호}': params.orderNo,
      '#{상품명}': params.goodsName,
      '#{결제금액}': params.totalAmount,
    },
  })
}

// 배송 시작
export async function sendShippingStart(params: {
  phone: string
  orderNo: string
  courierName: string
  trackingNo: string
}) {
  return sendAlimtalk({
    to: params.phone,
    templateId: 'SHIPPING_START',
    variables: {
      '#{주문번호}': params.orderNo,
      '#{택배사}': params.courierName,
      '#{운송장번호}': params.trackingNo,
    },
  })
}

// 장바구니 리마인드
export async function sendCartReminder(params: {
  phone: string
  memberName: string
  goodsName: string
  itemCount: number
}) {
  const text = `[설성목장몰] ${params.memberName}님, 장바구니에 담아두신 '${params.goodsName}'${params.itemCount > 1 ? ` 외 ${params.itemCount - 1}건` : ''}이 기다리고 있어요! 지금 주문하시면 신선하게 배송해 드릴게요 🐄\n\n👉 설성목장몰 바로가기\n${process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'}/cart`
  return sendSms({ to: params.phone, text })
}

// 취소 완료
export async function sendCancelComplete(params: {
  phone: string
  orderNo: string
  cancelAmount: string
}) {
  return sendAlimtalk({
    to: params.phone,
    templateId: 'CANCEL_COMPLETE',
    variables: {
      '#{주문번호}': params.orderNo,
      '#{환불금액}': params.cancelAmount,
    },
  })
}
