import type { CollectionConfig } from 'payload'

export const Articles: CollectionConfig = {
  slug: 'articles',
  labels: { singular: 'Haber', plural: 'Haberler' },
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'category', 'publishedAt', 'status'],
  },
  access: {
    read: () => true,
  },
  versions: {
    drafts: true,
  },
  fields: [
    {
      name: 'title',
      type: 'text',
      required: true,
    },
    {
      name: 'slug',
      type: 'text',
      unique: true,
      index: true,
      admin: {
        position: 'sidebar',
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
      name: 'category',
      type: 'relationship',
      relationTo: 'categories',
      required: true,
      admin: {
        position: 'sidebar',
      },
    },
    {
      name: 'featuredImage',
      type: 'upload',
      relationTo: 'media',
      required: true,
    },
    {
      name: 'excerpt',
      type: 'textarea',
      label: 'Kısa Özet',
    },
    {
      name: 'content',
      type: 'richText',
      required: true,
    },
    {
      name: 'publishedAt',
      type: 'date',
      admin: {
        position: 'sidebar',
        date: {
          pickerAppearance: 'dayAndTime',
        },
      },
      hooks: {
        beforeValidate: [
          ({ value, operation }) => {
            if (!value && (operation === 'create' || operation === 'update')) {
              return new Date().toISOString()
            }
            return value
          },
        ],
      },
    },
    {
        name: 'isFeatured',
        type: 'checkbox',
        label: 'Manşet Haber',
        defaultValue: false,
        admin: {
            position: 'sidebar'
        }
    },
    {
        name: 'viewCount',
        type: 'number',
        label: 'Görüntülenme',
        defaultValue: 0,
        admin: {
            position: 'sidebar',
            readOnly: true,
        },
    },
    {
        name: 'location',
        type: 'select',
        options: [
            { label: 'Antalya', value: 'antalya' },
            { label: 'Mersin', value: 'mersin' },
            { label: 'Adana', value: 'adana' },
            { label: 'Hatay', value: 'hatay' },
            { label: 'Isparta', value: 'isparta' },
            { label: 'Burdur', value: 'burdur' },
            { label: 'Osmaniye', value: 'osmaniye' },
            { label: 'K.Maraş', value: 'kahramanmaras' },
        ],
        admin: {
            position: 'sidebar'
        }
    }
  ],
}
