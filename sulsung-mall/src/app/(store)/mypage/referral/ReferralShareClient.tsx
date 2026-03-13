'use client'

import { useState } from 'react'
import { Copy, Share2 } from 'lucide-react'

export default function ReferralShareClient({ url, code }: { url: string; code: string }) {
  const [copied, setCopied] = useState(false)

  const copy = async () => {
    await navigator.clipboard.writeText(url)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const share = async () => {
    if (navigator.share) {
      await navigator.share({ title: '설성목장몰 가입 추천', text: `추천 코드 [${code}]로 설성목장몰에 가입하면 할인 혜택을 받을 수 있어요!`, url })
    } else {
      copy()
    }
  }

  return (
    <div className="flex gap-2">
      <button onClick={copy}
        className="flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm px-3 py-2 rounded-lg transition-colors">
        <Copy className="w-3.5 h-3.5" />
        {copied ? '복사됨!' : '링크 복사'}
      </button>
      <button onClick={share}
        className="flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm px-3 py-2 rounded-lg transition-colors">
        <Share2 className="w-3.5 h-3.5" />
        공유
      </button>
    </div>
  )
}
