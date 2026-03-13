import { getAdminUser } from '@/lib/admin-auth'
import { redirect } from 'next/navigation'
import CurationDetailClient from './CurationDetailClient'

export default async function CurationDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const admin = await getAdminUser()
  if (!admin) redirect('/admin/login')
  const { id } = await params

  return <CurationDetailClient curationId={id} />
}
