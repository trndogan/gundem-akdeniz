export const CITIES = [
  { label: 'Antalya', slug: 'antalya', value: 'antalya' },
  { label: 'Mersin', slug: 'mersin', value: 'mersin' },
  { label: 'Adana', slug: 'adana', value: 'adana' },
  { label: 'Hatay', slug: 'hatay', value: 'hatay' },
  { label: 'Isparta', slug: 'isparta', value: 'isparta' },
  { label: 'Burdur', slug: 'burdur', value: 'burdur' },
  { label: 'Osmaniye', slug: 'osmaniye', value: 'osmaniye' },
  { label: 'K.Maraş', slug: 'kahramanmaras', value: 'kahramanmaras' },
] as const;

export const CITY_SLUGS = CITIES.map(c => c.slug);

export function getCityBySlug(slug: string) {
  return CITIES.find(c => c.slug === slug);
}

export function getArticleUrl(slug: string | undefined, id: string | number): string {
  if (slug) return `/${slug}-${id}`;
  return `/haber-${id}`;
}

export function getImageUrl(image: any): string {
  if (!image) return '';
  if (typeof image === 'string') return '';
  return image.url || '';
}

export function getCategoryName(category: any): string {
  if (!category) return '';
  if (typeof category === 'string') return '';
  return category.name || '';
}

export function getCategorySlug(category: any): string {
  if (!category) return '';
  if (typeof category === 'string') return '';
  return category.slug || '';
}
