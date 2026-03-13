import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'
import TimeSaleFormClient from '../TimeSaleFormClient'

export default async function EditTimeSalePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const supabase = await createClient() as any
  const { data } = await supabase.from('time_sales').select('*').eq('id', id).single()
  if (!data) notFound()

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">타임세일 수정</h1>
      <TimeSaleFormClient initialData={data} />
    </div>
  )
}
