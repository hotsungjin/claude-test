import { createClient } from '@/lib/supabase/server'
import NotificationSettingsClient from './NotificationSettingsClient'

export default async function AdminNotificationsPage() {
  const supabase = await createClient() as any

  // 장바구니 리마인드 설정
  const { data: cartConfig } = await supabase
    .from('site_configs')
    .select('value')
    .eq('key', 'cart_reminder')
    .single()

  const cartReminder = (cartConfig?.value as any) ?? { enabled: true, hours: 24, max_per_day: 100 }

  // 최근 알림 발송 이력
  const { data: recentLogs } = await supabase
    .from('notification_logs')
    .select('id, member_id, type, channel, receiver, message, status, sent_at')
    .order('sent_at', { ascending: false })
    .limit(30)

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">알림 설정</h1>
      <NotificationSettingsClient
        cartReminder={cartReminder}
        recentLogs={recentLogs ?? []}
      />
    </div>
  )
}
