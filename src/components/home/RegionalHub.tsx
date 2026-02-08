'use client';
import React, { useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { Article } from '@/types';
import { getArticleUrl, getImageUrl, getCategoryName } from '@/lib/constants';

interface RegionalHubProps {
    articles: Article[];
}

export const RegionalHub = ({ articles }: RegionalHubProps) => {
  const [activeCity, setActiveCity] = useState('antalya');

  const filteredArticles = articles.filter(article => 
    article.location === activeCity
  );

  return (
    <section className="py-12 border-t border-gray-200">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Bölgesel Haberler</h2>
                <p className="text-slate-600 mt-1">Akdeniz kıyılarından yerel haberleri keşfedin</p>
            </div>
            
            {/* Tabbed Cities */}
            <div className="flex bg-gray-100 p-1 rounded-lg border border-gray-200 overflow-x-auto no-scrollbar gap-1">
                {['antalya', 'mersin', 'adana', 'hatay', 'isparta'].map((city) => (
                    <button 
                        key={city}
                        onClick={() => setActiveCity(city)}
                        className={`px-5 py-2 text-sm font-bold rounded-md transition-all capitalize ${
                            activeCity === city 
                            ? 'bg-primary text-white shadow-lg' 
                            : 'hover:bg-white text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        {city}
                    </button>
                ))}
            </div>
        </div>

        {/* City News Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            {filteredArticles.length > 0 ? (
                filteredArticles.map((item) => (
                    <Link key={item.id} href={getArticleUrl(item.slug, item.id)} className="group cursor-pointer block">
                       <div className="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm">
                           <Image 
                               src={getImageUrl(item.featuredImage)}
                               alt={item.title}
                               fill
                               sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 25vw"
                               className="object-cover transition-transform duration-500 group-hover:scale-110"
                           />
                           <div className="absolute top-3 left-3 bg-black/70 backdrop-blur-md px-2 py-1 rounded text-[10px] font-bold uppercase text-white z-10">
                               {getCategoryName(item.category)}
                           </div>
                       </div>
                       <h4 className="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
                           {item.title}
                       </h4>
                       <p className="text-slate-600 text-sm line-clamp-2">
                           {item.excerpt}
                       </p>
                       <div className="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
                           <span className="capitalize">{item.location}</span>
                           <span>{new Date(item.publishedAt).toLocaleDateString('tr-TR')}</span>
                       </div>
                   </Link>
               ))
            ) : (
                <div className="col-span-full py-12 text-center text-slate-500 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                    <p>Bu şehir için henüz haber eklenmemiş.</p>
                </div>
            )}

        </div>
    </section>
  );
};
