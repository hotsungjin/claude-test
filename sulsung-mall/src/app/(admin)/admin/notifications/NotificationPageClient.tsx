'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

interface Props {
  cartReminder: { enabled: boolean; hours: number; max_per_day: number }
  recentLogs: any[]
  memberCount: number
}

const TYPE_LABEL: Record<string, string> = {
  admin_manual: '관리자 수동 발송',
  cart_reminder: '장바구니 리마인드',
  order_confirm: '주문 확인',
  shipping_start: '배송 시작',
  cancel_complete: '취소 완료',
  dormant_warning: '휴면 예고',
  dormant_converted: '휴면 전환',
  bulk_email: '대량 이메일',
}

const CHANNEL_LABEL: Record<string, string> = {
  kakao: '알림톡',
  sms: 'SMS',
  email: '이메일',
  system: '시스템',
}

// 자동 발송 알림 유형 정의
const AUTO_NOTIFICATIONS = [
  {
    type: '주문 확인',
    trigger: '고객이 결제 완료 시',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '#{주문번호} 주문이 완료되었습니다. 상품명: #{상품명}, 결제금액: #{결제금액}',
    templateId: 'ORDER_CONFIRM',
  },
  {
    type: '배송 시작',
    trigger: '관리자가 배송 처리 시',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '#{주문번호} 주문이 발송되었습니다. 택배사: #{택배사}, 운송장번호: #{운송장번호}',
    templateId: 'SHIPPING_START',
  },
  {
    type: '주문 취소',
    trigger: '주문 취소/환불 완료 시',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '#{주문번호} 주문이 취소되었습니다. 환불금액: #{환불금액}',
    templateId: 'CANCEL_COMPLETE',
  },
  {
    type: '장바구니 리마인드',
    trigger: '장바구니 담은 후 N시간 경과 (크론)',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '#{고객명}님, 장바구니에 담아두신 \'#{상품명}\' #{상품수}이 기다리고 있어요!',
    templateId: 'CART_REMINDER',
  },
  {
    type: '휴면 예고',
    trigger: '마지막 로그인 80일 경과 (크론)',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '#{고객명}님, 10일 후 장기 미접속으로 계정이 휴면 처리됩니다.',
    templateId: 'DORMANT_WARNING',
  },
  {
    type: '휴면 전환',
    trigger: '마지막 로그인 90일 경과 (크론)',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '#{고객명}님, 90일간 미접속으로 계정이 휴면 처리되었습니다.',
    templateId: 'DORMANT_CONVERTED',
  },
  {
    type: '인증번호',
    trigger: '회원가입 / 비밀번호 재설정 시',
    channel: '카카오 알림톡 (실패 시 SMS)',
    message: '인증번호 #{인증번호}를 입력해주세요.',
    templateId: 'VERIFY_CODE',
  },
]

