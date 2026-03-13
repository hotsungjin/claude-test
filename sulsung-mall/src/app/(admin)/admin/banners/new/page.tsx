import BannerFormClient from '../BannerFormClient'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'

export default function NewBannerPage() {
  return (
    <div>
      <div className="flex items-center gap-3 mb-6">
        <Link href="/admin/banners" className="text-gray-400 hover:text-gray-600">
          <ChevronLeft className="w-5 h-5" />
        </Link>
        <h1 className="text-2xl font-bold text-gray-900">배너 등록</h1>
      </div>
      <BannerFormClient />
    </div>
  )
}
