import { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { getWeatherCityBySlug, WEATHER_CITIES } from '@/lib/weather-cities';
import { getPayloadClient } from '@/utils/payload';
import { RichTextRenderer } from '@/components/RichTextRenderer';

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://gundemakdeniz.com';

export async function generateMetadata({ params }: { params: Promise<{ city: string }> }): Promise<Metadata> {
  const { city } = await params;
  const cityData = getWeatherCityBySlug(city);
  if (!cityData) return {};
  const title = `${cityData.label} Hava Durumu - İlçe İlçe Detaylı`;
  const description = `${cityData.label} ve ilçelerinin güncel hava durumu, 7 günlük tahmin. ${cityData.districts.map(d => d.name).slice(0, 5).join(', ')} ve diğer ilçeler.`;
  const url = `${BASE_URL}/hava-durumu/${city}`;
  return {
    title,
    description,
    alternates: { canonical: url },
    openGraph: { title, description, url, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
    twitter: { card: 'summary', title, description },
  };
}

export async function generateStaticParams() {
  return WEATHER_CITIES.map(c => ({ city: c.slug }));
}

export const revalidate = 1800;

interface WeatherData {
  city: string;
  slug: string;
  current: {
    temperature: number;
    feelsLike: number;
    humidity: number;
    weatherCode: number;
    windSpeed: number;
  };
  forecast: {
    date: string;
    weatherCode: number;
    tempMax: number;
    tempMin: number;
    precipitation: number;
    windSpeed: number;
  }[];
  districts: {
    name: string;
    temperature: number | null;
    weatherCode: number;
    windSpeed: number | null;
  }[];
}

async function getWeatherData(slug: string): Promise<WeatherData | null> {
  try {
    const baseUrl = process.env.NEXT_PUBLIC_SERVER_URL || 'http://localhost:3000';
    const res = await fetch(`${baseUrl}/api/weather/${slug}`, { next: { revalidate: 1800 } });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

function getWeatherIcon(code: number): string {
  if (code === 0) return 'clear_day';
  if (code <= 3) return 'partly_cloudy_day';
  if (code <= 48) return 'foggy';
  if (code <= 67) return 'rainy';
  if (code <= 77) return 'weather_snowy';
  if (code <= 82) return 'rainy';
  if (code <= 86) return 'weather_snowy';
  return 'thunderstorm';
}

function getWeatherText(code: number): string {
  const map: Record<number, string> = {
    0: 'Açık', 1: 'Çoğunlukla Açık', 2: 'Parçalı Bulutlu', 3: 'Kapalı',
    45: 'Sisli', 48: 'Kırağılı Sis',
    51: 'Hafif Çisenti', 53: 'Orta Çisenti', 55: 'Yoğun Çisenti',
    56: 'Dondurucu Çisenti', 57: 'Yoğun Dondurucu Çisenti',
    61: 'Hafif Yağmur', 63: 'Orta Yağmur', 65: 'Şiddetli Yağmur',
    66: 'Dondurucu Yağmur', 67: 'Yoğun Dondurucu Yağmur',
    71: 'Hafif Kar', 73: 'Orta Kar', 75: 'Yoğun Kar', 77: 'Kar Taneleri',
    80: 'Hafif Sağanak', 81: 'Orta Sağanak', 82: 'Şiddetli Sağanak',
    85: 'Hafif Kar Sağanağı', 86: 'Yoğun Kar Sağanağı',
    95: 'Gök Gürültülü Fırtına', 96: 'Dolu ile Fırtına', 99: 'Şiddetli Fırtına',
  };
  return map[code] || 'Parçalı Bulutlu';
}

function getDayName(dateStr: string): string {
  const date = new Date(dateStr + 'T00:00:00');
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  if (date.getTime() === today.getTime()) return 'Bugün';
  if (date.getTime() === tomorrow.getTime()) return 'Yarın';
  return date.toLocaleDateString('tr-TR', { weekday: 'long' });
}

function formatDate(dateStr: string): string {
  const date = new Date(dateStr + 'T00:00:00');
  return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long' });
}

export default async function WeatherCityPage({ params }: { params: Promise<{ city: string }> }) {
  const { city } = await params;
  const cityData = getWeatherCityBySlug(city);
  if (!cityData) notFound();

  const data = await getWeatherData(city);

  // SEO text çek
  let seoText: any = null;
  try {
    const payload = await getPayloadClient();
    const seoData = await payload.findGlobal({ slug: 'service-page-seo' }) as any;
    const fieldName = `weather${city.charAt(0).toUpperCase() + city.slice(1)}`;
    seoText = seoData?.[fieldName]?.seoText;
  } catch { /* */ }

  const today = new Date().toLocaleDateString('tr-TR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Anasayfa', item: BASE_URL },
      { '@type': 'ListItem', position: 2, name: 'Hava Durumu', item: `${BASE_URL}/hava-durumu` },
      { '@type': 'ListItem', position: 3, name: `${cityData.label} Hava Durumu`, item: `${BASE_URL}/hava-durumu/${city}` },
    ],
  };

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <Link href="/hava-durumu" className="hover:text-primary transition-colors">Hava Durumu</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">{cityData.label}</span>
        </nav>

        {/* Page Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-3xl lg:text-4xl font-extrabold tracking-tight text-slate-900">{cityData.label} Hava Durumu</h1>
          </div>
          <p className="text-slate-600 ml-5">İlçe ilçe detaylı hava durumu &middot; {today}</p>
        </div>

        {!data ? (
          <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
            <span className="material-symbols-outlined text-4xl text-yellow-500 mb-2">cloud_off</span>
            <p className="text-slate-600">Hava durumu verileri şu anda alınamıyor. Lütfen daha sonra tekrar deneyin.</p>
          </div>
        ) : (
          <>
            {/* Anlık Durum Kartı */}
            <div className="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-8 text-white mb-8 shadow-lg">
              <div className="flex flex-col md:flex-row items-center justify-between gap-6">
                <div className="flex items-center gap-6">
                  <span className="material-symbols-outlined text-7xl opacity-90">{getWeatherIcon(data.current.weatherCode)}</span>
                  <div>
                    <div className="text-6xl font-extrabold">{Math.round(data.current.temperature)}°C</div>
                    <div className="text-blue-100 text-lg mt-1">{getWeatherText(data.current.weatherCode)}</div>
                  </div>
                </div>
                <div className="grid grid-cols-3 gap-6 text-center">
                  <div>
                    <div className="text-blue-200 text-xs uppercase tracking-wider mb-1">Hissedilen</div>
                    <div className="text-2xl font-bold">{Math.round(data.current.feelsLike)}°</div>
                  </div>
                  <div>
                    <div className="text-blue-200 text-xs uppercase tracking-wider mb-1">Nem</div>
                    <div className="text-2xl font-bold">%{data.current.humidity}</div>
                  </div>
                  <div>
                    <div className="text-blue-200 text-xs uppercase tracking-wider mb-1">Rüzgar</div>
                    <div className="text-2xl font-bold">{Math.round(data.current.windSpeed)} km/s</div>
                  </div>
                </div>
              </div>
            </div>

            {/* 7 Günlük Tahmin */}
            <h2 className="text-xl font-bold text-slate-900 mb-4">7 Günlük Tahmin</h2>
            <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-10">
              {data.forecast.map((day) => (
                <div key={day.date} className="bg-white rounded-xl border border-gray-200 p-4 text-center hover:shadow-md transition-shadow">
                  <div className="text-xs font-bold text-primary uppercase mb-1">{getDayName(day.date)}</div>
                  <div className="text-xs text-slate-400 mb-3">{formatDate(day.date)}</div>
                  <span className="material-symbols-outlined text-3xl text-blue-500 mb-2">{getWeatherIcon(day.weatherCode)}</span>
                  <div className="text-xs text-slate-500 mb-2 h-8 flex items-center justify-center">{getWeatherText(day.weatherCode)}</div>
                  <div className="flex items-center justify-center gap-2">
                    <span className="text-lg font-extrabold text-slate-800">{Math.round(day.tempMax)}°</span>
                    <span className="text-sm text-slate-400">{Math.round(day.tempMin)}°</span>
                  </div>
                  {day.precipitation > 0 && (
                    <div className="text-xs text-blue-500 mt-1 flex items-center justify-center gap-0.5">
                      <span className="material-symbols-outlined text-xs">water_drop</span>
                      {day.precipitation.toFixed(1)} mm
                    </div>
                  )}
                </div>
              ))}
            </div>

            {/* İlçeler */}
            <h2 className="text-xl font-bold text-slate-900 mb-4">İlçe Bazlı Hava Durumu</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
              {data.districts
                .filter(d => !d.name.includes('(Merkez)'))
                .sort((a, b) => a.name.localeCompare(b.name, 'tr'))
                .map((district) => (
                  <div key={district.name} className="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div className="flex items-center gap-3">
                      <span className="material-symbols-outlined text-2xl text-blue-400">{getWeatherIcon(district.weatherCode)}</span>
                      <div>
                        <div className="font-semibold text-slate-800">{district.name}</div>
                        <div className="text-xs text-slate-400">{getWeatherText(district.weatherCode)}</div>
                      </div>
                    </div>
                    <div className="text-right">
                      {district.temperature !== null && (
                        <div className="text-xl font-extrabold text-slate-800">{Math.round(district.temperature)}°C</div>
                      )}
                      {district.windSpeed !== null && (
                        <div className="text-xs text-slate-400">{Math.round(district.windSpeed)} km/s</div>
                      )}
                    </div>
                  </div>
                ))}
            </div>

            {/* Diğer Şehirler */}
            <h2 className="text-xl font-bold text-slate-900 mb-4">Diğer İller</h2>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              {WEATHER_CITIES.filter(c => c.slug !== city).map(c => (
                <Link
                  key={c.slug}
                  href={`/hava-durumu/${c.slug}`}
                  className="bg-white rounded-xl border border-gray-200 p-4 text-center hover:shadow-md hover:border-primary/30 transition-all group"
                >
                  <span className="material-symbols-outlined text-2xl text-blue-400 mb-1">wb_sunny</span>
                  <div className="font-semibold text-slate-800 group-hover:text-primary transition-colors text-sm">{c.label}</div>
                </Link>
              ))}
            </div>
          </>
        )}

        {/* Bilgi */}
        <div className="mt-10 bg-gray-50 border border-gray-200 rounded-xl p-6">
          <p className="text-sm text-slate-600 leading-relaxed">
            Hava durumu verileri <strong>Open-Meteo</strong> meteoroloji servisi üzerinden sağlanmaktadır.
            Veriler 30 dakikada bir güncellenmektedir. Tahminler bilgi amaçlıdır, kesin sonuçlar için
            Meteoroloji Genel Müdürlüğü&apos;nün resmi sitesini ziyaret edebilirsiniz.
          </p>
        </div>

        {/* SEO Text */}
        {seoText && (
          <div className="mt-10 prose prose-slate max-w-none prose-headings:font-extrabold prose-headings:tracking-tight prose-a:text-primary">
            <RichTextRenderer content={seoText} />
          </div>
        )}
      </main>
      <Footer />
    </>
  );
}
