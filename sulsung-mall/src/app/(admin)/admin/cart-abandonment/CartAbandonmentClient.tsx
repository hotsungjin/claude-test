'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/ko'
import { Send, CheckCircle } from 'lucide-react'

dayjs.extend(relativeTime)
dayjs.locale('ko')

interface AbandonedMember {
  memberId: string
  member: { id: string; name: string; phone: string; email: string }
  items: { id: string; name: string; price: number; sale_price: number | null; thumbnail_url: string | null; qty: number }[]
  latestAt: string
  alreadyReminded: boolean
}

export default function CartAbandonmentClient({ members }: { members: AbandonedMember[] }) {
  const router = useRouter()
  const [sending, setSending] = useState<Set<string>>(new Set())
  const [sent, setSent] = useState<Set<string>>(new Set())

  const sendReminder = async (m: AbandonedMember) => {
    if (sending.has(m.memberId)) return
    setSending(prev => new Set(prev).add(m.memberId))

    try {
      const res = await fetch('/api/v1/admin/cart-reminder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          memberId: m.memberId,
          memberName: m.member.name,
          phone: m.member.phone,
          goodsName: m.items[0]?.name ?? '상품',
          itemCount: m.items.length,
        }),
      })
      if (res.ok) {
        setSent(prev => new Set(prev).add(m.memberId))
      } else {
        alert('발송 실패')
      }
    } catch {
      alert('발송 실패')
    } finally {
      setSending(prev => {
        const next = new Set(prev)
        next.delete(m.memberId)
        return next
      })
    }
  }

  const sendAll = async () => {
    const targets = members.filter(m => !m.alreadyReminded && !sent.has(m.memberId) && m.member.phone)
    if (!confirm(`미발송 ${targets.length}명에게 리마인드를 보내시겠습니까?`)) return
    for (const m of targets) {
      await sendReminder(m)
    }
    router.refresh()
  }

  return (
    <div className="bg-white rounded-xl shadow-sm overflow-hidden">
      <div className="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
        <span className="text-sm text-gray-500">최근 7일 이탈 회원</span>
        <button onClick={sendAll}
          className="flex items-center gap-1.5 bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-blue-800">
          <Send className="w-3.5 h-3.5" /> 미발송 전체 발송
        </button>
      </div>

      <div className="divide-y divide-gray-50">
        {members.map(m => {
          const isReminded = m.alreadyReminded || sent.has(m.memberId)
          const totalValue = m.items.reduce((s, i) => s + (i.sale_price || i.price) * i.qty, 0)

          return (
            <div key={m.memberId} className="px-6 py-4 flex items-start gap-4">
              {/* 회원 정보 */}
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-medium text-gray-900">{m.member.name}</span>
                  <span className="text-xs text-gray-400">{m.member.phone}</span>
                  {isReminded && (
                    <span className="flex items-center gap-0.5 text-xs text-blue-600">
                      <CheckCircle className="w-3 h-3" /> 발송완료
                    </span>
                  )}
                </div>
                <p className="text-xs text-gray-400 mb-2">
                  마지막 담기: {dayjs(m.latestAt).fromNow()}
                </p>

                {/* 상품 목록 */}
                <div className="flex gap-2 overflow-x-auto">
                  {m.items.slice(0, 5).map((item, idx) => (
                    <div key={idx} className="flex-shrink-0 flex items-center gap-2 bg-gray-50 rounded-lg px-2 py-1.5">
                      {item.thumbnail_url ? (
                        <img src={item.thumbnail_url} alt="" className="w-8 h-8 rounded object-cover" />
                      ) : (
                        <div className="w-8 h-8 rounded bg-gray-200" />
                      )}
                      <div className="min-w-0">
                        <p className="text-xs text-gray-700 truncate max-w-[120px]">{item.name}</p>
                        <p className="text-xs text-gray-400">{item.qty}개</p>
                      </div>
                    </div>
                  ))}
                  {m.items.length > 5 && (
                    <span className="flex-shrink-0 text-xs text-gray-400 self-center">+{m.items.length - 5}건</span>
                  )}
                </div>
              </div>

              {/* 금액 + 발송 */}
              <div className="text-right flex-shrink-0">
                <p className="text-sm font-bold text-gray-900 mb-2">{totalValue.toLocaleString()}원</p>
                {!isReminded && m.member.phone ? (
                  <button onClick={() => sendReminder(m)} disabled={sending.has(m.memberId)}
                    className="flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-500 disabled:opacity-50">
                    <Send className="w-3 h-3" />
                    {sending.has(m.memberId) ? '발송중...' : '리마인드'}
                  </button>
                ) : !m.member.phone ? (
                  <span className="text-xs text-gray-400">번호 없음</span>
                ) : null}
              </div>
            </div>
          )
        })}

        {members.length === 0 && (
          <div className="px-6 py-12 text-center text-gray-400">
            이탈 장바구니가 없습니다
          </div>
        )}
      </div>
    </div>
  )
}
