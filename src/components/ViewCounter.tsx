'use client'

import { useEffect } from 'react'

export function ViewCounter({ articleId }: { articleId: string | number }) {
  useEffect(() => {
    fetch(`/api/view/${articleId}`, { method: 'POST' }).catch(() => {})
  }, [articleId])

  return null
}
