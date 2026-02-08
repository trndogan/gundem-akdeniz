import type { GlobalConfig } from 'payload'

export const Advertisements: GlobalConfig = {
  slug: 'advertisements',
  label: 'Reklam Yönetimi',
  access: {
    read: () => true,
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: 'Sidebar Reklamları',
          fields: [
            {
              name: 'sidebarTop',
              type: 'group',
              label: 'Sidebar Üst (300x250)',
              fields: [
                { name: 'enabled', type: 'checkbox', label: 'Aktif', defaultValue: false },
                { name: 'code', type: 'code', label: 'Reklam Kodu', admin: { language: 'html' } },
                { name: 'image', type: 'upload', relationTo: 'media', label: 'Görsel (Kod yoksa)' },
                { name: 'url', type: 'text', label: 'Link URL (Görsel için)' },
              ],
            },
            {
              name: 'sidebarBottom',
              type: 'group',
              label: 'Sidebar Alt (300x600)',
              fields: [
                { name: 'enabled', type: 'checkbox', label: 'Aktif', defaultValue: false },
                { name: 'code', type: 'code', label: 'Reklam Kodu', admin: { language: 'html' } },
                { name: 'image', type: 'upload', relationTo: 'media', label: 'Görsel (Kod yoksa)' },
                { name: 'url', type: 'text', label: 'Link URL (Görsel için)' },
              ],
            },
          ],
        },
        {
          label: 'Yazı İçi Reklamlar',
          fields: [
            {
              name: 'articleMiddle',
              type: 'group',
              label: 'Yazı Ortası',
              fields: [
                { name: 'enabled', type: 'checkbox', label: 'Aktif', defaultValue: false },
                { name: 'code', type: 'code', label: 'Reklam Kodu', admin: { language: 'html' } },
                { name: 'image', type: 'upload', relationTo: 'media', label: 'Görsel (Kod yoksa)' },
                { name: 'url', type: 'text', label: 'Link URL (Görsel için)' },
              ],
            },
            {
              name: 'articleBottom',
              type: 'group',
              label: 'Yazı Altı',
              fields: [
                { name: 'enabled', type: 'checkbox', label: 'Aktif', defaultValue: false },
                { name: 'code', type: 'code', label: 'Reklam Kodu', admin: { language: 'html' } },
                { name: 'image', type: 'upload', relationTo: 'media', label: 'Görsel (Kod yoksa)' },
                { name: 'url', type: 'text', label: 'Link URL (Görsel için)' },
              ],
            },
          ],
        },
        {
          label: 'Anasayfa Reklamları',
          fields: [
            {
              name: 'homepageTop',
              type: 'group',
              label: 'Anasayfa Üst Banner (728x90)',
              fields: [
                { name: 'enabled', type: 'checkbox', label: 'Aktif', defaultValue: false },
                { name: 'code', type: 'code', label: 'Reklam Kodu', admin: { language: 'html' } },
                { name: 'image', type: 'upload', relationTo: 'media', label: 'Görsel (Kod yoksa)' },
                { name: 'url', type: 'text', label: 'Link URL (Görsel için)' },
              ],
            },
            {
              name: 'homepageMiddle',
              type: 'group',
              label: 'Anasayfa Orta Banner',
              fields: [
                { name: 'enabled', type: 'checkbox', label: 'Aktif', defaultValue: false },
                { name: 'code', type: 'code', label: 'Reklam Kodu', admin: { language: 'html' } },
                { name: 'image', type: 'upload', relationTo: 'media', label: 'Görsel (Kod yoksa)' },
                { name: 'url', type: 'text', label: 'Link URL (Görsel için)' },
              ],
            },
          ],
        },
      ],
    },
  ],
}
