import { createAdminClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import BannerFormClient from '../BannerFormClient'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'

export default async function EditBannerPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createAdminClient() as any
  const { data: banner } = await supabase.from('banners').select('*').eq('id', id).single()
  if (!banner) notFound()

  return (
    <div>
      <div className="flex items-center gap-3 mb-6">
        <Link href="/admin/banners" className="text-gray-400 hover:text-gray-600">
          <ChevronLeft className="w-5 h-5" />
        </Link>
        <h1 className="text-2xl font-bold text-gray-900">배너 수정</h1>
      </div>
      <BannerFormClient banner={banner} />
    </div>
  )
}
