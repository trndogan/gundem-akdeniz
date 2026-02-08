import type { NextConfig } from "next";
import { withPayload } from "@payloadcms/next/withPayload";

const nextConfig: NextConfig = {
  images: {
    formats: ['image/avif', 'image/webp'],
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'images.unsplash.com',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
      },
      {
        protocol: 'https',
        hostname: 'gundemakdeniz.com',
      }
    ],
  },
  async rewrites() {
    return [
      { source: '/sitemap.xml', destination: '/api/sitemaps/index' },
      { source: '/post-sitemap:page(\\d+).xml', destination: '/api/sitemaps/posts/:page' },
      { source: '/page-sitemap.xml', destination: '/api/sitemaps/pages' },
      { source: '/category-sitemap.xml', destination: '/api/sitemaps/categories' },
      { source: '/service-sitemap.xml', destination: '/api/sitemaps/services' },
      { source: '/feed', destination: '/api/feed' },
      { source: '/rss.xml', destination: '/api/feed' },
    ]
  },
};

export default withPayload(nextConfig);
