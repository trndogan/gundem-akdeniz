'use client';

import Link from 'next/link';
import { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

interface MenuItem {
  label: string;
  type: string;
  url?: string;
  megaMenuItems?: { label: string; url: string }[];
  category?: { slug: string } | string;
  openInNewTab?: boolean;
}

const defaultMenuItems: MenuItem[] = [
  { label: 'Gündem', type: 'link', url: '/gundem' },
  {
    label: 'Şehirler', type: 'megamenu',
    megaMenuItems: [
      { label: 'Antalya', url: '/antalya' },
      { label: 'Mersin', url: '/mersin' },
      { label: 'Adana', url: '/adana' },
      { label: 'Hatay', url: '/hatay' },
      { label: 'Isparta', url: '/isparta' },
      { label: 'Burdur', url: '/burdur' },
      { label: 'Osmaniye', url: '/osmaniye' },
      { label: 'K.Maraş', url: '/kahramanmaras' },
    ],
  },
  { label: 'Ekonomi', type: 'link', url: '/ekonomi' },
  { label: 'Turizm', type: 'link', url: '/turizm' },
  { label: 'Spor', type: 'link', url: '/spor' },
  { label: 'Teknoloji', type: 'link', url: '/teknoloji' },
];

export function MobileMenu({ menuItems }: { menuItems?: MenuItem[] }) {
  const [isOpen, setIsOpen] = useState(false);
  const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
  const [mounted, setMounted] = useState(false);

  useEffect(() => { setMounted(true); }, []);

  const items = menuItems && menuItems.length > 0 ? menuItems : defaultMenuItems;

  const menuPanel = (
    <>
      {/* Overlay */}
      {isOpen && (
        <div className="fixed inset-0 bg-black/50 lg:hidden" style={{ zIndex: 9998 }} onClick={() => setIsOpen(false)} />
      )}

      {/* Slide-in Menu */}
      <div
        className={`fixed top-0 right-0 h-full w-[280px] shadow-2xl transform transition-transform duration-300 lg:hidden ${isOpen ? 'translate-x-0' : 'translate-x-full'}`}
        style={{ zIndex: 9999, backgroundColor: '#ffffff' }}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-100">
          <span className="text-lg font-black tracking-tight text-slate-900">
            GÜNDEM<span className="text-primary">AKDENİZ</span>
          </span>
          <button onClick={() => setIsOpen(false)}>
            <span className="material-symbols-outlined text-xl text-slate-500">close</span>
          </button>
        </div>

        {/* Search */}
        <div className="p-4 border-b border-gray-100">
          <div className="relative">
            <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input
              className="w-full bg-gray-50 border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none"
              placeholder="Haber Ara..."
              type="text"
            />
          </div>
        </div>

        {/* Menu Items */}
        <div className="py-2 overflow-y-auto" style={{ maxHeight: 'calc(100vh - 140px)' }}>
          {items.map((item, i) => {
            if (item.type === 'megamenu') {
              const isExpanded = expandedIndex === i;
              return (
                <div key={i}>
                  <button
                    className="w-full flex items-center justify-between px-5 py-3 text-sm font-bold text-slate-900 hover:bg-gray-50 transition-colors"
                    onClick={() => setExpandedIndex(isExpanded ? null : i)}
                  >
                    {item.label}
                    <span className={`material-symbols-outlined text-sm text-slate-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}>
                      expand_more
                    </span>
                  </button>
                  {isExpanded && (
                    <div className="bg-gray-50 py-1">
                      {(item.megaMenuItems || []).map((sub, j) => (
                        <Link
                          key={j}
                          href={sub.url || '#'}
                          className="block px-8 py-2.5 text-sm text-slate-600 hover:text-primary transition-colors"
                          onClick={() => setIsOpen(false)}
                        >
                          {sub.label}
                        </Link>
                      ))}
                    </div>
                  )}
                </div>
              );
            }

            const href = item.type === 'category' && item.category
              ? `/${typeof item.category === 'object' ? item.category.slug : ''}`
              : item.url || '#';

            return (
              <Link
                key={i}
                href={href}
                className="block px-5 py-3 text-sm font-bold text-slate-900 hover:bg-gray-50 hover:text-primary transition-colors"
                onClick={() => setIsOpen(false)}
                {...(item.openInNewTab ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
              >
                {item.label}
              </Link>
            );
          })}

          {/* Divider + Extra Links */}
          <div className="border-t border-gray-100 mt-2 pt-2">
            <Link href="/nobetci-eczane" className="flex items-center gap-2 px-5 py-3 text-sm text-slate-600 hover:text-primary transition-colors" onClick={() => setIsOpen(false)}>
              <span className="material-symbols-outlined text-base">local_pharmacy</span>
              Nöbetçi Eczane
            </Link>
          </div>
        </div>
      </div>
    </>
  );

  return (
    <>
      {/* Hamburger Button */}
      <button className="lg:hidden text-slate-900" onClick={() => setIsOpen(!isOpen)}>
        <span className="material-symbols-outlined text-2xl">
          {isOpen ? 'close' : 'menu'}
        </span>
      </button>

      {/* Portal: overlay + menu body'ye taşınır */}
      {mounted && createPortal(menuPanel, document.body)}
    </>
  );
}
