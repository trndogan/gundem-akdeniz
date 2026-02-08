import type { GlobalConfig } from 'payload'

export const MainMenu: GlobalConfig = {
  slug: 'main-menu',
  label: 'Ana Menü',
  access: {
    read: () => true,
  },
  fields: [
    {
      name: 'menuItems',
      type: 'array',
      label: 'Menü Öğeleri',
      fields: [
        {
          name: 'label',
          type: 'text',
          label: 'Menü Adı',
          required: true,
        },
        {
          name: 'type',
          type: 'select',
          label: 'Tür',
          defaultValue: 'link',
          options: [
            { label: 'Link', value: 'link' },
            { label: 'Kategori (Otomatik)', value: 'category' },
            { label: 'Mega Menü (Şehirler)', value: 'megamenu' },
          ],
        },
        {
          name: 'url',
          type: 'text',
          label: 'URL',
          admin: {
            condition: (_, siblingData) => siblingData?.type === 'link',
            description: 'Örn: /gundem veya https://...',
          },
        },
        {
          name: 'category',
          type: 'relationship',
          relationTo: 'categories',
          label: 'Kategori',
          admin: {
            condition: (_, siblingData) => siblingData?.type === 'category',
          },
        },
        {
          name: 'megaMenuItems',
          type: 'array',
          label: 'Alt Menü Öğeleri',
          admin: {
            condition: (_, siblingData) => siblingData?.type === 'megamenu',
          },
          fields: [
            {
              name: 'label',
              type: 'text',
              label: 'Etiket',
              required: true,
            },
            {
              name: 'url',
              type: 'text',
              label: 'URL',
              required: true,
            },
          ],
        },
        {
          name: 'openInNewTab',
          type: 'checkbox',
          label: 'Yeni sekmede aç',
          defaultValue: false,
        },
      ],
    },
  ],
}
