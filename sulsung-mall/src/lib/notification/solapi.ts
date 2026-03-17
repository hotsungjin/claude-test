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

// 카카오 알림톡 발송 (실패 시 SMS 자동 폴백)
export async function sendAlimtalk(params: {
  to: string
  templateId: string
  variables: Record<string, string>
  fallbackText?: string
}) {
  const pfId = process.env.SOLAPI_PFID
  // PFID 없으면 SMS 폴백
  if (!pfId && params.fallbackText) {
    return sendSms({ to: params.to, text: params.fallbackText })
  }

  const res = await fetch(`${SOLAPI_BASE}/messages/v4/send`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: JSON.stringify({
      message: {
        to: params.to,
        from: process.env.SOLAPI_SENDER_PHONE,
        text: params.fallbackText,               // 알림톡 실패 시 SMS로 보낼 텍스트
        kakaoOptions: {
          pfId,
          templateId: params.templateId,
          variables: params.variables,
          disableSms: false,                      // 실패 시 SMS 대체 발송
        },
      },
    }),
  })
  const data = await res.json()
  if (!res.ok) {
    // 알림톡 실패 시 SMS 폴백 시도
    if (params.fallbackText) {
      try {
        return await sendSms({ to: params.to, text: params.fallbackText })
      } catch {}
    }
    throw new Error(data.message ?? '알림톡 발송 실패')
  }
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
    templateId: 'ORDER_CONFIRM',
    variables: {
      '#{주문번호}': params.orderNo,
      '#{상품명}': params.goodsName,
      '#{결제금액}': params.totalAmount,
    },
    fallbackText: `[설성목장몰] 주문이 완료되었습니다.\n주문번호: ${params.orderNo}\n상품: ${params.goodsName}\n결제금액: ${params.totalAmount}`,
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
    fallbackText: `[설성목장몰] 주문이 발송되었습니다.\n주문번호: ${params.orderNo}\n택배사: ${params.courierName}\n운송장번호: ${params.trackingNo}`,
  })
}

// 장바구니 리마인드
export async function sendCartReminder(params: {
  phone: string
  memberName: string
  goodsName: string
  itemCount: number
}) {
  const itemText = params.itemCount > 1 ? ` 외 ${params.itemCount - 1}건` : ''
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'
  const fallback = `[설성목장몰] ${params.memberName}님, 장바구니에 담아두신 '${params.goodsName}'${itemText}이 기다리고 있어요! 지금 주문하시면 신선하게 배송해 드릴게요.\n\n${siteUrl}/cart`

  return sendAlimtalk({
    to: params.phone,
    templateId: 'CART_REMINDER',
    variables: {
      '#{고객명}': params.memberName,
      '#{상품명}': params.goodsName,
      '#{상품수}': itemText || '',
    },
    fallbackText: fallback,
  })
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
    fallbackText: `[설성목장몰] 주문이 취소되었습니다.\n주문번호: ${params.orderNo}\n환불금액: ${params.cancelAmount}`,
  })
}

// 휴면 전환 알림
export async function sendDormantConverted(params: {
  phone: string
  memberName: string
}) {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'
  return sendAlimtalk({
    to: params.phone,
    templateId: 'DORMANT_CONVERTED',
    variables: {
      '#{고객명}': params.memberName,
    },
    fallbackText: `[설성목장몰] ${params.memberName}님, 90일간 미접속으로 계정이 휴면 처리되었습니다. 로그인하시면 바로 정상 이용 가능합니다.\n\n${siteUrl}/auth/login`,
  })
}

// 휴면 예고 알림
export async function sendDormantWarning(params: {
  phone: string
  memberName: string
}) {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'
  return sendAlimtalk({
    to: params.phone,
    templateId: 'DORMANT_WARNING',
    variables: {
      '#{고객명}': params.memberName,
    },
    fallbackText: `[설성목장몰] ${params.memberName}님, 10일 후 장기 미접속으로 계정이 휴면 처리됩니다. 지금 로그인하시면 유지됩니다!\n\n${siteUrl}/auth/login`,
  })
}

// 인증번호 발송
export async function sendVerificationCode(params: {
  phone: string
  code: string
}) {
  return sendAlimtalk({
    to: params.phone,
    templateId: 'VERIFY_CODE',
    variables: {
      '#{인증번호}': params.code,
    },
    fallbackText: `[설성목장몰] 인증번호 ${params.code}를 입력해주세요.`,
  })
}
