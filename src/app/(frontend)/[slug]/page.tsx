import React from 'react';
import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import { getPayloadClient } from '@/utils/payload';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { CITY_SLUGS, getCityBySlug, getArticleUrl, getImageUrl, getCategoryName, getCategorySlug } from '@/lib/constants';
import { Article } from '@/types';
import { ViewCounter } from '@/components/ViewCounter';
import { AdSlot } from '@/components/AdSlot';

export const revalidate = 60;

// --- Helper: parse "slug-id" from the end ---
function parseArticleSlug(slug: string): { articleId: number | null } {
  const match = slug.match(/-(\d+)$/);
  if (match) return { articleId: parseInt(match[1], 10) };
  return { articleId: null };
}

// --- Generate Metadata ---
const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://gundemakdeniz.com';

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;

  // City page
  const city = getCityBySlug(slug);
  if (city) {
    const title = `${city.label} Haberleri`;
    const description = `${city.label} ve çevresinden en güncel haberler. Akdeniz Bölgesi'nin güvenilir haber kaynağı.`;
    return {
      title,
      description,
      alternates: { canonical: `${BASE_URL}/${slug}` },
      openGraph: { title, description, url: `${BASE_URL}/${slug}`, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
      twitter: { card: 'summary', title, description },
    };
  }

  // Category page
  if (!CITY_SLUGS.includes(slug as any)) {
    const payload = await getPayloadClient();
    const categories = await payload.find({ collection: 'categories', where: { slug: { equals: slug } }, limit: 1 });
    if (categories.docs.length > 0) {
      const cat = categories.docs[0] as any;
      const title = `${cat.name} Haberleri`;
      const description = `${cat.name} kategorisindeki en güncel haberler. Gündem Akdeniz.`;
      return {
        title,
        description,
        alternates: { canonical: `${BASE_URL}/${slug}` },
        openGraph: { title, description, url: `${BASE_URL}/${slug}`, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
        twitter: { card: 'summary', title, description },
      };
    }
  }

  // Article page
  const { articleId } = parseArticleSlug(slug);
  if (articleId) {
    const payload = await getPayloadClient();
    try {
      const article = await payload.findByID({ collection: 'articles', id: articleId, depth: 1 }) as any;
      if (article) {
        const imgUrl = getImageUrl(article.featuredImage);
        const fullImgUrl = imgUrl ? (imgUrl.startsWith('http') ? imgUrl : `${BASE_URL}${imgUrl}`) : '';
        const catName = getCategoryName(article.category);
        const articleUrl = `${BASE_URL}${getArticleUrl(article.slug, article.id)}`;
        return {
          title: article.title,
          description: article.excerpt || '',
          alternates: { canonical: articleUrl },
          openGraph: {
            title: article.title,
            description: article.excerpt || '',
            url: articleUrl,
            type: 'article',
            locale: 'tr_TR',
            siteName: 'Gündem Akdeniz',
            publishedTime: article.publishedAt,
            modifiedTime: article.updatedAt,
            section: catName || undefined,
            authors: ['Gündem Akdeniz'],
            images: fullImgUrl ? [{ url: fullImgUrl, width: 1200, height: 630, alt: article.title }] : [],
          },
          twitter: {
            card: 'summary_large_image',
            title: article.title,
            description: article.excerpt || '',
            images: fullImgUrl ? [fullImgUrl] : [],
          },
        };
      }
    } catch { /* not found */ }
  }

  // Static page
  try {
    const payload = await getPayloadClient();
    const pages = await payload.find({ collection: 'pages', where: { slug: { equals: slug } }, limit: 1 });
    if (pages.docs.length > 0) {
      const page = pages.docs[0] as any;
      return {
        title: page.title,
        description: page.metaDescription || '',
        alternates: { canonical: `${BASE_URL}/${slug}` },
        openGraph: { title: page.title, description: page.metaDescription || '', url: `${BASE_URL}/${slug}`, type: 'website', locale: 'tr_TR', siteName: 'Gündem Akdeniz' },
      };
    }
  } catch { /* */ }

  return { title: 'Sayfa Bulunamadı' };
}

// --- Page Component ---
export default async function SlugPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const payload = await getPayloadClient();

  // 1. Check if it's a city page
  const city = getCityBySlug(slug);
  if (city) {
    const articles = await payload.find({
      collection: 'articles',
      where: { location: { equals: city.value } },
      sort: '-publishedAt',
      limit: 20,
      depth: 1,
    });
    return <CityPage city={city} articles={articles.docs as unknown as Article[]} />;
  }

  // 2. Check if it's a category page
  const categories = await payload.find({
    collection: 'categories',
    where: { slug: { equals: slug } },
    limit: 1,
  });
  if (categories.docs.length > 0) {
    const category = categories.docs[0];
    const articles = await payload.find({
      collection: 'articles',
      where: { category: { equals: category.id } },
      sort: '-publishedAt',
      limit: 20,
      depth: 1,
    });
    return <CategoryPage category={category} articles={articles.docs as unknown as Article[]} />;
  }

  // 3. Check if it's an article page (slug-id format)
  const { articleId } = parseArticleSlug(slug);
  if (articleId) {
    try {
      const article = await payload.findByID({ collection: 'articles', id: articleId, depth: 2 });
      if (article) {
        // Fetch related articles (same category, for bottom section)
        const catId = article.category
          ? (typeof article.category === 'object' ? article.category?.id : article.category)
          : null;

        let relatedDocs: any[] = [];
        if (catId) {
          try {
            const related = await payload.find({
              collection: 'articles',
              where: {
                id: { not_equals: article.id },
                category: { equals: catId },
              },
              sort: '-publishedAt',
              limit: 3,
              depth: 1,
            });
            relatedDocs = related.docs;
          } catch { /* category may have been deleted */ }
        }

        // Fallback: if not enough related, fill with other recent articles
        if (relatedDocs.length < 3) {
          const existingIds = [article.id, ...relatedDocs.map((d: any) => d.id)];
          const fallback = await payload.find({
            collection: 'articles',
            where: { id: { not_in: existingIds } },
            sort: '-publishedAt',
            limit: 3 - relatedDocs.length,
            depth: 1,
          });
          relatedDocs = [...relatedDocs, ...fallback.docs];
        }
        // Fetch most read by viewCount
        const mostRead = await payload.find({
          collection: 'articles',
          where: { id: { not_equals: article.id } },
          sort: '-viewCount',
          limit: 5,
          depth: 1,
        });
        return <ArticleDetailPage article={article as unknown as Article} relatedArticles={relatedDocs as unknown as Article[]} mostReadArticles={mostRead.docs as unknown as Article[]} />;
      }
    } catch { /* not found */ }
  }

  // 4. Check if it's a static page
  const pages = await payload.find({ collection: 'pages', where: { slug: { equals: slug } }, limit: 1 });
  if (pages.docs.length > 0) {
    return <StaticPage page={pages.docs[0] as any} />;
  }

  notFound();
}

