import Link from 'next/link'

const FOOTER_LINKS = [
  { href: '/policy/business', label: '사업자 정보' },
  { href: '/policy/privacy', label: '개인정보처리방침', bold: true },
  { href: '/policy/terms', label: '이용약관' },
  { href: '/notice', label: '공지사항' },
  { href: '/cs', label: '고객센터' },
  { href: '/partner', label: '입점 문의' },
]

export default function Footer() {
  return (
    <footer data-store-footer style={{ backgroundColor: '#f5f5f5' }}>
      {/* 링크 */}
      <div className="flex flex-wrap gap-x-5 gap-y-1 px-4 pt-6 pb-4">
        {FOOTER_LINKS.map(item => (
          <Link
            key={item.href}
            href={item.href}
            className={`text-[13px] ${item.bold ? 'font-bold' : 'font-normal'}`}
            style={{ color: item.bold ? '#333' : '#555' }}
          >
            {item.label}
          </Link>
        ))}
      </div>

      {/* 사업자 정보 */}
      <div className="px-4" style={{ paddingBottom: '20px' }}>
        <p className="text-[12px] leading-[1.9]" style={{ color: '#888' }}>
          설성목장 대표이사 : 대표자명<br />
          경기도 이천시 설성면<br />
          사업자등록번호 : 000-00-00000<br />
          대표전화번호: 1588-0000<br />
          대표이메일: cs@sulsungmall.com<br />
          통신판매업신고 : 제 0000-경기이천-0000<br />
          개인정보보호책임자 : 대표자명
        </p>
      </div>
    </footer>
  )
}
