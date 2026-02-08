import { Metadata } from 'next';
import Link from 'next/link';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { getPayloadClient } from '@/utils/payload';
import { RichTextRenderer } from '@/components/RichTextRenderer';

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://gundemakdeniz.com';
const pageTitle = 'Nöbetçi Eczane - Akdeniz Bölgesi';
const pageDesc = 'Akdeniz Bölgesi illerindeki nöbetçi eczaneleri anlık olarak görüntüleyin. Antalya, Mersin, Adana, Hatay, Isparta, Burdur, Osmaniye ve Kahramanmaraş nöbetçi eczaneleri.';

export const metadata: Metadata = {
  title: pageTitle,
  description: pageDesc,
  alternates: { canonical: `${BASE_URL}/nobetci-eczane` },
  openGraph: { title: pageTitle, description: pageDesc, url: `${BASE_URL}/nobetci-eczane`, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
  twitter: { card: 'summary', title: pageTitle, description: pageDesc },
};

const PHARMACY_CITIES = [
  { label: 'Antalya', slug: 'antalya' },
  { label: 'Mersin', slug: 'mersin' },
  { label: 'Adana', slug: 'adana' },
  { label: 'Hatay', slug: 'hatay' },
  { label: 'Isparta', slug: 'isparta' },
  { label: 'Burdur', slug: 'burdur' },
  { label: 'Osmaniye', slug: 'osmaniye' },
  { label: 'Kahramanmaraş', slug: 'kahramanmaras' },
];

export default async function PharmacyIndexPage() {
  let seoText: any = null;
  try {
    const payload = await getPayloadClient();
    const seoData = await payload.findGlobal({ slug: 'service-page-seo' }) as any;
    seoText = seoData?.pharmacyGeneral?.seoText;
  } catch { /* */ }
  const today = new Date().toLocaleDateString('tr-TR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  return (
    <>
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">Nöbetçi Eczane</span>
        </nav>

        {/* Page Header */}
        <div className="mb-10">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">Nöbetçi Eczane</h1>
          </div>
          <p className="text-slate-600 ml-5">Akdeniz Bölgesi&apos;ndeki illerin güncel nöbetçi eczane listeleri &middot; {today}</p>
        </div>

        {/* Şehir Kartları */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {PHARMACY_CITIES.map((city) => (
            <Link
              key={city.slug}
              href={`/nobetci-eczane/${city.slug}`}
              className="group bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-primary/30 transition-all duration-300"
            >
              <div className="flex items-center gap-3 mb-3">
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                  <span className="material-symbols-outlined text-primary text-xl">local_pharmacy</span>
                </div>
                <h2 className="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">
                  {city.label}
                </h2>
              </div>
              <p className="text-sm text-slate-500 mb-4">{city.label} ili nöbetçi eczaneleri</p>
              <div className="flex items-center gap-1 text-xs font-semibold text-primary">
                Listele
                <span className="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
              </div>
            </Link>
          ))}
        </div>

        {/* Bilgi */}
        <div className="mt-10 bg-gray-50 border border-gray-200 rounded-xl p-6">
          <p className="text-sm text-slate-600 leading-relaxed">
            Nöbetçi eczaneler, mesai saatleri dışında ve tatil günlerinde hizmet veren eczanelerdir.
            Acil ilaç ihtiyacınızda <strong>182</strong> numaralı ALO hattını da arayabilirsiniz.
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
