// 토스페이먼츠 서버사이드 API

const TOSS_BASE_URL = 'https://api.tosspayments.com/v1'

function getAuthHeader() {
  const secret = process.env.TOSS_SECRET_KEY!
  return `Basic ${Buffer.from(`${secret}:`).toString('base64')}`
}

// 결제 승인
export async function confirmPayment(paymentKey: string, orderId: string, amount: number) {
  const res = await fetch(`${TOSS_BASE_URL}/payments/confirm`, {
    method: 'POST',
    headers: {
      Authorization: getAuthHeader(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ paymentKey, orderId, amount }),
  })
  const data = await res.json()
  if (!res.ok) throw new Error(data.message ?? '결제 승인 실패')
  return data
}

// 결제 취소
export async function cancelPayment(paymentKey: string, cancelReason: string, cancelAmount?: number) {
  const body: Record<string, unknown> = { cancelReason }
  if (cancelAmount != null) body.cancelAmount = cancelAmount

  const res = await fetch(`${TOSS_BASE_URL}/payments/${paymentKey}/cancel`, {
    method: 'POST',
    headers: {
      Authorization: getAuthHeader(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })
  const data = await res.json()
  if (!res.ok) throw new Error(data.message ?? '결제 취소 실패')
  return data
}

// 결제 조회
export async function getPayment(paymentKey: string) {
  const res = await fetch(`${TOSS_BASE_URL}/payments/${paymentKey}`, {
    headers: { Authorization: getAuthHeader() },
  })
  const data = await res.json()
  if (!res.ok) throw new Error(data.message ?? '결제 조회 실패')
  return data
}
