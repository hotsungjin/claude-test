'use client'

import { useRouter } from 'next/navigation'
import { ChevronLeft } from 'lucide-react'

export default function MypageHeader({ title }: { title: string }) {
  const router = useRouter()

  return (
    <div className="sticky top-0 z-40 bg-white flex items-center h-[48px] px-4">
      <button onClick={() => router.back()} className="mr-3 -ml-1">
        <ChevronLeft className="w-[24px] h-[24px]" strokeWidth={2.2} style={{ color: '#222' }} />
      </button>
      <h1 className="text-[20px] font-bold" style={{ color: '#222' }}>{title}</h1>
    </div>
  )
}
