import { createClient } from '@/lib/supabase/server'
import FaqClient from './FaqClient'
import MypageHeader from '@/components/store/mypage/MypageHeader'

export default async function FaqPage() {
  const supabase = await createClient() as any

  const { data: faqs } = await supabase
    .from('faqs')
    .select('id, category, question, answer')
    .eq('is_active', true)
    .order('sort_order')
    .order('created_at')

  // 카테고리별 그룹화
  const categories: Record<string, { id: number; category: string; question: string; answer: string }[]> = {}
  for (const faq of (faqs ?? [])) {
    const cat = faq.category ?? '일반'
    if (!categories[cat]) categories[cat] = []
    categories[cat].push(faq)
  }

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="자주 묻는 질문" />
      <div className="px-4 pt-4">
        <FaqClient categories={categories} />
      </div>
    </div>
  )
}
