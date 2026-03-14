import Link from 'next/link'

interface AdBannerProps {
  banner: {
    id: number
    image_url: string
    link_url?: string | null
    alt?: string | null
  }
}

export default function AdBanner({ banner }: AdBannerProps) {
  const img = (
    <img
      src={banner.image_url}
      alt={banner.alt ?? '광고 배너'}
      className="w-full h-auto object-cover"
      loading="lazy"
    />
  )

  if (banner.link_url) {
    return (
      <section>
        <Link href={banner.link_url}>{img}</Link>
      </section>
    )
  }

  return <section>{img}</section>
}
