'use client'

import Link from 'next/link'
import { SITE_NAME } from '@/constants'

const KEYWORDS = [
  { label: '청정 자연', highlight: '설성목장' },
  { label: '산지 직송', highlight: '당일 발송' },
  { label: '신선한', highlight: '한우·한돈' },
  { label: '정성 가득', highlight: '유제품·간편식' },
  { label: '특별한', highlight: '선물세트' },
]

export default function PcBrandingSidebar() {
  return (
    <aside className="sticky top-0 hidden h-screen w-[360px] flex-col items-center justify-center gap-[80px] md:flex">
      <div className="w-full">
        {/* 슬로건 */}
        <p className="py-[24px] text-[34px] font-bold leading-tight" style={{ color: '#333' }}>
          설성목장의 신선함으로<br />
          고객의 식탁까지
        </p>
        <p className="text-[16px] leading-[150%] tracking-[-0.032em]" style={{ color: '#777' }}>
          자연이 키우고 설성목장이 직접 전합니다.<br />
          가장 신선한 한우, 한돈, 가정간편식을 경험하세요!
        </p>

        {/* 키워드 태그 */}
        <div className="flex flex-wrap gap-[12px] pt-[40px]">
          {KEYWORDS.map(kw => (
            <span
              key={kw.highlight}
              className="flex h-[43px] items-center gap-[4px] rounded-full border border-gray-200 bg-white px-[18px] text-[16px] font-medium"
            >
              {kw.label}{' '}
              <span style={{ color: '#968774' }}>{kw.highlight}</span>
            </span>
          ))}
        </div>
      </div>

      {/* 하단 안내 */}
      <div className="flex w-full items-center gap-[12px] rounded-xl bg-white p-[16px]" style={{ border: '1px solid #f0ece8' }}>
        <div
          className="flex h-[72px] w-[72px] flex-shrink-0 items-center justify-center rounded-lg text-[32px]"
          style={{ backgroundColor: '#f7f4f1' }}
        >
          🐄
        </div>
        <div>
          <p className="text-[15px] font-bold" style={{ color: '#333' }}>
            설성목장몰에서<br />
            더 많은 혜택을 받아보세요
          </p>
          <p className="text-[13px] mt-[4px]" style={{ color: '#999' }}>
            회원가입 시 1,000P 즉시 지급
          </p>
        </div>
      </div>
    </aside>
  )
}
