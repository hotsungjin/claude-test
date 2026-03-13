import Link from 'next/link'
import { CheckCircle } from 'lucide-react'

export default function SignupCompletePage() {
  return (
    <div className="min-h-screen flex items-center justify-center" style={{ backgroundColor: '#f7f4f1' }}>
      <div className="text-center px-4">
        <CheckCircle className="w-16 h-16 mx-auto mb-4" style={{ color: '#968774' }} />
        <h1 className="text-2xl font-bold text-gray-900 mb-2">회원가입이 완료되었습니다</h1>
        <p className="text-gray-500 mb-8">지금 바로 로그인하실 수 있습니다.</p>
        <Link href="/auth/login"
          className="inline-block text-white px-8 py-3 rounded-xl font-semibold transition-colors"
          style={{ backgroundColor: '#968774' }}>
          로그인하기
        </Link>
      </div>
    </div>
  )
}
