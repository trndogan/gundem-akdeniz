export interface Media {
  id: string;
  url?: string;
  alt?: string;
  filename?: string;
  mimeType?: string;
  filesize?: number;
  width?: number;
  height?: number;
}

export interface Category {
  id: string;
  name: string;
  slug?: string;
}

export interface Article {
  id: string;
  title: string;
  slug?: string;
  category: Category | string;
  featuredImage: Media | string;
  excerpt?: string;
  content?: any;
  publishedAt: string;
  location?: 'antalya' | 'mersin' | 'adana' | 'hatay' | 'isparta' | 'burdur' | 'osmaniye' | 'kahramanmaras';
  isFeatured?: boolean;
  viewCount?: number;
  createdAt: string;
  updatedAt: string;
}
