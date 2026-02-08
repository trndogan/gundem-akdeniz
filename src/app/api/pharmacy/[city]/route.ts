import { NextRequest, NextResponse } from 'next/server'

const CITY_NAME_MAP: Record<string, string> = {
  antalya: 'antalya',
  mersin: 'mersin',
  adana: 'adana',
  hatay: 'hatay',
  isparta: 'isparta',
  burdur: 'burdur',
  osmaniye: 'osmaniye',
  kahramanmaras: 'kahramanmaras',
}

const CITY_LABEL_MAP: Record<string, string> = {
  antalya: 'Antalya',
  mersin: 'Mersin',
  adana: 'Adana',
  hatay: 'Hatay',
  isparta: 'Isparta',
  burdur: 'Burdur',
  osmaniye: 'Osmaniye',
  kahramanmaras: 'Kahramanmaraş',
}

interface Pharmacy {
  name: string
  district: string
  address: string
  phone: string
}

async function fetchFromEczanelerGenTr(citySlug: string): Promise<Pharmacy[]> {
  const url = `https://www.eczaneler.gen.tr/nobetci-${citySlug}`
  const res = await fetch(url, {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
      'Accept': 'text/html,application/xhtml+xml',
      'Accept-Language': 'tr-TR,tr;q=0.9',
    },
    next: { revalidate: 600 },
  })
  if (!res.ok) return []
  const html = await res.text()

  const pharmacies: Pharmacy[] = []

  // HTML'i </td></tr> ile böl - her parça bir eczane satırı
  const blocks = html.split('</td></tr>')

  for (const block of blocks) {
    // Sadece eczane satırlarını al (isim span'ı olanlar)
    if (!block.includes('class="isim"')) continue

    // İsim
    const nameMatch = block.match(/<span class="isim">(.*?)<\/span>/)
    const name = nameMatch ? nameMatch[1].trim() : ''
    if (!name) continue

    // İlçe
    const districtMatch = block.match(/bg-info[^"]*">(.*?)<\/span>/)
    const district = districtMatch ? districtMatch[1].trim() : ''

    // Telefon
    const phoneMatch = block.match(/col-lg-3 py-lg-2'>(.*?)<\/div>/)
    const phone = phoneMatch ? phoneMatch[1].replace(/<[^>]+>/g, '').trim() : ''

    // Adres - col-lg-6 div'inden ilk metin
    const addrMatch = block.match(/col-lg-6'>(.*?)(?:<br|<div)/)
    let address = ''
    if (addrMatch) {
      address = addrMatch[1]
        .replace(/<[^>]+>/g, '')
        .replace(/&raquo;/g, '')
        .replace(/&apos;/g, "'")
        .replace(/&amp;/g, '&')
        .replace(/\s+/g, ' ')
        .trim()
    }

    pharmacies.push({ name, district, address, phone })
  }

  return pharmacies
}

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ city: string }> }
) {
  const { city } = await params
  const citySlug = CITY_NAME_MAP[city]
  if (!citySlug) {
    return NextResponse.json({ error: 'Geçersiz şehir' }, { status: 400 })
  }

  let pharmacies: Pharmacy[] = []

  try {
    pharmacies = await fetchFromEczanelerGenTr(citySlug)
  } catch (err) {
    console.error('Pharmacy fetch error:', err)
  }

  return NextResponse.json({
    city: CITY_LABEL_MAP[city] || city,
    count: pharmacies.length,
    pharmacies,
    updatedAt: new Date().toISOString(),
  })
}
