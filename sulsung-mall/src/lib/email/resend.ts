import { Resend } from 'resend'

let _resend: Resend | null = null
function getResend() {
  if (!_resend) _resend = new Resend(process.env.RESEND_API_KEY!)
  return _resend
}
const FROM = process.env.RESEND_FROM_EMAIL ?? '설성목장몰 <noreply@sulsung.co.kr>'

export async function sendEmail(params: {
  to: string
  subject: string
  html: string
}) {
  const { data, error } = await getResend().emails.send({
    from: FROM,
    to: params.to,
    subject: params.subject,
    html: params.html,
  })
  if (error) throw new Error(error.message)
  return data
}

// ── 이메일 템플릿 ──

function baseTemplate(content: string) {
  return `
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">
<div style="max-width:600px;margin:0 auto;padding:20px">
  <div style="background:#968774;padding:20px 24px;border-radius:12px 12px 0 0">
    <h1 style="margin:0;color:#fff;font-size:18px">설성목장몰</h1>
  </div>
  <div style="background:#fff;padding:32px 24px;border-radius:0 0 12px 12px">
    ${content}
  </div>
  <div style="text-align:center;padding:20px;color:#999;font-size:12px">
    <p>설성목장몰 | 경기도 이천시 설성면</p>
    <p>cs@sulsung.co.kr | 1588-0000</p>
  </div>
</div>
</body>
</html>`
}

// 주문 확인 이메일
export async function sendOrderConfirmEmail(params: {
  to: string
  orderNo: string
  goodsName: string
  totalAmount: string
  orderDetailUrl: string
}) {
  return sendEmail({
    to: params.to,
    subject: `[설성목장몰] 주문이 확인되었습니다 (${params.orderNo})`,
    html: baseTemplate(`
      <h2 style="margin:0 0 16px;color:#333;font-size:20px">주문이 확인되었습니다</h2>
      <p style="color:#666;line-height:1.6;margin:0 0 24px">
        주문해 주셔서 감사합니다. 아래 내용을 확인해 주세요.
      </p>
      <div style="background:#f9f9f9;border-radius:8px;padding:16px;margin-bottom:24px">
        <table style="width:100%;border-collapse:collapse;font-size:14px">
          <tr><td style="padding:8px 0;color:#999">주문번호</td><td style="padding:8px 0;text-align:right;font-weight:600;color:#333">${params.orderNo}</td></tr>
          <tr><td style="padding:8px 0;color:#999">상품</td><td style="padding:8px 0;text-align:right;color:#333">${params.goodsName}</td></tr>
          <tr style="border-top:1px solid #eee"><td style="padding:12px 0 8px;color:#999;font-weight:600">결제금액</td><td style="padding:12px 0 8px;text-align:right;font-weight:700;color:#968774;font-size:18px">${params.totalAmount}</td></tr>
        </table>
      </div>
      <a href="${params.orderDetailUrl}" style="display:block;text-align:center;background:#968774;color:#fff;padding:14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">
        주문 상세 보기
      </a>
    `),
  })
}

// 배송 시작 이메일
export async function sendShippingEmail(params: {
  to: string
  orderNo: string
  courierName: string
  trackingNo: string
  trackingUrl: string
}) {
  return sendEmail({
    to: params.to,
    subject: `[설성목장몰] 상품이 발송되었습니다 (${params.orderNo})`,
    html: baseTemplate(`
      <h2 style="margin:0 0 16px;color:#333;font-size:20px">상품이 발송되었습니다 🚚</h2>
      <p style="color:#666;line-height:1.6;margin:0 0 24px">
        주문하신 상품이 발송되었습니다. 배송 정보를 확인해 주세요.
      </p>
      <div style="background:#f9f9f9;border-radius:8px;padding:16px;margin-bottom:24px">
        <table style="width:100%;border-collapse:collapse;font-size:14px">
          <tr><td style="padding:8px 0;color:#999">주문번호</td><td style="padding:8px 0;text-align:right;color:#333">${params.orderNo}</td></tr>
          <tr><td style="padding:8px 0;color:#999">택배사</td><td style="padding:8px 0;text-align:right;color:#333">${params.courierName}</td></tr>
          <tr><td style="padding:8px 0;color:#999">운송장번호</td><td style="padding:8px 0;text-align:right;font-weight:600;color:#333">${params.trackingNo}</td></tr>
        </table>
      </div>
      <a href="${params.trackingUrl}" style="display:block;text-align:center;background:#968774;color:#fff;padding:14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">
        배송 조회하기
      </a>
    `),
  })
}

// 비밀번호 재설정 이메일
export async function sendPasswordResetEmail(params: {
  to: string
  resetUrl: string
}) {
  return sendEmail({
    to: params.to,
    subject: '[설성목장몰] 비밀번호 재설정',
    html: baseTemplate(`
      <h2 style="margin:0 0 16px;color:#333;font-size:20px">비밀번호 재설정</h2>
      <p style="color:#666;line-height:1.6;margin:0 0 24px">
        비밀번호 재설정을 요청하셨습니다. 아래 버튼을 클릭하여 새 비밀번호를 설정해 주세요.
        <br>본인이 요청하지 않았다면 이 이메일을 무시해 주세요.
      </p>
      <a href="${params.resetUrl}" style="display:block;text-align:center;background:#968774;color:#fff;padding:14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">
        비밀번호 재설정
      </a>
      <p style="color:#999;font-size:12px;margin-top:16px;text-align:center">
        이 링크는 1시간 후 만료됩니다.
      </p>
    `),
  })
}

// 회원가입 환영 이메일
export async function sendWelcomeEmail(params: {
  to: string
  name: string
}) {
  return sendEmail({
    to: params.to,
    subject: '[설성목장몰] 회원가입을 환영합니다!',
    html: baseTemplate(`
      <h2 style="margin:0 0 16px;color:#333;font-size:20px">${params.name}님, 환영합니다! 🎉</h2>
      <p style="color:#666;line-height:1.6;margin:0 0 24px">
        설성목장몰에 가입해 주셔서 감사합니다.<br>
        설성목장에서 직접 키운 신선한 제품을 만나보세요.
      </p>
      <div style="background:#f0ebe4;border-radius:8px;padding:20px;text-align:center;margin-bottom:24px">
        <p style="color:#968774;font-size:14px;margin:0 0 4px">가입 축하 포인트</p>
        <p style="color:#968774;font-size:28px;font-weight:700;margin:0">1,000P</p>
      </div>
      <a href="${process.env.NEXT_PUBLIC_SITE_URL ?? 'https://sulsung.co.kr'}" style="display:block;text-align:center;background:#968774;color:#fff;padding:14px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">
        쇼핑 시작하기
      </a>
    `),
  })
}

// 관리자 대량 발송
export async function sendBulkEmail(params: {
  recipients: string[]
  subject: string
  content: string
}) {
  const results = { sent: 0, failed: 0 }
  for (const to of params.recipients) {
    try {
      await sendEmail({
        to,
        subject: params.subject,
        html: baseTemplate(`
          <div style="color:#333;line-height:1.8;font-size:14px">
            ${params.content}
          </div>
        `),
      })
      results.sent++
    } catch {
      results.failed++
    }
  }
  return results
}
