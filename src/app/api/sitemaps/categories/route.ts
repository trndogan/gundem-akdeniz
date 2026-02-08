import { NextRequest, NextResponse } from 'next/server'
import { getPayloadClient } from '@/utils/payload'

const CITY_SLUGS = ['antalya', 'mersin', 'adana', 'hatay', 'isparta', 'burdur', 'osmaniye', 'kahramanmaras']
const CITY_LABELS: Record<string, string> = {
  antalya: 'Antalya', mersin: 'Mersin', adana: 'Adana', hatay: 'Hatay',
  isparta: 'Isparta', burdur: 'Burdur', osmaniye: 'Osmaniye', kahramanmaras: 'Kahramanmaraş',
}

export async function GET(request: NextRequest) {
  const proto = request.headers.get('x-forwarded-proto') || 'http'
  const host = request.headers.get('host') || 'localhost:3000'
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || `${proto}://${host}`

  let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`
  xml += `<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>\n`
  xml += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n`

  // Şehir sayfaları
  for (const city of CITY_SLUGS) {
    xml += `  <url>\n`
    xml += `    <loc>${baseUrl}/${city}</loc>\n`
    xml += `    <lastmod>${new Date().toISOString()}</lastmod>\n`
    xml += `    <changefreq>daily</changefreq>\n`
    xml += `    <priority>0.8</priority>\n`
    xml += `  </url>\n`
  }

  // Kategori sayfaları
  try {
    const payload = await getPayloadClient()
    const categories = await payload.find({ collection: 'categories', limit: 100, depth: 0 })
    for (const doc of categories.docs) {
      const cat = doc as any
      if (cat.slug) {
        xml += `  <url>\n`
        xml += `    <loc>${baseUrl}/${cat.slug}</loc>\n`
        xml += `    <lastmod>${new Date(cat.updatedAt).toISOString()}</lastmod>\n`
        xml += `    <changefreq>daily</changefreq>\n`
        xml += `    <priority>0.7</priority>\n`
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
