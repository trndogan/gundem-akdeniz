import { NextRequest, NextResponse } from 'next/server'
import { getPayloadClient } from '@/utils/payload'

export async function GET(request: NextRequest) {
  const proto = request.headers.get('x-forwarded-proto') || 'http'
  const host = request.headers.get('host') || 'localhost:3000'
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || `${proto}://${host}`

  let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`
  xml += `<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>\n`
  xml += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n`

  // Ana sayfa
  xml += `  <url>\n`
  xml += `    <loc>${baseUrl}</loc>\n`
  xml += `    <lastmod>${new Date().toISOString()}</lastmod>\n`
  xml += `    <changefreq>hourly</changefreq>\n`
  xml += `    <priority>1.0</priority>\n`
  xml += `  </url>\n`

  // Statik sayfalar (Hakkımızda, İletişim vb.)
  try {
    const payload = await getPayloadClient()
    const pages = await payload.find({ collection: 'pages', limit: 100, depth: 0 })
    for (const doc of pages.docs) {
      const p = doc as any
      if (p.slug) {
        xml += `  <url>\n`
        xml += `    <loc>${baseUrl}/${p.slug}</loc>\n`
        xml += `    <lastmod>${new Date(p.updatedAt).toISOString()}</lastmod>\n`
        xml += `    <changefreq>monthly</changefreq>\n`
        xml += `    <priority>0.5</priority>\n`
        xml += `  </url>\n`
      }
    }
  } catch { /* */ }

  xml += `</urlset>`

  return new NextResponse(xml, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, max-age=3600, s-maxage=3600',
    },
  })
}
