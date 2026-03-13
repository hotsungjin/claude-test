import { getAdminUser } from '@/lib/admin-auth'
import { redirect } from 'next/navigation'
import AdminListClient from './AdminListClient'

export default async function AdminsPage() {
  const admin = await getAdminUser()
  if (!admin || admin.role !== 'super') redirect('/admin/dashboard')

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">관리자 관리</h1>
      </div>
      <AdminListClient currentAdminId={admin.id} />
    </div>
  )
}
