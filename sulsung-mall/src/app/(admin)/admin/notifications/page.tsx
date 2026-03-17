import { createClient } from '@/lib/supabase/server'
import NotificationPageClient from './NotificationPageClient'

export default async function AdminNotificationsPage() {
  const supabase = await createClient() as any

  // 장바구니 리마인드 설정
  const { data: cartConfig } = await supabase
    .from('site_configs')
    .select('value')
    .eq('key', 'cart_reminder')
    .single()

  const cartReminder = (cartConfig?.value as any) ?? { enabled: true, hours: 24, max_per_day: 100 }

  // 최근 알림 발송 이력 (50건)
  const { data: recentLogs } = await supabase
    .from('notification_logs')
    .select('id, member_id, type, channel, receiver, message, status, error_msg, sent_at')
    .order('sent_at', { ascending: false })
    .limit(50)

  // 전체 회원 수 (발송 대상 표시용)
  const { count: memberCount } = await supabase
    .from('members')
    .select('id', { count: 'exact', head: true })
    .eq('status', 'active')

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">메시지 / 알림 관리</h1>
      <NotificationPageClient
        cartReminder={cartReminder}
        recentLogs={recentLogs ?? []}
        memberCount={memberCount ?? 0}
      />
    </div>
  )
}
