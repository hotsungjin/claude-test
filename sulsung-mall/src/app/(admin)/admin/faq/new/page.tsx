import FaqFormClient from '../FaqFormClient'

export default function AdminFaqNewPage() {
  return (
    <div className="max-w-2xl">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">FAQ 등록</h1>
      <FaqFormClient />
    </div>
  )
}