// ===================== ARTICLE DETAIL =====================
function ArticleDetailPage({ article, relatedArticles, mostReadArticles }: { article: Article; relatedArticles: Article[]; mostReadArticles: Article[] }) {
  const imageUrl = getImageUrl(article.featuredImage);
  const categoryName = getCategoryName(article.category);
  const categorySlug = getCategorySlug(article.category);

  const articleUrl = `${BASE_URL}${getArticleUrl(article.slug, article.id)}`;
  const fullImageUrl = imageUrl ? (imageUrl.startsWith('http') ? imageUrl : `${BASE_URL}${imageUrl}`) : undefined;

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'NewsArticle',
    mainEntityOfPage: { '@type': 'WebPage', '@id': articleUrl },
    headline: article.title,
    description: article.excerpt || '',
    image: fullImageUrl ? { '@type': 'ImageObject', url: fullImageUrl, width: 1200, height: 630 } : undefined,
    datePublished: article.publishedAt,
    dateModified: article.updatedAt,
    articleSection: categoryName || undefined,
    author: { '@type': 'Organization', name: 'Gündem Akdeniz', url: BASE_URL },
    publisher: {
      '@type': 'Organization',
      name: 'Gündem Akdeniz',
      url: BASE_URL,
      logo: { '@type': 'ImageObject', url: `${BASE_URL}/logo.png`, width: 112, height: 112 },
    },
  };

  const breadcrumbLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Anasayfa', item: BASE_URL },
      ...(categorySlug ? [{ '@type': 'ListItem', position: 2, name: categoryName, item: `${BASE_URL}/${categorySlug}` }] : []),
      { '@type': 'ListItem', position: categorySlug ? 3 : 2, name: article.title, item: articleUrl },
    ],
  };

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <ViewCounter articleId={article.id} />
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          {categorySlug && (
            <>
              <Link href={`/${categorySlug}`} className="hover:text-primary transition-colors">{categoryName}</Link>
              <span className="material-symbols-outlined text-xs">chevron_right</span>
            </>
          )}
          {article.location && (
            <>
              <Link href={`/${article.location}`} className="hover:text-primary transition-colors capitalize">{article.location}</Link>
              <span className="material-symbols-outlined text-xs">chevron_right</span>
            </>
          )}
          <span className="text-slate-700 font-medium truncate max-w-[300px]">{article.title}</span>
        </nav>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          {/* Main Content */}
          <article className="lg:col-span-2">
            {/* Category & Location Tags */}
            <div className="flex items-center gap-3 mb-4">
              {categoryName && (
                <Link href={`/${categorySlug}`} className="bg-primary text-white text-xs font-bold px-3 py-1 rounded uppercase tracking-wider hover:bg-red-700 transition-colors">
                  {categoryName}
                </Link>
              )}
              {article.location && (
                <Link href={`/${article.location}`} className="bg-slate-800 text-white text-xs font-bold px-3 py-1 rounded uppercase tracking-wider hover:bg-slate-700 transition-colors capitalize">
                  {article.location}
                </Link>
              )}
            </div>

            {/* Title */}
            <h1 className="text-3xl lg:text-5xl font-extrabold leading-tight tracking-tight text-slate-900 mb-4">
              {article.title}
            </h1>

            {/* Excerpt */}
            {article.excerpt && (
              <p className="text-xl text-slate-600 font-light leading-relaxed mb-6">
                {article.excerpt}
              </p>
            )}

            {/* Meta */}
            <div className="flex items-center gap-4 text-sm text-slate-500 mb-8 pb-6 border-b border-gray-200">
              <span className="flex items-center gap-1">
                <span className="material-symbols-outlined text-sm">schedule</span>
                {new Date(article.publishedAt).toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
              </span>
              <span className="flex items-center gap-1">
                <span className="material-symbols-outlined text-sm">person</span>
                Gündem Akdeniz
              </span>
            </div>

            {/* Featured Image */}
            {imageUrl && (
              <div className="relative aspect-video rounded-xl overflow-hidden mb-8 border border-gray-200 shadow-sm">
                <Image
                  src={imageUrl}
                  alt={article.title}
                  fill
                  sizes="(max-width: 768px) 100vw, 700px"
                  className="object-cover"
                  priority
                />
              </div>
            )}

            {/* Content */}
            <div className="prose prose-lg max-w-none prose-headings:font-extrabold prose-headings:tracking-tight prose-a:text-primary prose-img:rounded-xl">
              <RichTextRenderer content={article.content} />
            </div>

            {/* Tags / Share */}
            <div className="mt-10 pt-6 border-t border-gray-200 flex flex-wrap items-center gap-4">
              <span className="text-sm font-bold text-slate-700">Paylaş:</span>
              <div className="flex gap-2">
                <button className="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center hover:opacity-80 transition-opacity">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </button>
                <button className="w-10 h-10 rounded-lg bg-sky-500 text-white flex items-center justify-center hover:opacity-80 transition-opacity">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                </button>
                <button className="w-10 h-10 rounded-lg bg-green-600 text-white flex items-center justify-center hover:opacity-80 transition-opacity">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </button>
              </div>
            </div>
          </article>

          {/* ========== SIDEBAR ========== */}
          <aside className="lg:col-span-1">
            <div className="sticky top-20 space-y-8">

              {/* Reklam Alanı */}
              <AdSlot position="sidebarTop" />

              {/* En Çok Okunan */}
              <div>
                <h3 className="text-xl font-extrabold tracking-tight text-slate-900 mb-6 pb-4 border-b border-gray-200 flex items-center gap-2">
                  <span className="material-symbols-outlined text-primary">trending_up</span>
                  En Çok Okunan
                </h3>
                <div className="space-y-4">
                  {mostReadArticles.map((item, index) => (
                    <Link key={item.id} href={getArticleUrl(item.slug, item.id)} className="group flex gap-3 items-start cursor-pointer">
                      <span className={`shrink-0 w-7 h-7 rounded-full flex items-center justify-center font-extrabold text-xs ${
                        index < 3
                          ? 'bg-primary text-white'
                          : 'bg-gray-100 text-slate-500 border border-gray-200'
                      }`}>
                        {index + 1}
                      </span>
                      <div className="relative w-16 h-12 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                        <Image
                          src={getImageUrl(item.featuredImage)}
                          alt={item.title}
                          fill
                          sizes="64px"
                          className="object-cover group-hover:scale-110 transition-transform duration-300"
                        />
                      </div>
                      <div className="flex-1 min-w-0">
                        <h4 className="font-bold text-[13px] leading-snug text-slate-900 group-hover:text-primary transition-colors line-clamp-2">
                          {item.title}
                        </h4>
                        <div className="flex items-center gap-2 mt-1">
                          <span className="text-primary text-[10px] font-bold uppercase">{getCategoryName(item.category)}</span>
                          <span className="text-[10px] text-slate-400 flex items-center gap-0.5">
                            <span className="material-symbols-outlined" style={{ fontSize: '10px' }}>visibility</span>
                            {item.viewCount || 0}
                          </span>
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              </div>

              {/* İkinci Reklam Alanı */}
              <AdSlot position="sidebarBottom" />

            </div>
          </aside>
        </div>

        {/* ========== BENZER HABERLER (Yazı Altı) ========== */}
        {relatedArticles.length > 0 && (
          <section className="mt-16 pt-12 border-t border-gray-200">
            <div className="flex items-end justify-between mb-8">
              <div>
                <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Benzer Haberler</h2>
                <p className="text-slate-600 mt-1">Bu habere benzer diğer içerikler</p>
              </div>
              {categorySlug && (
                <Link href={`/${categorySlug}`} className="text-primary font-bold text-sm hover:underline flex items-center gap-1">
                  Tümünü Gör <span className="material-symbols-outlined text-sm">arrow_forward</span>
                </Link>
              )}
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {relatedArticles.map((item) => (
                <NewsCard key={item.id} article={item} />
              ))}
            </div>
          </section>
        )}
      </main>
      <Footer />
    </>
  );
}

// ===================== CITY PAGE =====================
function CityPage({ city, articles }: { city: { label: string; slug: string }; articles: Article[] }) {
  return (
    <>
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">{city.label} Haberleri</span>
        </nav>

        {/* Page Header */}
        <div className="mb-10">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">{city.label} Haberleri</h1>
          </div>
          <p className="text-slate-600 ml-5">{city.label} ve çevresinden en güncel haberler</p>
        </div>

        {/* News Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {articles.map((item) => (
            <NewsCard key={item.id} article={item} />
          ))}
        </div>

        {articles.length === 0 && (
          <div className="py-20 text-center text-slate-500 bg-gray-50 rounded-xl border border-dashed border-gray-200">
            <span className="material-symbols-outlined text-5xl text-slate-300 mb-4 block">newspaper</span>
            <p className="text-lg font-medium">Bu şehir için henüz haber eklenmemiş.</p>
          </div>
        )}
      </main>
      <Footer />
    </>
  );
}

// ===================== CATEGORY PAGE =====================
function CategoryPage({ category, articles }: { category: any; articles: Article[] }) {
  return (
    <>
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">{category.name} Haberleri</span>
        </nav>

        {/* Page Header */}
        <div className="mb-10">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">{category.name}</h1>
          </div>
          <p className="text-slate-600 ml-5">{category.name} kategorisindeki en güncel haberler</p>
        </div>

        {/* News Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {articles.map((item) => (
            <NewsCard key={item.id} article={item} />
          ))}
        </div>

        {articles.length === 0 && (
          <div className="py-20 text-center text-slate-500 bg-gray-50 rounded-xl border border-dashed border-gray-200">
            <span className="material-symbols-outlined text-5xl text-slate-300 mb-4 block">newspaper</span>
            <p className="text-lg font-medium">Bu kategoride henüz haber eklenmemiş.</p>
          </div>
        )}
      </main>
      <Footer />
    </>
  );
}

// ===================== STATIC PAGE =====================
function StaticPage({ page }: { page: { title: string; content: any } }) {
  return (
    <>
      <Header />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        <nav className="flex items-center gap-2 text-sm text-slate-500 mb-6">
          <Link href="/" className="hover:text-primary transition-colors">Anasayfa</Link>
          <span className="material-symbols-outlined text-xs">chevron_right</span>
          <span className="text-slate-700 font-medium">{page.title}</span>
        </nav>
        <div className="mb-10">
          <div className="flex items-center gap-3 mb-2">
            <span className="w-1.5 h-10 bg-primary rounded-full"></span>
            <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">{page.title}</h1>
          </div>
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-8 md:p-12 prose prose-slate max-w-none prose-headings:font-extrabold prose-headings:tracking-tight prose-a:text-primary prose-img:rounded-xl">
          <RichTextRenderer content={page.content} />
        </div>
      </main>
      <Footer />
    </>
  );
}

// ===================== SHARED NEWS CARD =====================
function NewsCard({ article }: { article: Article }) {
  return (
    <Link href={getArticleUrl(article.slug, article.id)} className="group cursor-pointer block">
      <div className="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm">
        <Image
          src={getImageUrl(article.featuredImage)}
          alt={article.title}
          fill
          sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 25vw"
          className="object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute top-3 left-3 bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase z-10">
          {getCategoryName(article.category)}
        </div>
      </div>
      <h4 className="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
        {article.title}
      </h4>
      <p className="text-slate-600 text-sm line-clamp-2">
        {article.excerpt}
      </p>
      <div className="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
        <span className="capitalize">{article.location || 'Genel'}</span>
        <span>{new Date(article.publishedAt).toLocaleDateString('tr-TR')}</span>
      </div>
    </Link>
  );
}

// ===================== RICH TEXT RENDERER =====================
function RichTextRenderer({ content }: { content: any }) {
  if (!content) return null;

  // Lexical editor stores content as JSON
  if (content.root && content.root.children) {
    return <LexicalRenderer nodes={content.root.children} />;
  }

  // Fallback: if content is a string
  if (typeof content === 'string') {
    return <div dangerouslySetInnerHTML={{ __html: content }} />;
  }

  return null;
}

function LexicalRenderer({ nodes }: { nodes: any[] }) {
  return (
    <>
      {nodes.map((node: any, i: number) => {
        switch (node.type) {
          case 'heading': {
            const level = node.tag || 'h2';
            return React.createElement(level, { key: i }, <LexicalRenderer nodes={node.children || []} />);
          }
          case 'paragraph':
            return <p key={i}><LexicalRenderer nodes={node.children || []} /></p>;
          case 'text':
            let text = <>{node.text}</>;
            if (node.format & 1) text = <strong>{text}</strong>;
            if (node.format & 2) text = <em>{text}</em>;
            if (node.format & 4) text = <s>{text}</s>;
            if (node.format & 8) text = <u>{text}</u>;
            if (node.format & 16) text = <code>{text}</code>;
            return <span key={i}>{text}</span>;
          case 'list': {
            const ListTag = node.listType === 'number' ? 'ol' : 'ul';
            return <ListTag key={i}><LexicalRenderer nodes={node.children || []} /></ListTag>;
          }
          case 'listitem':
            return <li key={i}><LexicalRenderer nodes={node.children || []} /></li>;
          case 'link':
            return (
              <a key={i} href={node.fields?.url || '#'} target={node.fields?.newTab ? '_blank' : undefined} rel={node.fields?.newTab ? 'noopener noreferrer' : undefined}>
                <LexicalRenderer nodes={node.children || []} />
              </a>
            );
          case 'quote':
            return <blockquote key={i}><LexicalRenderer nodes={node.children || []} /></blockquote>;
          case 'upload': {
            const imgUrl = node.value?.url || '';
            const alt = node.value?.alt || '';
            return imgUrl ? <div key={i} className="relative w-full aspect-video"><Image src={imgUrl} alt={alt} fill sizes="(max-width: 768px) 100vw, 700px" className="rounded-xl object-cover" /></div> : null;
          }
          case 'linebreak':
            return <br key={i} />;
          default:
            if (node.children) return <LexicalRenderer key={i} nodes={node.children} />;
            return null;
        }
      })}
    </>
  );
}
