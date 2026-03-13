import EmailFormClient from './EmailFormClient'

export default function AdminEmailPage() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">이메일 발송</h1>
      <EmailFormClient />
    </div>
  )
}
