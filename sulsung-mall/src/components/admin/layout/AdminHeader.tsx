'use client'

import { Bell, LogOut } from 'lucide-react'

export default function AdminHeader() {
  return (
    <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
      <div />
      <div className="flex items-center gap-3">
        <button className="relative p-2 hover:bg-gray-100 rounded-lg">
          <Bell className="w-5 h-5 text-gray-500" />
        </button>
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
            관
          </div>
          <span className="text-sm font-medium text-gray-700">관리자</span>
        </div>
        <button className="p-2 hover:bg-gray-100 rounded-lg">
          <LogOut className="w-4 h-4 text-gray-500" />
        </button>
      </div>
    </header>
  )
}
