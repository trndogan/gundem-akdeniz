import { NextRequest, NextResponse } from 'next/server'
import fs from 'fs'
import path from 'path'

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

interface CacheData {
  pharmacies: Pharmacy[]
  updatedAt: string
}

const CACHE_DIR = '/tmp/pharmacy-cache'

function ensureCacheDir() {
  if (!fs.existsSync(CACHE_DIR)) {
    fs.mkdirSync(CACHE_DIR, { recursive: true })
  }
}

function getCachePath(citySlug: string): string {
  return path.join(CACHE_DIR, `${citySlug}.json`)
}

function readCache(citySlug: string): CacheData | null {
  try {
    const cachePath = getCachePath(citySlug)
    if (!fs.existsSync(cachePath)) return null
    const raw = fs.readFileSync(cachePath, 'utf-8')
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function writeCache(citySlug: string, data: CacheData) {
  try {
    ensureCacheDir()
    fs.writeFileSync(getCachePath(citySlug), JSON.stringify(data), 'utf-8')
  } catch (err) {
    console.error('Cache write error:', err)
  }
}

export async function fetchFromCollectApi(citySlug: string): Promise<Pharmacy[]> {
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
    cache: 'no-store',
  })
  if (!res.ok) {
    console.error(`CollectAPI error: ${res.status} ${res.statusText}`)
    return []
  }
  const data = await res.json()
  if (!data?.result || !Array.isArray(data.result)) return []

  const pharmacies = data.result.map((p: any) => ({
    name: p.name || '',
    district: p.dist || '',
    address: p.address || '',
    phone: p.phone || '',
  }))

  writeCache(citySlug, { pharmacies, updatedAt: new Date().toISOString() })

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

  const forceRefresh = request.nextUrl.searchParams.get('refresh') === 'true'

  let pharmacies: Pharmacy[] = []
  let updatedAt = new Date().toISOString()

  const cached = readCache(citySlug)

  if (cached && !forceRefresh) {
    pharmacies = cached.pharmacies
    updatedAt = cached.updatedAt
  } else {
    try {
      pharmacies = await fetchFromCollectApi(citySlug)
      updatedAt = new Date().toISOString()
    } catch (err) {
      console.error('Pharmacy fetch error:', err)
      if (cached) {
        pharmacies = cached.pharmacies
        updatedAt = cached.updatedAt
      }
    }
  }

  return NextResponse.json({
    city: CITY_LABEL_MAP[city] || city,
    count: pharmacies.length,
    pharmacies,
    updatedAt,
  })
}
