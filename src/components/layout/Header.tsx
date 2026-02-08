import Link from 'next/link';
import React from 'react';
import { getPayloadClient } from '@/utils/payload';
import { MobileMenu } from './MobileMenu';

export const Header = async () => {
  let menuItems: any[] = [];
  try {
    const payload = await getPayloadClient();
    const menu = await payload.findGlobal({ slug: 'main-menu', depth: 1 });
    menuItems = (menu?.menuItems as any[]) || [];
  } catch {
    // Fallback: admin henüz ayarlanmadıysa varsayılan menü
  }

  // Fallback menü (admin'den veri yoksa)
  const hasMenu = menuItems.length > 0;

  return (
    <nav className="sticky top-0 z-50 glass-header">
      <div className="max-w-[1440px] mx-auto px-6 h-16 flex items-center justify-between">
        <div className="flex items-center gap-10">
          {/* Logo */}
          <Link href="/" className="flex items-center gap-3">
            <div className="relative">
              <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center relative overflow-hidden group">
                <div className="absolute inset-0 bg-gradient-to-br from-primary to-red-700"></div>
                <span className="relative z-10 text-white font-black text-xl tracking-tighter">GA</span>
                <div className="absolute bottom-0 left-0 right-0 h-1 bg-white/30"></div>
              </div>
            </div>
            <h1 className="text-2xl font-black tracking-tight leading-none text-slate-900">
              GÜNDEM<span className="text-primary">AKDENİZ</span>
            </h1>
          </Link>
          
          {/* Desktop Menu */}
          <div className="hidden lg:flex items-center gap-8">
            {hasMenu ? (
              <>
                {menuItems.map((item: any, i: number) => {
                  if (item.type === 'megamenu') {
                    return (
                      <div key={i} className="relative group">
                        <button className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide flex items-center gap-1">
                          {item.label}
                          <span className="material-symbols-outlined text-sm">expand_more</span>
                        </button>
                        <div className="absolute top-full left-0 mt-3 w-[340px] bg-white rounded-xl shadow-xl border border-gray-100 p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 before:content-[''] before:absolute before:-top-2 before:left-8 before:w-4 before:h-4 before:bg-white before:border-l before:border-t before:border-gray-100 before:rotate-45">
                          <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 px-1">{item.label}</p>
                          <div className="grid grid-cols-2 gap-1">
                            {(item.megaMenuItems || []).map((sub: any, j: number) => (
                              <Link key={j} href={sub.url || '#'} className="px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 hover:bg-primary/5 hover:text-primary transition-colors">
                                {sub.label}
                              </Link>
                            ))}
                          </div>
                        </div>
                      </div>
                    );
                  }

                  const href = item.type === 'category' && item.category
                    ? `/${typeof item.category === 'object' ? item.category.slug : ''}`
                    : item.url || '#';

                  return (
                    <Link
                      key={i}
                      className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide"
                      href={href}
                      {...(item.openInNewTab ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                    >
                      {item.label}
                    </Link>
                  );
                })}
              </>
            ) : (
              <>
                <Link className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide" href="/gundem">Gündem</Link>
                <div className="relative group">
                  <button className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide flex items-center gap-1">
                    Şehirler
                    <span className="material-symbols-outlined text-sm">expand_more</span>
                  </button>
                  <div className="absolute top-full left-0 mt-3 w-[340px] bg-white rounded-xl shadow-xl border border-gray-100 p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 before:content-[''] before:absolute before:-top-2 before:left-8 before:w-4 before:h-4 before:bg-white before:border-l before:border-t before:border-gray-100 before:rotate-45">
                    <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 px-1">Şehirler</p>
                    <div className="grid grid-cols-2 gap-1">
                      {[
                        { label: 'Antalya', slug: 'antalya' },
                        { label: 'Mersin', slug: 'mersin' },
                        { label: 'Adana', slug: 'adana' },
                        { label: 'Hatay', slug: 'hatay' },
                        { label: 'Isparta', slug: 'isparta' },
                        { label: 'Burdur', slug: 'burdur' },
                        { label: 'Osmaniye', slug: 'osmaniye' },
                        { label: 'K.Maraş', slug: 'kahramanmaras' },
                      ].map((city) => (
                        <Link key={city.slug} href={`/${city.slug}`} className="px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 hover:bg-primary/5 hover:text-primary transition-colors">
                          {city.label}
                        </Link>
                      ))}
                    </div>
                  </div>
                </div>
                <Link className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide" href="/ekonomi">Ekonomi</Link>
                <Link className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide" href="/turizm">Turizm</Link>
                <Link className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide" href="/spor">Spor</Link>
                <Link className="text-sm font-bold hover:text-primary transition-colors uppercase tracking-wide" href="/teknoloji">Teknoloji</Link>
              </>
            )}
          </div>
        </div>
        
        <div className="flex items-center gap-4">
          {/* Search Bar */}
          <div className="relative hidden md:block">
            <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input className="bg-white border border-gray-200 rounded-full pl-10 pr-4 py-1.5 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none w-64 transition-all" 
                   placeholder="Haber Ara..." 
                   type="text"/>
          </div>
          
          {/* Live Button - mobilde gizli */}
          <button className="hidden lg:flex items-center gap-1.5 bg-primary hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all">
            <span className="material-symbols-outlined text-sm">notifications</span>
            CANLI
          </button>
          
          {/* Mobile Menu */}
          <MobileMenu menuItems={hasMenu ? menuItems : undefined} />
        </div>
      </div>
    </nav>
  );
};
