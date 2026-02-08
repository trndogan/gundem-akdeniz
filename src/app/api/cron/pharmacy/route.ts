import { NextRequest, NextResponse } from 'next/server'
import { revalidatePath } from 'next/cache'

// Gece 00:00'da çağrılacak cron endpoint
// Tüm nöbetçi eczane sayfalarının cache'ini temizler ve veriyi yeniden çeker
// Güvenlik: CRON_SECRET env değişkeni ile koruma

const CITIES = ['antalya', 'mersin', 'adana', 'hatay', 'isparta', 'burdur', 'osmaniye', 'kahramanmaras']

export async function GET(request: NextRequest) {
  // Güvenlik kontrolü
  const authHeader = request.headers.get('authorization')
  const cronSecret = process.env.CRON_SECRET

  if (cronSecret && authHeader !== `Bearer ${cronSecret}`) {
    return NextResponse.json({ error: 'Yetkisiz erişim' }, { status: 401 })
  }

  try {
    // 1. Ana nöbetçi eczane sayfasını revalidate et
    revalidatePath('/nobetci-eczane')

    // 2. Her il sayfasını revalidate et
    for (const city of CITIES) {
      revalidatePath(`/nobetci-eczane/${city}`)
    }

    // 3. API cache'lerini de temizle - her il için veriyi yeniden çek
    const baseUrl = process.env.NEXT_PUBLIC_SERVER_URL || 'http://localhost:3000'
    const results: Record<string, number> = {}

    for (const city of CITIES) {
      try {
        const res = await fetch(`${baseUrl}/api/pharmacy/${city}`, {
          cache: 'no-store',
        })
        const data = await res.json()
        results[city] = data.count || 0
      } catch {
        results[city] = -1
      }
    }

    return NextResponse.json({
      success: true,
      message: 'Nöbetçi eczane verileri güncellendi',
      updatedAt: new Date().toISOString(),
      results,
    })
  } catch (err) {
    console.error('Cron pharmacy error:', err)
    return NextResponse.json({ success: false, error: String(err) }, { status: 500 })
  }
}
