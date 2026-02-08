import React from 'react';
import { Article } from '@/types';

interface BreakingNewsProps {
  articles: Article[];
}

export const BreakingNews = ({ articles }: BreakingNewsProps) => {
  // Show at least 5 items for the marquee to look good
  const displayArticles = articles.length > 0 ? articles : [];
  
  // If we have very few articles, duplicate them to ensure smooth marquee
  const marqueeItems = displayArticles.length < 5 
    ? [...displayArticles, ...displayArticles, ...displayArticles] 
    : [...displayArticles, ...displayArticles]; // Always duplicate for seamless loop

  return (
    <div className="bg-gray-100 border-b border-gray-200 overflow-hidden h-11 flex items-center">
      <div className="flex items-center w-full">
        <div className="flex items-center gap-2 font-bold text-xs tracking-wider uppercase shrink-0 px-5 bg-gray-100 z-10">
          <div className="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
          <span className="text-slate-900">SON DAKİKA</span>
        </div>
        <div className="flex-1 overflow-hidden relative">
          <div className="flex items-center gap-6 text-sm font-medium whitespace-nowrap animate-marquee text-slate-700">
            {marqueeItems.map((article, index) => (
              <React.Fragment key={`${article.id}-${index}`}>
                <p>{article.title}</p>
                <span className="text-gray-400">•</span>
              </React.Fragment>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
