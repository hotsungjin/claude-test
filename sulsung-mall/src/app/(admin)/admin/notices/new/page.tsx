import NoticeFormClient from '../NoticeFormClient'

export default function AdminNoticeNewPage() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">공지사항 등록</h1>
      <NoticeFormClient />
    </div>
  )
}
