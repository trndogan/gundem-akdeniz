import { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { getPayloadClient } from '@/utils/payload';
import { RichTextRenderer } from '@/components/RichTextRenderer';

export const revalidate = 600;

const CITY_MAP: Record<string, string> = {
  antalya: 'Antalya',
  mersin: 'Mersin',
  adana: 'Adana',
  hatay: 'Hatay',
  isparta: 'Isparta',
  burdur: 'Burdur',
  osmaniye: 'Osmaniye',
  kahramanmaras: 'Kahramanmaraş',
};

interface Pharmacy {
  name: string;
  district: string;
  address: string;
  phone: string;
}

async function getPharmacies(citySlug: string): Promise<{ pharmacies: Pharmacy[]; count: number }> {
  try {
    const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || process.env.VERCEL_URL || 'http://localhost:3000';
    const res = await fetch(`${baseUrl}/api/pharmacy/${citySlug}`, {
      next: { revalidate: 600 },
    });
    if (!res.ok) return { pharmacies: [], count: 0 };
    const data = await res.json();
    return { pharmacies: data.pharmacies || [], count: data.count || 0 };
  } catch {
    return { pharmacies: [], count: 0 };
  }
}

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://gundemakdeniz.com';

export async function generateMetadata({ params }: { params: Promise<{ city: string }> }): Promise<Metadata> {
  const { city } = await params;
  const label = CITY_MAP[city];
  if (!label) return {};
  const title = `${label} Nöbetçi Eczane - Güncel Liste`;
  const description = `${label} ilindeki bugünkü nöbetçi eczanelerin güncel listesi. Adres, telefon ve harita bilgileri.`;
  const url = `${BASE_URL}/nobetci-eczane/${city}`;
  return {
    title,
    description,
    alternates: { canonical: url },
    openGraph: { title, description, url, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
    twitter: { card: 'summary', title, description },
  };
}

export async function generateStaticParams() {
  return Object.keys(CITY_MAP).map((city) => ({ city }));
}

export default async function PharmacyCityPage({ params }: { params: Promise<{ city: string }> }) {
  const { city } = await params;
  const label = CITY_MAP[city];
  if (!label) notFound();

  const { pharmacies, count } = await getPharmacies(city);

  // SEO text çek
  let seoText: any = null;
  try {
    const payload = await getPayloadClient();
    const seoData = await payload.findGlobal({ slug: 'service-page-seo' }) as any;
    const fieldName = `pharmacy${city.charAt(0).toUpperCase() + city.slice(1)}`;
    seoText = seoData?.[fieldName]?.seoText;
  } catch { /* */ }

  const today = new Date().toLocaleDateString('tr-TR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  // İlçelere göre grupla
  const byDistrict: Record<string, Pharmacy[]> = {};
  pharmacies.forEach((p) => {
    const d = p.district || 'Diğer';
    if (!byDistrict[d]) byDistrict[d] = [];
    byDistrict[d].push(p);
  });
  const districts = Object.keys(byDistrict).sort();

  return (
    <>
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6 flex-wrap">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <Link href="/nobetci-eczane" className="hover:text-primary transition-colors">Nöbetçi Eczane</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">{label}</span>
        </nav>

        {/* Page Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">{label} Nöbetçi Eczane</h1>
          </div>
          <p className="text-slate-600 ml-5">{today} &middot; {count} nöbetçi eczane &middot; {districts.length} ilçe</p>
        </div>

        {/* Diğer İller */}
        <div className="flex flex-wrap gap-2 mb-8">
          {Object.entries(CITY_MAP).map(([slug, name]) => (
            <Link
              key={slug}
              href={`/nobetci-eczane/${slug}`}
              className={`px-4 py-2 rounded-lg text-sm font-semibold transition-all ${
                slug === city
                  ? 'bg-primary text-white'
                  : 'bg-white border border-gray-200 text-slate-600 hover:border-primary/30 hover:text-primary'
              }`}
            >
              {name}
            </Link>
          ))}
        </div>

        {/* Eczane yoksa */}
        {count === 0 && (
          <div className="py-20 text-center text-slate-500 bg-gray-50 rounded-xl border border-dashed border-gray-200">
            <span className="material-symbols-outlined text-5xl text-slate-300 mb-4 block">local_pharmacy</span>
            <p className="text-lg font-medium">{label} için nöbetçi eczane verisi bulunamadı.</p>
            <p className="text-sm mt-2">Lütfen daha sonra tekrar deneyin veya <strong>182</strong> ALO hattını arayın.</p>
          </div>
        )}

        {/* İlçelere göre eczaneler */}
        {districts.map((district) => (
          <section key={district} className="mb-10">
            <div className="flex items-center gap-3 mb-4">
              <h2 className="text-lg font-extrabold text-slate-900">{district}</h2>
              <span className="bg-primary/10 text-primary text-xs font-bold px-2.5 py-1 rounded-full">
                {byDistrict[district].length}
              </span>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {byDistrict[district].map((pharmacy, idx) => (
                <div
                  key={`${pharmacy.name}-${idx}`}
                  className="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-primary/20 transition-all duration-300"
                >
                  <h3 className="font-bold text-slate-900 mb-2">{pharmacy.name}</h3>
                  <p className="text-sm text-slate-500 leading-relaxed mb-4">{pharmacy.address}</p>
                  <div className="flex items-center gap-2 pt-3 border-t border-gray-100">
                    {pharmacy.phone && (
                      <a
                        href={`tel:${pharmacy.phone.replace(/\s/g, '')}`}
                        className="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-gray-50 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary transition-colors"
                      >
                        <span className="material-symbols-outlined text-base">call</span>
                        {pharmacy.phone}
                      </a>
                    )}
                    <a
                      href={`https://www.google.com/maps/search/${encodeURIComponent(pharmacy.name + ' ' + pharmacy.address)}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center justify-center gap-1 py-2 px-3 rounded-lg bg-gray-50 text-slate-700 text-sm font-medium hover:bg-primary/5 hover:text-primary transition-colors"
                    >
                      <span className="material-symbols-outlined text-base">map</span>
                      Harita
                    </a>
                  </div>
                </div>
              ))}
            </div>
          </section>
        ))}

        {/* Alt Bilgi */}
        <div className="mt-6 bg-gray-50 border border-gray-200 rounded-xl p-6">
          <p className="text-sm text-slate-600">
            Nöbetçi eczane bulamıyorsanız <strong>182</strong> numaralı ALO hattını arayabilirsiniz.
            Veriler 10 dakikada bir güncellenmektedir.
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
