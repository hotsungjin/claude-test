import { SITE_NAME, BASE_SHIPPING_FEE, FREE_SHIPPING_THRESHOLD } from '@/constants'
import { formatPrice } from '@/utils/format'

export default function AdminSettingsPage() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">쇼핑몰 설정</h1>

      <div className="max-w-2xl space-y-6">
        {/* 기본 정보 */}
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h2 className="font-semibold text-gray-800 mb-4">기본 정보</h2>
          <div className="space-y-3 text-sm">
            <div className="flex justify-between py-2 border-b border-gray-50">
              <span className="text-gray-500">쇼핑몰명</span>
              <span className="font-medium text-gray-800">{SITE_NAME}</span>
            </div>
            <div className="flex justify-between py-2 border-b border-gray-50">
              <span className="text-gray-500">기본 배송비</span>
              <span className="font-medium text-gray-800">{formatPrice(BASE_SHIPPING_FEE)}</span>
            </div>
            <div className="flex justify-between py-2">
              <span className="text-gray-500">무료배송 기준</span>
              <span className="font-medium text-gray-800">{formatPrice(FREE_SHIPPING_THRESHOLD)} 이상</span>
            </div>
          </div>
        </div>

        <div className="bg-blue-50 rounded-xl p-5 text-sm text-blue-700">
          <p className="font-medium mb-1">설정 변경 안내</p>
          <p>쇼핑몰 기본 설정은 <code className="bg-blue-100 px-1 rounded">src/constants.ts</code> 파일을 직접 수정하거나, Supabase의 <code className="bg-blue-100 px-1 rounded">site_settings</code> 테이블을 통해 변경할 수 있습니다.</p>
        </div>
      </div>
    </div>
  )
}
