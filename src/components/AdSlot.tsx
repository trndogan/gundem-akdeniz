import React from 'react';
import { getPayloadClient } from '@/utils/payload';
import { getImageUrl } from '@/lib/constants';

type AdPosition = 'sidebarTop' | 'sidebarBottom' | 'articleMiddle' | 'articleBottom' | 'homepageTop' | 'homepageMiddle';

const placeholderSizes: Record<AdPosition, { w: string; h: string; label: string }> = {
  sidebarTop: { w: '300', h: '250', label: '300 x 250' },
  sidebarBottom: { w: '300', h: '600', label: '300 x 600' },
  articleMiddle: { w: '728', h: '90', label: '728 x 90' },
  articleBottom: { w: '728', h: '90', label: '728 x 90' },
  homepageTop: { w: '728', h: '90', label: '728 x 90' },
  homepageMiddle: { w: '728', h: '90', label: '728 x 90' },
};

export async function AdSlot({ position }: { position: AdPosition }) {
  let adData: any = null;
  try {
    const payload = await getPayloadClient();
    const ads = await payload.findGlobal({ slug: 'advertisements', depth: 1 });
    adData = ads?.[position];
  } catch {
    // fallback
  }

  const size = placeholderSizes[position];

  // Reklam aktif ve kod varsa
  if (adData?.enabled && adData?.code) {
    return (
      <div className="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden shadow-sm">
        <div className="bg-gray-200 text-center py-1">
          <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">Reklam</span>
        </div>
        <div dangerouslySetInnerHTML={{ __html: adData.code }} />
      </div>
    );
  }

  // Reklam aktif ve görsel varsa
  if (adData?.enabled && adData?.image) {
    const imgUrl = getImageUrl(adData.image);
    if (imgUrl) {
      const content = (
        <div className="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden shadow-sm">
          <div className="bg-gray-200 text-center py-1">
            <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">Reklam</span>
          </div>
          <img src={imgUrl} alt="Reklam" className="w-full" loading="lazy" />
        </div>
      );
      if (adData.url) {
        return <a href={adData.url} target="_blank" rel="noopener noreferrer">{content}</a>;
      }
      return content;
    }
  }

  // Placeholder (reklam ayarlanmamış)
  const heightClass = position.includes('sidebar')
    ? position === 'sidebarTop' ? 'h-[250px]' : 'h-[600px]'
    : 'h-[90px]';

  return (
    <div className="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden shadow-sm">
      <div className="bg-gray-200 text-center py-1">
        <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">Reklam</span>
      </div>
      <div className={`flex items-center justify-center ${heightClass} bg-gradient-to-br from-gray-100 to-gray-200`}>
        <div className="text-center">
          <span className="material-symbols-outlined text-4xl text-slate-300">ad_units</span>
          <p className="text-xs text-slate-400 mt-2 font-medium">{size.label}</p>
          <p className="text-[10px] text-slate-400">Reklam Alanı</p>
        </div>
      </div>
    </div>
  );
}