export default function NotificationPageClient({ cartReminder, recentLogs, memberCount }: Props) {
  const router = useRouter()
  const [activeTab, setActiveTab] = useState<'send' | 'auto' | 'history' | 'settings'>('send')

  // 발송 폼
  const [target, setTarget] = useState<'all' | 'single'>('single')
  const [phone, setPhone] = useState('')
  const [message, setMessage] = useState('')
  const [channel, setChannel] = useState<'alimtalk_sms' | 'sms_only'>('alimtalk_sms')
  const [sending, setSending] = useState(false)
  const [sendResult, setSendResult] = useState<any>(null)

  // 장바구니 설정
  const [settings, setSettings] = useState(cartReminder)
  const [saving, setSaving] = useState(false)

  function formatPhone(value: string) {
    const nums = value.replace(/[^0-9]/g, '').slice(0, 11)
    if (nums.length <= 3) return nums
    if (nums.length <= 7) return `${nums.slice(0, 3)}-${nums.slice(3)}`
    return `${nums.slice(0, 3)}-${nums.slice(3, 7)}-${nums.slice(7)}`
  }

  async function handleSend() {
    if (!message.trim()) return alert('메시지를 입력해주세요.')
    if (target === 'single' && phone.replace(/[^0-9]/g, '').length < 10) return alert('전화번호를 입력해주세요.')
    if (target === 'all' && !confirm(`전체 회원 ${memberCount}명에게 발송합니다. 진행하시겠습니까?`)) return

    setSending(true)
    setSendResult(null)
    const res = await fetch('/api/v1/admin/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        target,
        phone: target === 'single' ? phone.replace(/[^0-9]/g, '') : undefined,
        message,
        channel,
      }),
    })
    const data = await res.json()
    setSending(false)
    setSendResult(data)
    if (data.success) router.refresh()
  }

  async function saveSettings() {
    setSaving(true)
    await fetch('/api/v1/admin/settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key: 'cart_reminder', value: settings }),
    })
    setSaving(false)
    router.refresh()
  }

  const tabs = [
    { key: 'send', label: '메시지 발송' },
    { key: 'auto', label: '자동 알림 현황' },
    { key: 'history', label: '발송 이력' },
    { key: 'settings', label: '설정' },
  ] as const

  return (
    <div className="space-y-6">
      {/* 탭 */}
      <div className="flex gap-1 bg-gray-100 rounded-lg p-1">
        {tabs.map(tab => (
          <button
            key={tab.key}
            onClick={() => setActiveTab(tab.key)}
            className={`flex-1 px-4 py-2 text-sm font-medium rounded-md transition ${
              activeTab === tab.key
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* ── 메시지 발송 ── */}
      {activeTab === 'send' && (
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h2 className="font-semibold text-gray-900 mb-4">메시지 발송</h2>

          <div className="space-y-4">
            {/* 발송 채널 */}
            <div>
              <label className="block text-xs text-gray-500 mb-2">발송 채널</label>
              <div className="flex gap-3">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="channel" value="alimtalk_sms"
                    checked={channel === 'alimtalk_sms'}
                    onChange={() => setChannel('alimtalk_sms')}
                    className="accent-blue-600" />
                  <span className="text-sm text-gray-700">카카오 알림톡 (미수신 시 SMS 전환)</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="channel" value="sms_only"
                    checked={channel === 'sms_only'}
                    onChange={() => setChannel('sms_only')}
                    className="accent-blue-600" />
                  <span className="text-sm text-gray-700">SMS / LMS</span>
                </label>
              </div>
              {channel === 'alimtalk_sms' && !process.env.NEXT_PUBLIC_SOLAPI_PFID && (
                <p className="text-xs text-amber-600 mt-1">
                  * 카카오채널 PFID 미설정 시 SMS로 자동 전환됩니다.
                </p>
              )}
            </div>

            {/* 발송 대상 */}
            <div>
              <label className="block text-xs text-gray-500 mb-2">발송 대상</label>
              <div className="flex gap-3">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="target" value="single"
                    checked={target === 'single'}
                    onChange={() => setTarget('single')}
                    className="accent-blue-600" />
                  <span className="text-sm text-gray-700">개별 발송</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="target" value="all"
                    checked={target === 'all'}
                    onChange={() => setTarget('all')}
                    className="accent-blue-600" />
                  <span className="text-sm text-gray-700">전체 회원 ({memberCount}명)</span>
                </label>
              </div>
            </div>

            {/* 전화번호 (개별) */}
            {target === 'single' && (
              <div>
                <label className="block text-xs text-gray-500 mb-1">수신 전화번호</label>
                <input
                  type="tel"
                  value={phone}
                  onChange={e => setPhone(formatPhone(e.target.value))}
                  placeholder="010-0000-0000"
                  className="w-full max-w-xs border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                />
              </div>
            )}

            {/* 메시지 내용 */}
            <div>
              <label className="block text-xs text-gray-500 mb-1">
                메시지 내용
                <span className="ml-2 text-gray-400">
                  {message.length}자 {message.length > 90 ? '(LMS)' : '(SMS)'}
                </span>
              </label>
              <textarea
                value={message}
                onChange={e => setMessage(e.target.value)}
                placeholder="발송할 메시지를 입력하세요"
                rows={4}
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400 resize-none"
              />
              <p className="text-xs text-gray-400 mt-1">
                SMS: 90자 이내 / LMS: 2,000자 이내. 90자 초과 시 자동으로 LMS로 발송됩니다.
              </p>
            </div>

            {/* 발송 버튼 */}
            <button
              onClick={handleSend}
              disabled={sending || !message.trim()}
              className="px-6 py-2.5 bg-blue-700 text-white text-sm font-medium rounded-lg hover:bg-blue-800 disabled:opacity-50"
            >
              {sending ? '발송 중...' : target === 'all' ? `전체 발송 (${memberCount}명)` : '발송하기'}
            </button>

            {/* 발송 결과 */}
            {sendResult && (
              <div className={`rounded-lg p-4 text-sm ${
                sendResult.success ? 'bg-blue-50 text-blue-800' : 'bg-red-50 text-red-700'
              }`}>
                {sendResult.success ? (
                  <>
                    <p className="font-medium">발송 완료</p>
                    <p>전체 {sendResult.total}건 중 성공 {sendResult.successCount}건, 실패 {sendResult.failCount}건</p>
                    {sendResult.errors?.length > 0 && (
                      <ul className="mt-2 text-xs space-y-0.5">
                        {sendResult.errors.map((e: string, i: number) => <li key={i}>{e}</li>)}
                      </ul>
                    )}
                  </>
                ) : (
                  <p>{sendResult.error}</p>
                )}
              </div>
            )}
          </div>
        </div>
      )}

      {/* ── 자동 알림 현황 ── */}
      {activeTab === 'auto' && (
        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-100">
            <h2 className="font-semibold text-gray-900">자동 알림 현황</h2>
            <p className="text-xs text-gray-500 mt-1">각 상황별로 자동 발송되는 메시지 목록입니다.</p>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-28">알림 유형</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-48">발송 시점</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-40">채널</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">메시지 내용</th>
                <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium w-32">템플릿 ID</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {AUTO_NOTIFICATIONS.map((n, i) => (
                <tr key={i} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <span className="inline-block text-xs font-medium px-2 py-1 rounded-full bg-blue-50 text-blue-700">
                      {n.type}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-700">{n.trigger}</td>
                  <td className="px-4 py-3">
                    <span className={`text-xs px-2 py-0.5 rounded-full ${
                      n.channel.includes('알림톡')
                        ? 'bg-yellow-50 text-yellow-700'
                        : n.channel.includes('이메일')
                          ? 'bg-purple-50 text-purple-700'
                          : 'bg-green-50 text-green-700'
                    }`}>
                      {n.channel}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-600">{n.message}</td>
                  <td className="px-4 py-3 text-xs text-gray-400 font-mono">{n.templateId}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* ── 발송 이력 ── */}
      {activeTab === 'history' && (
        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-100">
            <h2 className="font-semibold text-gray-900">최근 발송 이력</h2>
            <p className="text-xs text-gray-500 mt-1">최근 50건의 알림 발송 기록입니다.</p>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium whitespace-nowrap">발송일시</th>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium whitespace-nowrap">유형</th>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium whitespace-nowrap">채널</th>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium whitespace-nowrap">수신자</th>
                  <th className="px-4 py-3 text-left text-xs text-gray-500 font-medium">내용</th>
                  <th className="px-4 py-3 text-center text-xs text-gray-500 font-medium whitespace-nowrap">상태</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {recentLogs.map((log: any) => (
                  <tr key={log.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                      {log.sent_at ? new Date(log.sent_at).toLocaleString('ko-KR') : '-'}
                    </td>
                    <td className="px-4 py-3 text-xs whitespace-nowrap">
                      <span className="inline-block px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                        {TYPE_LABEL[log.type] ?? log.type}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-xs whitespace-nowrap">
                      <span className={`px-2 py-0.5 rounded-full ${
                        log.channel === 'kakao' ? 'bg-yellow-50 text-yellow-700' :
                        log.channel === 'sms' ? 'bg-green-50 text-green-700' :
                        log.channel === 'email' ? 'bg-purple-50 text-purple-700' :
                        'bg-gray-50 text-gray-500'
                      }`}>
                        {CHANNEL_LABEL[log.channel] ?? log.channel}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{log.receiver}</td>
                    <td className="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">{log.message}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`text-xs px-2 py-0.5 rounded-full ${
                        log.status === 'sent' ? 'bg-blue-100 text-blue-700' :
                        log.status === 'failed' ? 'bg-red-100 text-red-600' :
                        'bg-gray-100 text-gray-500'
                      }`}>
                        {log.status === 'sent' ? '성공' : log.status === 'failed' ? '실패' : log.status}
                      </span>
                      {log.status === 'failed' && log.error_msg && (
                        <p className="text-[10px] text-red-400 mt-0.5">{log.error_msg}</p>
                      )}
                    </td>
                  </tr>
                ))}
                {recentLogs.length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-4 py-12 text-center text-gray-400 text-sm">
                      발송 이력이 없습니다.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ── 설정 ── */}
      {activeTab === 'settings' && (
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h2 className="font-semibold text-gray-900 mb-4">장바구니 이탈 리마인드 설정</h2>
          <div className="space-y-4">
            <label className="flex items-center gap-3">
              <input
                type="checkbox"
                checked={settings.enabled}
                onChange={e => setSettings({ ...settings, enabled: e.target.checked })}
                className="w-4 h-4 accent-blue-600"
              />
              <span className="text-sm text-gray-700">자동 리마인드 활성화</span>
            </label>

            <div className="grid grid-cols-2 gap-4 max-w-md">
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
              Vercel Cron이 매일 10시에 자동 호출합니다.
            </div>

            <button
              onClick={saveSettings}
              disabled={saving}
              className="px-4 py-2 bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-800 disabled:opacity-50"
            >
              {saving ? '저장 중...' : '설정 저장'}
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
