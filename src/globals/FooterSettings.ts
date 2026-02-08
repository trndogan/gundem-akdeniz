import type { GlobalConfig } from 'payload'

export const FooterSettings: GlobalConfig = {
  slug: 'footer-settings',
  label: 'Footer Ayarları',
  access: {
    read: () => true,
  },
  fields: [
    {
      name: 'description',
      type: 'textarea',
      label: 'Footer Açıklama',
      defaultValue: "Akdeniz Bölgesi'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.",
    },
    {
      name: 'columns',
      type: 'array',
      label: 'Footer Sütunları',
      maxRows: 4,
      fields: [
        {
          name: 'title',
          type: 'text',
          label: 'Sütun Başlığı',
          required: true,
        },
        {
          name: 'links',
          type: 'array',
          label: 'Linkler',
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
            {
              name: 'openInNewTab',
              type: 'checkbox',
              label: 'Yeni sekmede aç',
              defaultValue: false,
            },
          ],
        },
      ],
    },
    {
      name: 'serviceBanners',
      type: 'array',
      label: 'Servis Bannerları',
      maxRows: 6,
      fields: [
        {
          name: 'icon',
          type: 'text',
          label: 'Material Icon Adı',
          required: true,
          admin: {
            description: 'Google Material Symbols icon adı (örn: wb_sunny, local_pharmacy)',
          },
        },
        {
          name: 'title',
          type: 'text',
          label: 'Başlık',
          required: true,
        },
        {
          name: 'subtitle',
          type: 'text',
          label: 'Alt Başlık',
        },
        {
          name: 'url',
          type: 'text',
          label: 'URL',
          defaultValue: '#',
        },
      ],
    },
    {
      name: 'copyrightText',
      type: 'text',
      label: 'Telif Hakkı Metni',
      defaultValue: '© 2026 GundemAkdeniz.com - Tüm hakları saklıdır.',
    },
  ],
}
