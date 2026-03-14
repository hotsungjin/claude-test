import { createClient } from '@/lib/supabase/server'
import { formatDate } from '@/utils/format'
import MypageHeader from '@/components/store/mypage/MypageHeader'

export default async function MileagePage() {
  const supabase = await createClient() as any
  const { data: { user } } = await supabase.auth.getUser()
  const { data: member } = await supabase.from('members').select('id, mileage').eq('auth_id', user.id).single()

  const { data: logs } = await supabase
    .from('mileage_logs')
    .select('id, delta, balance, reason, created_at')
    .eq('member_id', member.id)
    .order('created_at', { ascending: false })
    .limit(50)

  return (
    <div style={{ backgroundColor: '#fff', minHeight: '100vh' }}>
      <MypageHeader title="포인트" />

      {/* 적립금 카드 */}
      <div className="mx-4 mt-4 rounded-xl overflow-hidden" style={{ backgroundColor: '#faf8f5' }}>
        <div className="px-5 pt-5 pb-4">
          <p className="text-[13px]" style={{ color: '#888' }}>포인트</p>
          <p className="text-[28px] font-bold mt-1" style={{ color: '#222' }}>
            {(member?.mileage ?? 0).toLocaleString()}<span className="text-[18px]">원</span>
          </p>
        </div>
        <div className="mx-5 border-t" style={{ borderColor: '#e8e4df' }} />
        <div className="flex items-center justify-between px-5 py-3.5">
          <span className="text-[13px]" style={{ color: '#888' }}>7일 내 소멸 예정</span>
          <span className="text-[13px] font-medium" style={{ color: '#222' }}>0원</span>
        </div>
      </div>

      {/* 안내 배너 */}
      <div className="mx-4 mt-3 rounded-xl px-4 py-3.5 flex items-center gap-2" style={{ backgroundColor: '#fef9ec' }}>
        <span className="text-[14px]">🪙</span>
        <span className="text-[13px]" style={{ color: '#8a7a5a' }}>상품 구매 시 1% 적립, 자동 충전</span>
      </div>

      {/* 기간 필터 */}
      <div className="flex gap-2 px-4 mt-5">
        {['1개월', '3개월', '6개월'].map((label, i) => (
          <button key={label}
            className="px-4 py-2 rounded-full text-[13px] font-medium"
            style={i === 0
              ? { backgroundColor: '#222', color: '#fff' }
              : { backgroundColor: '#fff', color: '#666', border: '1px solid #ddd' }
            }>
            {label}
          </button>
        ))}
      </div>

      {/* 전체 필터 */}
      <div className="flex justify-end px-4 mt-4 mb-2">
        <span className="text-[13px]" style={{ color: '#888' }}>전체 ▾</span>
      </div>

      {/* 내역 */}
      {(!logs || logs.length === 0) ? (
        <div className="flex flex-col items-center justify-center py-20">
          <div className="w-14 h-14 rounded-full flex items-center justify-center mb-4" style={{ backgroundColor: '#f0f0f0' }}>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ccc" strokeWidth="2" strokeLinecap="round">
              <line x1="4" y1="6" x2="20" y2="6" /><line x1="4" y1="12" x2="20" y2="12" /><line x1="4" y1="18" x2="14" y2="18" />
            </svg>
          </div>
          <p className="text-[14px] font-medium" style={{ color: '#999' }}>적립 내역이 없어요</p>
        </div>
      ) : (
        <div className="divide-y" style={{ borderColor: '#f5f5f5' }}>
          {logs.map((log: any) => (
            <div key={log.id} className="flex items-center justify-between px-4 py-4">
              <div>
                <p className="text-[14px] font-medium" style={{ color: '#333' }}>{log.reason}</p>
                <p className="text-[12px] mt-1" style={{ color: '#aaa' }}>{formatDate(log.created_at)}</p>
              </div>
              <div className="text-right">
                <p className="text-[14px] font-bold"
                  style={{ color: log.delta > 0 ? '#968774' : '#e53e3e' }}>
                  {log.delta > 0 ? '+' : ''}{log.delta.toLocaleString()}원
                </p>
                <p className="text-[12px] mt-0.5" style={{ color: '#aaa' }}>잔액 {log.balance.toLocaleString()}원</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
