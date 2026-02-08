import { NextRequest, NextResponse } from 'next/server'
import { getPayloadClient } from '@/utils/payload'
import { getArticleUrl } from '@/lib/constants'

const POSTS_PER_SITEMAP = 1000

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ page: string }> }
) {
  const { page: pageStr } = await params
  const page = parseInt(pageStr, 10)
  if (isNaN(page) || page < 1) {
    return new NextResponse('Not found', { status: 404 })
  }

  const proto = request.headers.get('x-forwarded-proto') || 'http'
  const host = request.headers.get('host') || 'localhost:3000'
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || `${proto}://${host}`

  try {
    const payload = await getPayloadClient()
    const articles = await payload.find({
      collection: 'articles',
      limit: POSTS_PER_SITEMAP,
      page,
      depth: 1,
      sort: '-publishedAt',
    })

    let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`
    xml += `<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>\n`
    xml += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"\n`
    xml += `        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"\n`
    xml += `        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">\n`

    for (const doc of articles.docs) {
      const a = doc as any
      const url = getArticleUrl(a.slug, a.id)
      const lastmod = a.updatedAt || a.publishedAt
      const imageUrl = typeof a.featuredImage === 'object' ? a.featuredImage?.url : null

      xml += `  <url>\n`
      xml += `    <loc>${baseUrl}${url}</loc>\n`
      xml += `    <lastmod>${new Date(lastmod).toISOString()}</lastmod>\n`
      xml += `    <changefreq>weekly</changefreq>\n`
      xml += `    <priority>0.9</priority>\n`

      if (a.publishedAt) {
        xml += `    <news:news>\n`
        xml += `      <news:publication>\n`
        xml += `        <news:name>Gündem Akdeniz</news:name>\n`
        xml += `        <news:language>tr</news:language>\n`
        xml += `      </news:publication>\n`
        xml += `      <news:publication_date>${new Date(a.publishedAt).toISOString()}</news:publication_date>\n`
        xml += `      <news:title>${escapeXml(a.title || '')}</news:title>\n`
        xml += `    </news:news>\n`
      }

      if (imageUrl) {
        const fullImageUrl = imageUrl.startsWith('http') ? imageUrl : `${baseUrl}${imageUrl}`
        xml += `    <image:image>\n`
        xml += `      <image:loc>${escapeXml(fullImageUrl)}</image:loc>\n`
        xml += `      <image:title>${escapeXml(a.title || '')}</image:title>\n`
        xml += `    </image:image>\n`
      }

      xml += `  </url>\n`
    }

    xml += `</urlset>`

    return new NextResponse(xml, {
      headers: {
        'Content-Type': 'application/xml; charset=utf-8',
        'Cache-Control': 'public, max-age=3600, s-maxage=3600',
      },
    })
  } catch (err) {
    console.error('Post sitemap error:', err)
    return new NextResponse('Error generating sitemap', { status: 500 })
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
