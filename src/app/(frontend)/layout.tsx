import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "../globals.css";
import { getPayloadClient } from "@/utils/payload";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  display: "swap",
});

export async function generateMetadata(): Promise<Metadata> {
  let siteSettings: any = null;
  try {
    const payload = await getPayloadClient();
    siteSettings = await payload.findGlobal({ slug: 'site-settings' });
  } catch { /* fallback */ }

  const siteName = siteSettings?.siteName || 'Gündem Akdeniz';
  const siteDesc = siteSettings?.siteDescription || "Akdeniz Bölgesi'nin öncü haber platformu. Antalya, Mersin, Adana, Hatay ve tüm Akdeniz'den güncel, tarafsız ve güvenilir haberler.";

  const verification: Record<string, string> = {};
  if (siteSettings?.googleVerification) verification.google = siteSettings.googleVerification;
  if (siteSettings?.yandexVerification) verification.yandex = siteSettings.yandexVerification;
  if (siteSettings?.bingVerification) verification.other = siteSettings.bingVerification;

  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://gundemakdeniz.com';

  return {
    metadataBase: new URL(baseUrl),
    title: {
      default: `${siteName} - Akdeniz'in Haber Portalı`,
      template: `%s | ${siteName}`,
    },
    description: siteDesc,
    keywords: ["akdeniz haber", "antalya haber", "mersin haber", "adana haber", "hatay haber", "isparta haber", "burdur haber", "osmaniye haber", "kahramanmaraş haber", "gündem", "ekonomi", "turizm", "spor"],
    authors: [{ name: `${siteName} Ekibi` }],
    creator: siteName,
    publisher: siteName,
    robots: { index: true, follow: true, 'max-image-preview': 'large' as const, 'max-snippet': -1, 'max-video-preview': -1 },
    alternates: { canonical: baseUrl },
    verification,
    openGraph: {
      type: "website",
      locale: "tr_TR",
      url: baseUrl,
      siteName,
      title: `${siteName} - Akdeniz'in Haber Portalı`,
      description: siteDesc,
      images: [{ url: "/og-image.jpg", width: 1200, height: 630, alt: siteName }],
    },
    twitter: {
      card: "summary_large_image",
      title: `${siteName} - Akdeniz'in Haber Portalı`,
      description: siteDesc,
      creator: "@gundemakdeniz",
    },
  };
}

export default async function FrontendLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  let siteSettings: any = null;
  try {
    const payload = await getPayloadClient();
    siteSettings = await payload.findGlobal({ slug: 'site-settings' });
  } catch { /* fallback */ }

  const gaId = siteSettings?.googleAnalyticsId;
  const gtmId = siteSettings?.googleTagManagerId;
  const customHead = siteSettings?.customHeadCode;

  return (
    <html lang="tr" className={inter.variable}>
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" as="style" />
        <script dangerouslySetInnerHTML={{ __html: `
          var l=document.createElement('link');l.rel='stylesheet';
          l.href='https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap';
          document.head.appendChild(l);
        ` }} />
        <link rel="alternate" type="application/rss+xml" title="Gündem Akdeniz RSS" href="/feed" />
        {gaId && (
          <>
            <script async src={`https://www.googletagmanager.com/gtag/js?id=${gaId}`} />
            <script dangerouslySetInnerHTML={{ __html: `window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${gaId}');` }} />
          </>
        )}
        {gtmId && (
          <script dangerouslySetInnerHTML={{ __html: `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${gtmId}');` }} />
        )}
        {customHead && (
          <script dangerouslySetInnerHTML={{ __html: customHead }} />
        )}
      </head>
      <body className="bg-gray-50 font-display text-slate-900 antialiased overflow-x-hidden">
        {gtmId && (
          <noscript>
            <iframe src={`https://www.googletagmanager.com/ns.html?id=${gtmId}`} height="0" width="0" style={{ display: 'none', visibility: 'hidden' }} />
          </noscript>
        )}
        {children}
      </body>
    </html>
  );
}
