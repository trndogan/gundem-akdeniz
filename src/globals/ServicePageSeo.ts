import type { GlobalConfig } from 'payload'

const CITIES = [
  { label: 'Antalya', value: 'antalya' },
  { label: 'Mersin', value: 'mersin' },
  { label: 'Adana', value: 'adana' },
  { label: 'Hatay', value: 'hatay' },
  { label: 'Isparta', value: 'isparta' },
  { label: 'Burdur', value: 'burdur' },
  { label: 'Osmaniye', value: 'osmaniye' },
  { label: 'Kahramanmaraş', value: 'kahramanmaras' },
]

function createCitySeoFields(prefix: string, sectionLabel: string) {
  return [
    {
      name: `${prefix}General`,
      label: `${sectionLabel} - Genel Sayfa`,
      type: 'group' as const,
      fields: [
        {
          name: 'seoText',
          label: 'SEO Metin (Ana Sayfa)',
          type: 'richText' as const,
          admin: {
            description: `${sectionLabel} ana sayfasının altında görünecek SEO metni`,
          },
        },
      ],
    },
    ...CITIES.map(city => ({
      name: `${prefix}${city.value.charAt(0).toUpperCase() + city.value.slice(1)}`,
      label: `${sectionLabel} - ${city.label}`,
      type: 'group' as const,
      fields: [
        {
          name: 'seoText',
          label: `${city.label} SEO Metin`,
          type: 'richText' as const,
          admin: {
            description: `${city.label} ${sectionLabel.toLowerCase()} sayfasının altında görünecek SEO metni`,
          },
        },
      ],
    })),
  ]
}

export const ServicePageSeo: GlobalConfig = {
  slug: 'service-page-seo',
  label: 'Servis Sayfaları SEO',
  admin: {
    description: 'Nöbetçi Eczane ve Hava Durumu sayfaları için il bazlı SEO metinleri',
    group: 'SEO',
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: 'Nöbetçi Eczane',
          fields: createCitySeoFields('pharmacy', 'Nöbetçi Eczane'),
        },
        {
          label: 'Hava Durumu',
          fields: createCitySeoFields('weather', 'Hava Durumu'),
        },
      ],
    },
  ],
}
