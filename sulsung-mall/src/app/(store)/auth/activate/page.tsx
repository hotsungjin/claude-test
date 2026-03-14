import { Suspense } from 'react'
import ActivateClient from './ActivateClient'

export default function ActivatePage() {
  return (
    <Suspense>
      <ActivateClient />
    </Suspense>
  )
}
