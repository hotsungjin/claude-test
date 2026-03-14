import { Suspense } from 'react'
import LoginClient from './LoginClient'

export default function LoginPage() {
  return (
    <Suspense fallback={<div className="min-h-screen flex items-center justify-center"><div className="animate-spin w-6 h-6 border-2 border-t-transparent rounded-full" style={{ borderColor: '#968774', borderTopColor: 'transparent' }} /></div>}>
      <LoginClient />
    </Suspense>
  )
}
