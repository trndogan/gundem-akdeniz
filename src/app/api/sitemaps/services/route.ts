import { NextRequest, NextResponse } from 'next/server'

const CITY_SLUGS = ['antalya', 'mersin', 'adana', 'hatay', 'isparta', 'burdur', 'osmaniye', 'kahramanmaras']

export async function GET(request: NextRequest) {
  const proto = request.headers.get('x-forwarded-proto') || 'http'
  const host = request.headers.get('host') || 'localhost:3000'
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || `${proto}://${host}`

  let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`
  xml += `<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>\n`
  xml += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n`

  // Nöbetçi Eczane ana sayfa
  xml += `  <url>\n`
  xml += `    <loc>${baseUrl}/nobetci-eczane</loc>\n`
  xml += `    <lastmod>${new Date().toISOString()}</lastmod>\n`
  xml += `    <changefreq>daily</changefreq>\n`
  xml += `    <priority>0.6</priority>\n`
  xml += `  </url>\n`

  // Nöbetçi Eczane il sayfaları
  for (const city of CITY_SLUGS) {
    xml += `  <url>\n`
    xml += `    <loc>${baseUrl}/nobetci-eczane/${city}</loc>\n`
    xml += `    <lastmod>${new Date().toISOString()}</lastmod>\n`
    xml += `    <changefreq>daily</changefreq>\n`
    xml += `    <priority>0.6</priority>\n`
    xml += `  </url>\n`
  }

  // Hava Durumu ana sayfa
  xml += `  <url>\n`
  xml += `    <loc>${baseUrl}/hava-durumu</loc>\n`
  xml += `    <lastmod>${new Date().toISOString()}</lastmod>\n`
  xml += `    <changefreq>hourly</changefreq>\n`
  xml += `    <priority>0.7</priority>\n`
  xml += `  </url>\n`

  // Hava Durumu il sayfaları
  for (const city of CITY_SLUGS) {
    xml += `  <url>\n`
    xml += `    <loc>${baseUrl}/hava-durumu/${city}</loc>\n`
    xml += `    <lastmod>${new Date().toISOString()}</lastmod>\n`
    xml += `    <changefreq>hourly</changefreq>\n`
    xml += `    <priority>0.7</priority>\n`
    xml += `  </url>\n`
  }

  xml += `</urlset>`

  return new NextResponse(xml, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, max-age=3600, s-maxage=3600',
    },
  })
}
