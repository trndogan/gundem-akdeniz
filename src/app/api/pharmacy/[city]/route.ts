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

async function fetchFromCollectApi(citySlug: string): Promise<Pharmacy[]> {
  const cityLabel = CITY_LABEL_MAP[citySlug]
  if (!cityLabel) return []

  const apiKey = process.env.COLLECTAPI_KEY
  if (!apiKey) {
    console.error('COLLECTAPI_KEY env variable is not set')
    return []
  }

  const url = `https://api.collectapi.com/health/dutyPharmacy?il=${encodeURIComponent(cityLabel)}`
  const res = await fetch(url, {
    headers: {
      'authorization': `apikey ${apiKey}`,
      'content-type': 'application/json',
    },
    next: { revalidate: 86400 },
  })
  if (!res.ok) {
    console.error(`CollectAPI error: ${res.status} ${res.statusText}`)
    return []
  }
  const data = await res.json()
  if (!data?.result || !Array.isArray(data.result)) return []

  return data.result.map((p: any) => ({
    name: p.name || '',
    district: p.dist || '',
    address: p.address || '',
    phone: p.phone || '',
  }))
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
    pharmacies = await fetchFromCollectApi(citySlug)
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
