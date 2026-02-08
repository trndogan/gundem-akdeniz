import Link from 'next/link';
import React from 'react';
import { getPayloadClient } from '@/utils/payload';

// Varsayılan footer sütunları (admin'den veri yoksa)
const defaultColumns = [
  {
    title: 'Bölgeler',
    links: [
      { label: 'Antalya Haberleri', url: '/antalya' },
      { label: 'Mersin Haberleri', url: '/mersin' },
      { label: 'Adana Haberleri', url: '/adana' },
      { label: 'Hatay Haberleri', url: '/hatay' },
      { label: 'Isparta Haberleri', url: '/isparta' },
      { label: 'Burdur Haberleri', url: '/burdur' },
      { label: 'Osmaniye Haberleri', url: '/osmaniye' },
      { label: 'K.Maraş Haberleri', url: '/kahramanmaras' },
    ],
  },
  {
    title: 'Kategoriler',
    links: [
      { label: 'Gündem', url: '/gundem' },
      { label: 'Ekonomi', url: '/ekonomi' },
      { label: 'Turizm', url: '/turizm' },
      { label: 'Kültür', url: '/kultur' },
      { label: 'Spor', url: '/spor' },
      { label: 'Teknoloji', url: '/teknoloji' },
      { label: 'Girişim', url: '/girisim' },
      { label: 'Denizcilik', url: '/denizcilik' },
    ],
  },
  {
    title: 'Kurumsal',
    links: [
      { label: 'Hakkımızda', url: '/hakkimizda' },
      { label: 'İletişim', url: '/iletisim' },
      { label: 'Reklam', url: '/reklam' },
      { label: 'Künye', url: '/kunye' },
      { label: 'Gizlilik Politikası', url: '/gizlilik-politikasi' },
      { label: 'Kullanım Koşulları', url: '/kullanim-kosullari' },
    ],
  },
];

const defaultBanners = [
  { icon: 'wb_sunny', title: 'Hava Durumu', subtitle: 'İlçe ilçe detaylı', url: '/hava-durumu' },
  { icon: 'local_pharmacy', title: 'Nöbetçi Eczane', subtitle: 'Canlı listeler', url: '/nobetci-eczane' },
  { icon: 'sentiment_dissatisfied', title: 'Vefat Haberleri', subtitle: 'Taziye ilanları', url: '#' },
  { icon: 'work', title: 'İş İlanları', subtitle: 'Bölgesel fırsatlar', url: '#' },
];

export const Footer = async () => {
  let footerData: any = null;
  let siteData: any = null;
  try {
    const payload = await getPayloadClient();
    footerData = await payload.findGlobal({ slug: 'footer-settings' });
    siteData = await payload.findGlobal({ slug: 'site-settings' });
  } catch {
    // Fallback
  }

  const description = footerData?.description || "Akdeniz Bölgesi'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.";
  const columns = footerData?.columns?.length > 0 ? footerData.columns : defaultColumns;
  const banners = footerData?.serviceBanners?.length > 0 ? footerData.serviceBanners : defaultBanners;
  const copyright = footerData?.copyrightText || '© 2026 GundemAkdeniz.com - Tüm hakları saklıdır.';
  const social = siteData?.socialLinks || {};

  return (
    <footer className="bg-gray-100 border-t border-gray-200 py-16 mt-12">
        <div className="max-w-[1440px] mx-auto px-6">
            {/* Main Footer Content */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12 pb-12 border-b border-gray-300">
                
                {/* Brand & Description */}
                <div className="lg:col-span-1">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="relative">
                            <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center relative overflow-hidden">
                                <div className="absolute inset-0 bg-gradient-to-br from-primary to-red-700"></div>
                                <span className="relative z-10 text-white font-black text-xl tracking-tighter">GA</span>
                                <div className="absolute bottom-0 left-0 right-0 h-1 bg-white/30"></div>
                            </div>
                        </div>
                        <h2 className="text-xl font-black tracking-tight leading-none text-slate-900">
                            GÜNDEM<span className="text-primary">AKDENİZ</span>
                        </h2>
                    </div>
                    <p className="text-slate-600 text-sm leading-relaxed mb-6">
                        {description}
                    </p>
                    {/* Social Media */}
                    <div className="flex gap-3">
                        {(social.facebook || !footerData) && (
                        <Link className="w-10 h-10 rounded-lg bg-white hover:bg-primary hover:text-white border border-gray-200 flex items-center justify-center transition-all hover:scale-110 text-slate-700" href={social.facebook || '#'} target="_blank">
                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </Link>
                        )}
                        {(social.twitter || !footerData) && (
                        <Link className="w-10 h-10 rounded-lg bg-white hover:bg-primary hover:text-white border border-gray-200 flex items-center justify-center transition-all hover:scale-110 text-slate-700" href={social.twitter || '#'} target="_blank">
                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </Link>
                        )}
                        {(social.instagram || !footerData) && (
                        <Link className="w-10 h-10 rounded-lg bg-white hover:bg-primary hover:text-white border border-gray-200 flex items-center justify-center transition-all hover:scale-110 text-slate-700" href={social.instagram || '#'} target="_blank">
                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </Link>
                        )}
                        {social.youtube && (
                        <Link className="w-10 h-10 rounded-lg bg-white hover:bg-primary hover:text-white border border-gray-200 flex items-center justify-center transition-all hover:scale-110 text-slate-700" href={social.youtube} target="_blank">
                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </Link>
                        )}
                        {social.tiktok && (
                        <Link className="w-10 h-10 rounded-lg bg-white hover:bg-primary hover:text-white border border-gray-200 flex items-center justify-center transition-all hover:scale-110 text-slate-700" href={social.tiktok} target="_blank">
                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/></svg>
                        </Link>
                        )}
                    </div>
                </div>

                {/* Dynamic Columns */}
                {columns.map((col: any, i: number) => (
                  <div key={i}>
                    <h3 className="text-base font-extrabold mb-6 uppercase tracking-wider text-slate-900">{col.title}</h3>
                    <ul className="space-y-3">
                      {(col.links || []).map((link: any, j: number) => (
                        <li key={j}>
                          <Link
                            className="text-slate-600 hover:text-primary transition-colors text-sm font-medium"
                            href={link.url || '#'}
                            {...(link.openInNewTab ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                          >
                            {link.label}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
            </div>

            {/* Servis Sayfaları Banner */}
            {banners.length > 0 && (
            <div className="mt-8 pt-8">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {banners.map((banner: any, i: number) => (
                    <Link key={i} href={banner.url || '#'} className="bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 transition-all group shadow-sm hover:shadow-md">
                        <div className="flex items-center gap-3">
                            <span className="material-symbols-outlined text-3xl text-primary">{banner.icon}</span>
                            <div>
                                <h4 className="font-bold text-sm text-slate-900 group-hover:text-primary transition-colors">{banner.title}</h4>
                                {banner.subtitle && <p className="text-xs text-slate-500">{banner.subtitle}</p>}
                            </div>
                        </div>
                    </Link>
                    ))}
                </div>
            </div>
            )}

            {/* Bottom Bar */}
            <div className="flex flex-col md:flex-row justify-between items-center gap-4 text-slate-600 text-sm mt-8">
                <p>{copyright}</p>
                <p className="text-xs text-slate-500">Akdeniz Bölgesi&apos;nin Güvenilir Haber Kaynağı</p>
            </div>
        </div>
    </footer>
  );
};
