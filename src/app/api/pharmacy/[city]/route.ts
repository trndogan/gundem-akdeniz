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

const CITY_PLATE_MAP: Record<string, string> = {
  antalya: '07',
  mersin: '33',
  adana: '01',
  hatay: '31',
  isparta: '32',
  burdur: '15',
  osmaniye: '80',
  kahramanmaras: '46',
}

interface Pharmacy {
  name: string
  district: string
  address: string
  phone: string
}

async function fetchFromNosyApi(citySlug: string): Promise<Pharmacy[]> {
  const cityLabel = CITY_LABEL_MAP[citySlug]
  if (!cityLabel) return []
  
  const url = `https://www.nosyapi.com/apiv2/service/pharmacies-on-duty?city=${encodeURIComponent(cityLabel)}`
  const res = await fetch(url, {
    headers: {
      'Authorization': 'Bearer ' + (process.env.NOSYAPI_KEY || ''),
      'Content-Type': 'application/json',
    },
    next: { revalidate: 600 },
  })
  if (!res.ok) return []
  const data = await res.json()
  if (!data?.data) return []
  
  return data.data.map((p: any) => ({
    name: p.pharmacyName || p.name || '',
    district: p.district || p.dist || '',
    address: p.address || '',
    phone: p.phone || '',
  }))
}

async function fetchFromEczaneApi(citySlug: string): Promise<Pharmacy[]> {
  const plate = CITY_PLATE_MAP[citySlug]
  if (!plate) return []

  const url = `https://api.eczaneler.gen.tr/nobetci?il=${plate}`
  const res = await fetch(url, {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
      'Accept': 'application/json',
    },
    next: { revalidate: 600 },
  })
  if (!res.ok) return []
  const data = await res.json()
  if (!Array.isArray(data)) return []

  return data.map((p: any) => ({
    name: p.EczaneAdi || p.name || '',
    district: p.Ilce || p.district || '',
    address: p.Adresi || p.address || '',
    phone: p.Telefon || p.phone || '',
  }))
}

async function fetchFromOpenApi(citySlug: string): Promise<Pharmacy[]> {
  const cityLabel = CITY_LABEL_MAP[citySlug]
  if (!cityLabel) return []

  const url = `https://api.collectapi.com/health/dutyPharmacy?il=${encodeURIComponent(cityLabel)}`
  const res = await fetch(url, {
    headers: {
      'Authorization': 'apikey ' + (process.env.COLLECTAPI_KEY || ''),
      'Content-Type': 'application/json',
    },
    next: { revalidate: 600 },
  })
  if (!res.ok) return []
  const data = await res.json()
  if (!data?.result) return []

  return data.result.map((p: any) => ({
    name: p.name || '',
    district: p.dist || '',
    address: p.address || '',
    phone: p.phone || '',
  }))
}

async function fetchFromRxMediaApi(citySlug: string): Promise<Pharmacy[]> {
  const cityLabel = CITY_LABEL_MAP[citySlug]
  if (!cityLabel) return []

  const url = `https://rxmedia.com.tr/api/nobetci-eczane/${encodeURIComponent(cityLabel)}`
  const res = await fetch(url, {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
      'Accept': 'application/json',
    },
    next: { revalidate: 600 },
  })
  if (!res.ok) return []
  const data = await res.json()
  if (!Array.isArray(data)) return []

  return data.map((p: any) => ({
    name: p.eczaneAdi || p.name || '',
    district: p.ilce || p.district || '',
    address: p.adres || p.address || '',
    phone: p.telefon || p.phone || '',
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

  // Sırayla farklı API kaynaklarını dene
  const fetchers = [
    fetchFromEczaneApi,
    fetchFromRxMediaApi,
    fetchFromNosyApi,
    fetchFromOpenApi,
  ]

  for (const fetcher of fetchers) {
    try {
      pharmacies = await fetcher(citySlug)
      if (pharmacies.length > 0) break
    } catch (err) {
      console.error(`Pharmacy fetch error (${fetcher.name}):`, err)
    }
  }

  return NextResponse.json({
    city: CITY_LABEL_MAP[city] || city,
    count: pharmacies.length,
    pharmacies,
    updatedAt: new Date().toISOString(),
  })
}
