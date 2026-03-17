export default function StoreLoading() {
  return (
    <div className="bg-white animate-pulse">
      {/* 배너 스켈레톤 */}
      <div className="w-full aspect-[2/1] bg-gray-200" />

      {/* 카테고리 스켈레톤 */}
      <div className="px-3 py-5">
        <div className="grid grid-cols-5 gap-x-1 gap-y-4">
          {Array.from({ length: 10 }).map((_, i) => (
            <div key={i} className="flex flex-col items-center gap-2">
              <div className="w-[60px] h-[60px] rounded-full bg-gray-200" />
              <div className="w-10 h-3 rounded bg-gray-200" />
            </div>
          ))}
        </div>
      </div>

      {/* 섹션 스켈레톤 */}
      {[1, 2].map(s => (
        <div key={s} style={{ paddingTop: '22px' }}>
          <div className="px-4 pb-3">
            <div className="w-48 h-5 rounded bg-gray-200 mb-1" />
            <div className="w-64 h-3.5 rounded bg-gray-100" />
          </div>
          <div className="flex gap-2.5 px-4 pb-6 overflow-hidden">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="flex-shrink-0" style={{ width: '140px' }}>
                <div className="w-[140px] h-[140px] rounded-lg bg-gray-200 mb-2" />
                <div className="w-24 h-3 rounded bg-gray-200 mb-1" />
                <div className="w-16 h-4 rounded bg-gray-200" />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  )
}
