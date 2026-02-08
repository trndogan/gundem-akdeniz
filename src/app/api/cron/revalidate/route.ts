import { NextRequest, NextResponse } from 'next/server'
import { revalidatePath } from 'next/cache'

// Vercel Cron: Her gece 00:00 TSİ (21:00 UTC) çağrılır
// Nöbetçi eczane + hava durumu sayfalarını revalidate eder

const CITIES = ['antalya', 'mersin', 'adana', 'hatay', 'isparta', 'burdur', 'osmaniye', 'kahramanmaras']

export async function GET(request: NextRequest) {
  // Vercel cron güvenlik kontrolü
  const authHeader = request.headers.get('authorization')
  const cronSecret = process.env.CRON_SECRET

  if (cronSecret && authHeader !== `Bearer ${cronSecret}`) {
    return NextResponse.json({ error: 'Yetkisiz erişim' }, { status: 401 })
  }

  const results: Record<string, any> = { pharmacy: {}, weather: {} }

  try {
    const baseUrl = process.env.NEXT_PUBLIC_SERVER_URL || 'http://localhost:3000'

    // 1. Nöbetçi Eczane - Sayfaları revalidate et
    revalidatePath('/nobetci-eczane')
    for (const city of CITIES) {
      revalidatePath(`/nobetci-eczane/${city}`)
    }

    // 2. Nöbetçi Eczane - API cache'lerini temizle
    for (const city of CITIES) {
      try {
        const res = await fetch(`${baseUrl}/api/pharmacy/${city}`, { cache: 'no-store' })
        const data = await res.json()
        results.pharmacy[city] = data.count || 0
      } catch {
        results.pharmacy[city] = -1
      }
    }

    // 3. Hava Durumu - Sayfaları revalidate et
    revalidatePath('/hava-durumu')
    for (const city of CITIES) {
      revalidatePath(`/hava-durumu/${city}`)
    }

    // 4. Hava Durumu - API cache'lerini temizle
    for (const city of CITIES) {
      try {
        const res = await fetch(`${baseUrl}/api/weather/${city}`, { cache: 'no-store' })
        const data = await res.json()
        results.weather[city] = data.current?.temperature ?? 'error'
      } catch {
        results.weather[city] = 'error'
      }
    }

    // 5. Anasayfayı da revalidate et
    revalidatePath('/')

    return NextResponse.json({
      success: true,
      message: 'Tüm veriler güncellendi (eczane + hava durumu)',
      updatedAt: new Date().toISOString(),
      results,
    })
  } catch (err) {
    console.error('Cron revalidate error:', err)
    return NextResponse.json({ success: false, error: String(err) }, { status: 500 })
  }
}
