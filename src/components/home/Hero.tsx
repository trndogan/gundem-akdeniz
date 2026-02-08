import React from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { Article } from '@/types';
import { getArticleUrl, getImageUrl, getCategoryName } from '@/lib/constants';

interface HeroProps {
  articles: Article[];
}

export const Hero = ({ articles }: HeroProps) => {
  if (!articles || articles.length === 0) return null;

  const mainStory = articles[0];
  const sideStories = articles.slice(1, 5);

  return (
    <section className="grid grid-cols-1 lg:grid-cols-5 gap-4 h-auto lg:h-[600px] mb-12">
        
        {/* Main Story (3/5) */}
        <Link href={getArticleUrl(mainStory.slug, mainStory.id)} className="lg:col-span-3 relative rounded-xl overflow-hidden group bento-card cursor-pointer block min-h-[400px]">
            <div className="absolute inset-0">
                <Image 
                    src={getImageUrl(mainStory.featuredImage)}
                    alt={mainStory.title}
                    fill
                    sizes="(max-width: 1024px) 100vw, 60vw"
                    className="object-cover transition-transform duration-700 group-hover:scale-105"
                    priority
                    quality={75}
                />
                <div className="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/90"></div>
            </div>
            <div className="absolute top-6 left-6 flex gap-2 z-10">
                <span className="bg-primary text-white text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Özel Haber</span>
                {mainStory.location && (
                    <span className="bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider capitalize">
                        {mainStory.location}
                    </span>
                )}
            </div>
            <div className="absolute bottom-0 left-0 p-8 w-full lg:w-4/5">
                <p className="text-white/70 text-sm font-medium mb-3 flex items-center gap-2">
                    <span className="material-symbols-outlined text-sm">schedule</span> 
                    {new Date(mainStory.publishedAt).toLocaleDateString('tr-TR', { day: 'numeric', month: 'long' })}
                </p>
                <h2 className="text-white text-3xl lg:text-5xl font-extrabold leading-tight mb-4 tracking-tight">
                    {mainStory.title}
                </h2>
                <p className="text-white/80 text-lg line-clamp-2 mb-6 font-light">
                    {mainStory.excerpt}
                </p>
                <button className="bg-white text-black px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-primary hover:text-white transition-all">
                    Haberi Oku
                </button>
            </div>
        </Link>

        {/* Trending Stack (2/5) */}
        <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-4">
            
            {sideStories.map((story) => (
                <Link key={story.id} href={getArticleUrl(story.slug, story.id)} className="relative rounded-xl overflow-hidden bento-card cursor-pointer border border-gray-200 bg-white shadow-sm block">
                    <div className="flex h-full">
                        <div className="w-1/3 relative min-h-[120px]">
                            <Image 
                                src={getImageUrl(story.featuredImage)}
                                alt={story.title}
                                fill
                                sizes="(max-width: 768px) 33vw, 15vw"
                                className="object-cover"
                            />
                        </div>
                        <div className="w-2/3 p-4 flex flex-col justify-center">
                            <span className="text-primary text-[10px] font-bold uppercase mb-1">
                                {getCategoryName(story.category)}
                            </span>
                            <h3 className="text-base font-bold leading-snug text-slate-900 group-hover:text-primary transition-colors line-clamp-2">
                                {story.title}
                            </h3>
                            <p className="text-xs text-slate-600 mt-2 line-clamp-1">{story.excerpt}</p>
                        </div>
                    </div>
                </Link>
            ))}

        </div>
    </section>
  );
};
