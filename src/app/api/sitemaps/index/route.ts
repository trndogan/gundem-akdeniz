import { NextRequest, NextResponse } from 'next/server'
import { getPayloadClient } from '@/utils/payload'

const POSTS_PER_SITEMAP = 1000

export async function GET(request: NextRequest) {
  const proto = request.headers.get('x-forwarded-proto') || 'http'
  const host = request.headers.get('host') || 'localhost:3000'
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || `${proto}://${host}`

  let totalArticles = 0
  try {
    const payload = await getPayloadClient()
    const result = await payload.find({ collection: 'articles', limit: 0, depth: 0 })
    totalArticles = result.totalDocs
  } catch { /* */ }

  const postSitemapCount = Math.max(1, Math.ceil(totalArticles / POSTS_PER_SITEMAP))
  const now = new Date().toISOString()

  let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`
  xml += `<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>\n`
  xml += `<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n`

  for (let i = 1; i <= postSitemapCount; i++) {
    xml += `  <sitemap>\n`
    xml += `    <loc>${baseUrl}/post-sitemap${i}.xml</loc>\n`
    xml += `    <lastmod>${now}</lastmod>\n`
    xml += `  </sitemap>\n`
  }

  xml += `  <sitemap>\n`
  xml += `    <loc>${baseUrl}/page-sitemap.xml</loc>\n`
  xml += `    <lastmod>${now}</lastmod>\n`
  xml += `  </sitemap>\n`

  xml += `  <sitemap>\n`
  xml += `    <loc>${baseUrl}/category-sitemap.xml</loc>\n`
  xml += `    <lastmod>${now}</lastmod>\n`
  xml += `  </sitemap>\n`

  xml += `  <sitemap>\n`
  xml += `    <loc>${baseUrl}/service-sitemap.xml</loc>\n`
  xml += `    <lastmod>${now}</lastmod>\n`
  xml += `  </sitemap>\n`

  xml += `</sitemapindex>`

  return new NextResponse(xml, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, max-age=3600, s-maxage=3600',
    },
  })
}
