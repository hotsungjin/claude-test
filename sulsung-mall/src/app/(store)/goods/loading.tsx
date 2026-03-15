export default function GoodsLoading() {
  return (
    <div className="flex items-center justify-center" style={{ height: '60vh' }}>
      <div
        className="w-6 h-6 rounded-full border-2 border-t-transparent animate-spin"
        style={{ borderColor: '#968774', borderTopColor: 'transparent' }}
      />
    </div>
  )
}
