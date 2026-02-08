import type { CollectionConfig } from 'payload'

export const Pages: CollectionConfig = {
  slug: 'pages',
  labels: { singular: 'Sayfa', plural: 'Sayfalar' },
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'slug', 'updatedAt'],
  },
  access: {
    read: () => true,
  },
  fields: [
    {
      name: 'title',
      type: 'text',
      label: 'Sayfa Başlığı',
      required: true,
    },
    {
      name: 'slug',
      type: 'text',
      label: 'URL Slug',
      unique: true,
      index: true,
      admin: {
        position: 'sidebar',
        description: 'Örn: hakkimizda, iletisim, gizlilik-politikasi',
      },
      hooks: {
        beforeValidate: [
          ({ value, data }) => {
            if (!value && data?.title) {
              const turkishMap: Record<string, string> = {
                'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u',
                'Ç': 'c', 'Ğ': 'g', 'İ': 'i', 'Ö': 'o', 'Ş': 's', 'Ü': 'u',
              };
              return data.title
                .split('')
                .map((char: string) => turkishMap[char] || char)
                .join('')
                .toLowerCase()
                .replace(/&/g, '-ve-')
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9-]/g, '')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '')
            }
            return value
          },
        ],
      },
    },
    {
      name: 'content',
      type: 'richText',
      label: 'Sayfa İçeriği',
      required: true,
    },
    {
      name: 'metaDescription',
      type: 'textarea',
      label: 'SEO Açıklama',
      admin: {
        position: 'sidebar',
        description: 'Arama motorları için kısa açıklama',
      },
    },
  ],
}
