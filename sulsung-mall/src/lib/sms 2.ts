import { SolapiMessageService } from 'solapi'

const messageService = new SolapiMessageService(
  process.env.SOLAPI_API_KEY!,
  process.env.SOLAPI_API_SECRET!
)

const SENDER_PHONE = process.env.SOLAPI_SENDER_PHONE!

export async function sendSMS(to: string, text: string) {
  const res = await messageService.sendOne({
    to,
    from: SENDER_PHONE,
    text,
  })
  return res
}

export function generateCode(): string {
  return Math.floor(100000 + Math.random() * 900000).toString()
}

/** 전화번호 정규화: 하이픈 제거, 010으로 시작하는지 검증 */
export function normalizePhone(phone: string): string | null {
  const cleaned = phone.replace(/[^0-9]/g, '')
  if (/^01[016789]\d{7,8}$/.test(cleaned)) return cleaned
  return null
}
