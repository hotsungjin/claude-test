import { createClient } from '@/lib/supabase/server'
import { notFound } from 'next/navigation'

const VALID_SLUGS = ['terms', 'privacy', 'marketing']

export default async function PolicyPage({
  params,
}: {
  params: Promise<{ slug: string }>
}) {
  const { slug } = await params
  if (!VALID_SLUGS.includes(slug)) notFound()

  const supabase = await createClient()
  const { data } = await (supabase as any)
    .from('terms')
    .select('title, content, updated_at')
    .eq('slug', slug)
    .single()

  if (!data) notFound()

  return (
    <div className="max-w-3xl mx-auto px-4 py-10">
      <h1 className="text-xl font-bold text-gray-900 mb-2">{data.title}</h1>
      <p className="text-xs text-gray-400 mb-6">
        최종 수정일: {new Date(data.updated_at).toLocaleDateString('ko-KR')}
      </p>
      <div
        className="prose prose-sm prose-gray max-w-none text-sm leading-relaxed text-gray-700 whitespace-pre-wrap"
        dangerouslySetInnerHTML={{ __html: data.content }}
      />
    </div>
  )
}
