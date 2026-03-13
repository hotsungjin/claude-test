import { NextResponse } from 'next/server'

// API 에러 핸들러 래퍼
export function apiHandler(
  handler: (req: Request, ctx?: any) => Promise<NextResponse>
) {
  return async (req: Request, ctx?: any) => {
    try {
      return await handler(req, ctx)
    } catch (error: any) {
      console.error(`[API Error] ${req.method} ${req.url}`, {
        message: error.message,
        stack: error.stack,
        timestamp: new Date().toISOString(),
      })

      // Zod 유효성 검사 에러
      if (error.name === 'ZodError') {
        return NextResponse.json(
          { error: '입력 데이터가 올바르지 않습니다.', details: error.issues },
          { status: 400 }
        )
      }

      // Supabase 에러
      if (error.code && error.message) {
        return NextResponse.json(
          { error: error.message },
          { status: error.status ?? 500 }
        )
      }

      return NextResponse.json(
        { error: '서버 오류가 발생했습니다.' },
        { status: 500 }
      )
    }
  }
}
