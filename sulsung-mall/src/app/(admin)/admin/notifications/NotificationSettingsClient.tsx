'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

interface Props {
  cartReminder: { enabled: boolean; hours: number; max_per_day: number }
  recentLogs: any[]
}

const TYPE_LABEL: Record<string, string> = {
  cart_reminder: '장바구니 리마인드',
  order_confirm: '주문 확인',
  shipping_start: '배송 시작',
  cancel_complete: '취소 완료',
}

const CHANNEL_LABEL: Record<string, string> = {
  sms: 'SMS',
  alimtalk: '알림톡',
  email: '이메일',
}

export default function NotificationSettingsClient({ cartReminder, recentLogs }: Props) {
  const router = useRouter()
  const [settings, setSettings] = useState(cartReminder)
  const [saving, setSaving] = useState(false)

  const save = async () => {
    setSaving(true)
    await fetch('/api/v1/admin/settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key: 'cart_reminder', value: settings }),
    })
    setSaving(false)
    router.refresh()
  }

  return (
    <div className="space-y-6">
      {/* 장바구니 리마인드 설정 */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h2 className="font-semibold text-gray-900 mb-4">장바구니 이탈 리마인드</h2>
        <div className="space-y-4">
          <label className="flex items-center gap-3">
            <input
              type="checkbox"
              checked={settings.enabled}
              onChange={e => setSettings({ ...settings, enabled: e.target.checked })}
              className="w-4 h-4 accent-green-600"
            />
            <span className="text-sm text-gray-700">자동 리마인드 활성화</span>
          </label>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs text-gray-500 mb-1">발송 시점 (장바구니 담은 후)</label>
              <select
                value={settings.hours}
                onChange={e => setSettings({ ...settings, hours: Number(e.target.value) })}
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
              >
                <option value={6}>6시간 후</option>
                <option value={12}>12시간 후</option>
                <option value={24}>24시간 후</option>
                <option value={48}>48시간 후</option>
              </select>
            </div>
            <div>
              <label className="block text-xs text-gray-500 mb-1">일 최대 발송 건수</label>
              <input
                type="number"
                value={settings.max_per_day}
                onChange={e => setSettings({ ...settings, max_per_day: Number(e.target.value) })}
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                min={1}
                max={1000}
              />
            </div>
          </div>

          <div className="bg-gray-50 rounded-lg p-3 text-xs text-gray-500">
            Cron 엔드포인트: <code className="bg-gray-200 px-1.5 py-0.5 rounded">/api/v1/cron/cart-reminder</code>
            <br />
            CRON_SECRET 환경변수를 설정하고, 외부 스케줄러(Vercel Cron 등)에서 주기적으로 호출하세요.
          </div>

          <button
            onClick={save}
            disabled={saving}
            className="px-4 py-2 bg-green-700 text-white text-sm rounded-lg hover:bg-green-800 disabled:opacity-50"
          >
            {saving ? '저장 중...' : '설정 저장'}
          </button>
        </div>
      </div>

      {/* 최근 알림 발송 이력 */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-900">최근 알림 발송 이력</h2>
        </div>
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">발송일시</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">유형</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">채널</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">수신자</th>
              <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">내용</th>
              <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium">상태</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {recentLogs.map((log: any) => (
              <tr key={log.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-xs text-gray-500">
                  {log.sent_at ? new Date(log.sent_at).toLocaleString('ko-KR') : '-'}
                </td>
                <td className="px-4 py-3 text-xs">{TYPE_LABEL[log.type] ?? log.type}</td>
                <td className="px-4 py-3 text-xs">{CHANNEL_LABEL[log.channel] ?? log.channel}</td>
                <td className="px-4 py-3 text-xs text-gray-600">{log.receiver}</td>
                <td className="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{log.message}</td>
                <td className="px-4 py-3 text-center">
                  <span className={`text-xs px-2 py-0.5 rounded-full ${
                    log.status === 'sent' ? 'bg-green-100 text-green-700' :
                    log.status === 'failed' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {log.status === 'sent' ? '성공' : log.status === 'failed' ? '실패' : log.status}
                  </span>
                </td>
              </tr>
            ))}
            {recentLogs.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-gray-400 text-sm">발송 이력이 없습니다.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
