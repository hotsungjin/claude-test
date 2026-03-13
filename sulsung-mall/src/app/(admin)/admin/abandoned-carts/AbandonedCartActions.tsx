'use client'

import { useState } from 'react'

interface Props {
  memberId: string
  memberName: string
  phone?: string
  goodsName: string
  itemCount: number
}

export default function AbandonedCartActions({ memberId, memberName, phone, goodsName, itemCount }: Props) {
  const [sending, setSending] = useState(false)
  const [sent, setSent] = useState(false)

  const sendReminder = async () => {
    if (!phone) {
      alert('연락처가 등록되지 않은 회원입니다.')
      return
    }
    if (!confirm(`${memberName}님에게 장바구니 리마인드를 발송하시겠습니까?`)) return

    setSending(true)
    try {
      const res = await fetch('/api/v1/admin/cart-reminder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ memberId, memberName, phone, goodsName, itemCount }),
      })
      if (res.ok) {
        setSent(true)
      } else {
        const data = await res.json()
        alert(data.error ?? '발송 실패')
      }
    } catch {
      alert('발송 중 오류가 발생했습니다.')
    }
    setSending(false)
  }

  if (sent) {
    return <span className="text-xs text-green-600 font-medium">발송 완료</span>
  }

  return (
    <button
      onClick={sendReminder}
      disabled={sending || !phone}
      className="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-500 disabled:opacity-50"
    >
      {sending ? '발송중...' : '리마인드'}
    </button>
  )
}
