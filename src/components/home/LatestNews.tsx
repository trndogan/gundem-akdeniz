import Link from 'next/link';
import React from 'react';
import { Article } from '@/types';
import { getArticleUrl, getImageUrl, getCategoryName } from '@/lib/constants';

interface LatestNewsProps {
  articles: Article[];
}

export const LatestNews = ({ articles }: LatestNewsProps) => {
  return (
    <section className="py-12 border-t border-gray-200">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Son Haberler</h2>
                <p className="text-slate-600 mt-1">Gündemden en sıcak gelişmeler ve son dakika haberleri</p>
            </div>
            
            <Link href="#" className="text-primary font-bold text-sm hover:underline flex items-center gap-1">
                Tümünü Gör <span className="material-symbols-outlined text-sm">arrow_forward</span>
            </Link>
        </div>

        {/* News Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            {articles.map((item) => (
                <Link key={item.id} href={getArticleUrl(item.slug, item.id)} className="group cursor-pointer block">
                    <div className="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm">
                        <img 
                            src={getImageUrl(item.featuredImage)}
                            alt={item.title}
                            className="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                        />
                        <div className="absolute top-3 left-3 bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase z-10">
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
                        <span className="capitalize">{item.location || 'Genel'}</span>
                        <span>{new Date(item.publishedAt).toLocaleDateString('tr-TR')}</span>
                    </div>
                </Link>
            ))}

        </div>
    </section>
  );
};
