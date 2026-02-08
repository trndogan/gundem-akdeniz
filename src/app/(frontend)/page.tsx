import { Header } from "@/components/layout/Header";
import { BreakingNews } from "@/components/home/BreakingNews";
import { Hero } from "@/components/home/Hero";
import { RegionalHub } from "@/components/home/RegionalHub";
import { LatestNews } from "@/components/home/LatestNews";
import { Footer } from "@/components/layout/Footer";
import { getPayloadClient } from "@/utils/payload";
import { Article } from "@/types";
import { AdSlot } from "@/components/AdSlot";

// Revalidate every 60 seconds (ISR)
export const revalidate = 60;

export default async function Home() {
  const payload = await getPayloadClient();

  // Fetch Featured Articles for Hero
  const heroArticles = await payload.find({
    collection: 'articles',
    where: { 
      isFeatured: { equals: true } 
    },
    limit: 5,
    sort: '-publishedAt',
    depth: 1,
  });

  // Fetch Latest Articles
  const latestArticles = await payload.find({
    collection: 'articles',
    limit: 8,
    sort: '-publishedAt',
    depth: 1,
  });

  // Fetch Regional Articles (Get a larger batch to filter on client)
  // In a real app, you might want to fetch this via API on client side upon tab change
  // or fetch per category/city using server components
  const regionalArticles = await payload.find({
    collection: 'articles',
    limit: 50,
    sort: '-publishedAt',
    depth: 1,
  });

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'NewsMediaOrganization',
    name: 'Gündem Akdeniz',
    url: 'https://gundemakdeniz.com',
    logo: {
      '@type': 'ImageObject',
      url: 'https://gundemakdeniz.com/logo.png',
      width: 112,
      height: 112
    },
    sameAs: [
      'https://twitter.com/gundemakdeniz',
      'https://facebook.com/gundemakdeniz',
      'https://instagram.com/gundemakdeniz'
    ],
    address: {
      '@type': 'PostalAddress',
      addressLocality: 'Antalya',
      addressRegion: 'Antalya',
      addressCountry: 'TR'
    }
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <Header />
      <BreakingNews articles={latestArticles.docs as unknown as Article[]} />
      <main className="max-w-[1440px] mx-auto px-6 py-8">
        <Hero articles={heroArticles.docs as unknown as Article[]} />
        <AdSlot position="homepageTop" />
        <RegionalHub articles={regionalArticles.docs as unknown as Article[]} />
        <AdSlot position="homepageMiddle" />
        <LatestNews articles={latestArticles.docs as unknown as Article[]} />
      </main>
      <Footer />
    </>
  );
}
