import { NextRequest, NextResponse } from 'next/server'
import { getPayloadClient } from '@/utils/payload'
import { getArticleUrl, getImageUrl, getCategoryName } from '@/lib/constants'

export async function GET(request: NextRequest) {
  const proto = request.headers.get('x-forwarded-proto') || 'http'
  const host = request.headers.get('host') || 'localhost:3000'
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || `${proto}://${host}`

  try {
    const payload = await getPayloadClient()
    const articles = await payload.find({
      collection: 'articles',
      limit: 50,
      depth: 1,
      sort: '-publishedAt',
    })

    const now = new Date().toUTCString()

    let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`
    xml += `<?xml-stylesheet type="text/xsl" href="/feed.xsl"?>\n`
    xml += `<rss version="2.0"\n`
    xml += `     xmlns:atom="http://www.w3.org/2005/Atom"\n`
    xml += `     xmlns:dc="http://purl.org/dc/elements/1.1/"\n`
    xml += `     xmlns:content="http://purl.org/rss/1.0/modules/content/"\n`
    xml += `     xmlns:media="http://search.yahoo.com/mrss/">\n`
    xml += `  <channel>\n`
    xml += `    <title>Gündem Akdeniz</title>\n`
    xml += `    <link>${baseUrl}</link>\n`
    xml += `    <description>Akdeniz Bölgesi'nin öncü haber platformu. Antalya, Mersin, Adana, Hatay ve tüm Akdeniz'den güncel haberler.</description>\n`
    xml += `    <language>tr</language>\n`
    xml += `    <lastBuildDate>${now}</lastBuildDate>\n`
    xml += `    <atom:link href="${baseUrl}/feed" rel="self" type="application/rss+xml"/>\n`
    xml += `    <image>\n`
    xml += `      <url>${baseUrl}/logo.png</url>\n`
    xml += `      <title>Gündem Akdeniz</title>\n`
    xml += `      <link>${baseUrl}</link>\n`
    xml += `      <width>144</width>\n`
    xml += `      <height>144</height>\n`
    xml += `    </image>\n`

    for (const doc of articles.docs) {
      const a = doc as any
      const articleUrl = `${baseUrl}${getArticleUrl(a.slug, a.id)}`
      const imgUrl = getImageUrl(a.featuredImage)
      const fullImgUrl = imgUrl ? (imgUrl.startsWith('http') ? imgUrl : `${baseUrl}${imgUrl}`) : ''
      const catName = getCategoryName(a.category)
      const pubDate = a.publishedAt ? new Date(a.publishedAt).toUTCString() : now

      xml += `    <item>\n`
      xml += `      <title>${escapeXml(a.title || '')}</title>\n`
      xml += `      <link>${articleUrl}</link>\n`
      xml += `      <guid isPermaLink="true">${articleUrl}</guid>\n`
      xml += `      <pubDate>${pubDate}</pubDate>\n`
      xml += `      <dc:creator>Gündem Akdeniz</dc:creator>\n`

      if (catName) {
        xml += `      <category>${escapeXml(catName)}</category>\n`
      }

      if (a.excerpt) {
        xml += `      <description>${escapeXml(a.excerpt)}</description>\n`
      }

      if (fullImgUrl) {
        xml += `      <media:content url="${escapeXml(fullImgUrl)}" medium="image"/>\n`
        xml += `      <media:thumbnail url="${escapeXml(fullImgUrl)}"/>\n`
      }

      xml += `    </item>\n`
    }

    xml += `  </channel>\n`
    xml += `</rss>`

    return new NextResponse(xml, {
      headers: {
        'Content-Type': 'application/rss+xml; charset=utf-8',
        'Cache-Control': 'public, max-age=1800, s-maxage=1800',
      },
    })
  } catch (err) {
    console.error('RSS feed error:', err)
    return new NextResponse('Error generating RSS feed', { status: 500 })
  }
}

function escapeXml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
}
