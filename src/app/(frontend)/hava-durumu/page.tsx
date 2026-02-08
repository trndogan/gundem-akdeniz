import { Metadata } from 'next';
import Link from 'next/link';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { WEATHER_CITIES } from '@/lib/weather-cities';
import { getPayloadClient } from '@/utils/payload';
import { RichTextRenderer } from '@/components/RichTextRenderer';

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://gundemakdeniz.com';
const pageTitle = 'Hava Durumu - Akdeniz Bölgesi';
const pageDesc = 'Akdeniz Bölgesi illerinin güncel hava durumu. Antalya, Mersin, Adana, Hatay, Isparta, Burdur, Osmaniye ve Kahramanmaraş ilçe ilçe detaylı hava durumu tahminleri.';

export const metadata: Metadata = {
  title: pageTitle,
  description: pageDesc,
  alternates: { canonical: `${BASE_URL}/hava-durumu` },
  openGraph: { title: pageTitle, description: pageDesc, url: `${BASE_URL}/hava-durumu`, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
  twitter: { card: 'summary', title: pageTitle, description: pageDesc },
};

export const revalidate = 1800;

async function getCityWeather(slug: string) {
  try {
    const baseUrl = process.env.NEXT_PUBLIC_SERVER_URL || 'http://localhost:3000';
    const res = await fetch(`${baseUrl}/api/weather/${slug}`, { next: { revalidate: 1800 } });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

export default async function WeatherIndexPage() {
  const today = new Date().toLocaleDateString('tr-TR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  // Tüm şehirlerin anlık hava durumunu paralel çek
  const weatherPromises = WEATHER_CITIES.map(city => getCityWeather(city.slug));
  const weatherData = await Promise.all(weatherPromises);

  // SEO text çek
  let seoText: any = null;
  try {
    const payload = await getPayloadClient();
    const seoData = await payload.findGlobal({ slug: 'service-page-seo' }) as any;
    seoText = seoData?.weatherGeneral?.seoText;
  } catch { /* */ }

  return (
    <>
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">Hava Durumu</span>
        </nav>

        {/* Page Header */}
        <div className="mb-10">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">Hava Durumu</h1>
          </div>
          <p className="text-slate-600 ml-5">Akdeniz Bölgesi&apos;ndeki illerin güncel hava durumu &middot; {today}</p>
        </div>

        {/* Şehir Kartları */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {WEATHER_CITIES.map((city, i) => {
            const data = weatherData[i];
            const temp = data?.current?.temperature;
            const weatherCode = data?.current?.weatherCode ?? 0;
            const weatherIcon = getWeatherIcon(weatherCode);
            const weatherText = getWeatherText(weatherCode);

            return (
              <Link
                key={city.slug}
                href={`/hava-durumu/${city.slug}`}
                className="group bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-primary/30 transition-all duration-300"
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                      <span className="material-symbols-outlined text-blue-500 text-xl">{weatherIcon}</span>
                    </div>
                    <h2 className="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">
                      {city.label}
                    </h2>
                  </div>
                  {temp !== null && temp !== undefined && (
                    <span className="text-2xl font-extrabold text-slate-800">{Math.round(temp)}°</span>
                  )}
                </div>
                <p className="text-sm text-slate-500 mb-1">{weatherText}</p>
                <p className="text-xs text-slate-400 mb-4">{city.districts.length} ilçe detaylı tahmin</p>
                <div className="flex items-center gap-1 text-xs font-semibold text-primary">
                  Detaylı Görüntüle
                  <span className="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
              </Link>
            );
          })}
        </div>

        {/* Bilgi */}
        <div className="mt-10 bg-gray-50 border border-gray-200 rounded-xl p-6">
          <p className="text-sm text-slate-600 leading-relaxed">
            Hava durumu verileri <strong>Open-Meteo</strong> meteoroloji servisi üzerinden sağlanmaktadır.
            Veriler 30 dakikada bir güncellenmektedir. İlçe bazlı detaylı tahminler için il sayfalarını ziyaret edebilirsiniz.
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
    61: 'Hafif Yağmur', 63: 'Orta Yağmur', 65: 'Şiddetli Yağmur',
    71: 'Hafif Kar', 73: 'Orta Kar', 75: 'Yoğun Kar',
    80: 'Hafif Sağanak', 81: 'Orta Sağanak', 82: 'Şiddetli Sağanak',
    95: 'Fırtına', 96: 'Dolu ile Fırtına', 99: 'Şiddetli Fırtına',
  };
  return map[code] || 'Parçalı Bulutlu';
}
