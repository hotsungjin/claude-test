import { getAdminUser } from '@/lib/admin-auth'
import { redirect } from 'next/navigation'
import CurationListClient from './CurationListClient'

export default async function CurationsPage() {
  const admin = await getAdminUser()
  if (!admin) redirect('/admin/login')

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">기획전 관리</h1>
      </div>
      <CurationListClient />
    </div>
  )
}
