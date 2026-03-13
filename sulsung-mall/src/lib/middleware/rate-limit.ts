// 인메모리 Rate Limiter (Vercel Serverless 환경용)
// 프로덕션에서는 Upstash Redis 기반으로 교체 권장

interface RateLimitEntry {
  count: number
  resetAt: number
}

const store = new Map<string, RateLimitEntry>()

// 5분마다 만료된 엔트리 정리
setInterval(() => {
  const now = Date.now()
  for (const [key, entry] of store) {
    if (entry.resetAt < now) store.delete(key)
  }
}, 300000)

export function rateLimit(options: {
  key: string
  limit?: number      // 허용 횟수 (기본 60)
  windowMs?: number   // 윈도우 크기 ms (기본 60초)
}): { success: boolean; remaining: number; resetAt: number } {
  const limit = options.limit ?? 60
  const windowMs = options.windowMs ?? 60000
  const now = Date.now()

  const entry = store.get(options.key)

  if (!entry || entry.resetAt < now) {
    store.set(options.key, { count: 1, resetAt: now + windowMs })
    return { success: true, remaining: limit - 1, resetAt: now + windowMs }
  }

  entry.count++
  if (entry.count > limit) {
    return { success: false, remaining: 0, resetAt: entry.resetAt }
  }

  return { success: true, remaining: limit - entry.count, resetAt: entry.resetAt }
}

// IP 추출 헬퍼
export function getClientIp(req: Request): string {
  return (
    req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ??
    req.headers.get('x-real-ip') ??
    'unknown'
  )
}
