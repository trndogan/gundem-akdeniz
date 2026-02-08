import { getPayload } from 'payload'
import config from '../../../../payload.config'
import { NextResponse } from 'next/server'

export const POST = async (_req: Request, { params }: { params: Promise<{ id: string }> }) => {
  const { id } = await params
  const payload = await getPayload({ config })

  try {
    const article = await payload.findByID({ collection: 'articles', id: Number(id), depth: 0 })
    if (!article) {
      return NextResponse.json({ error: 'Not found' }, { status: 404 })
    }

    const currentCount = (article.viewCount as number) || 0
    await payload.update({
      collection: 'articles',
      id: Number(id),
      data: { viewCount: currentCount + 1 },
    })

    return NextResponse.json({ success: true, viewCount: currentCount + 1 })
  } catch (err) {
    return NextResponse.json({ error: String(err) }, { status: 500 })
  }
}
