import type { GlobalConfig } from 'payload'

export const SiteSettings: GlobalConfig = {
  slug: 'site-settings',
  label: 'Site Ayarları',
  access: {
    read: () => true,
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: 'Genel',
          fields: [
            {
              name: 'siteName',
              type: 'text',
              label: 'Site Adı',
              defaultValue: 'Gündem Akdeniz',
            },
            {
              name: 'siteDescription',
              type: 'textarea',
              label: 'Site Açıklaması',
              defaultValue: "Akdeniz Bölgesi'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.",
            },
            {
              name: 'logo',
              type: 'upload',
              relationTo: 'media',
              label: 'Logo',
            },
            {
              name: 'favicon',
              type: 'upload',
              relationTo: 'media',
              label: 'Favicon',
            },
          ],
        },
        {
          label: 'SEO & Doğrulama',
          fields: [
            {
              name: 'googleVerification',
              type: 'text',
              label: 'Google Site Doğrulama Kodu',
              admin: {
                description: 'Google Search Console doğrulama meta tag content değeri',
              },
            },
            {
              name: 'yandexVerification',
              type: 'text',
              label: 'Yandex Doğrulama Kodu',
              admin: {
                description: 'Yandex Webmaster doğrulama meta tag content değeri',
              },
            },
            {
              name: 'bingVerification',
              type: 'text',
              label: 'Bing Doğrulama Kodu',
              admin: {
                description: 'Bing Webmaster doğrulama meta tag content değeri',
              },
            },
            {
              name: 'googleAnalyticsId',
              type: 'text',
              label: 'Google Analytics ID',
              admin: {
                description: 'Örn: G-XXXXXXXXXX',
              },
            },
            {
              name: 'googleTagManagerId',
              type: 'text',
              label: 'Google Tag Manager ID',
              admin: {
                description: 'Örn: GTM-XXXXXXX',
              },
            },
            {
              name: 'customHeadCode',
              type: 'code',
              label: 'Özel Head Kodu',
              admin: {
                language: 'html',
                description: '<head> içine eklenecek özel HTML kodu (meta taglar, scriptler vb.)',
              },
            },
          ],
        },
        {
          label: 'Sosyal Medya',
          fields: [
            {
              name: 'socialLinks',
              type: 'group',
              label: 'Sosyal Medya Linkleri',
              fields: [
                { name: 'facebook', type: 'text', label: 'Facebook URL' },
                { name: 'twitter', type: 'text', label: 'Twitter/X URL' },
                { name: 'instagram', type: 'text', label: 'Instagram URL' },
                { name: 'youtube', type: 'text', label: 'YouTube URL' },
                { name: 'tiktok', type: 'text', label: 'TikTok URL' },
                { name: 'linkedin', type: 'text', label: 'LinkedIn URL' },
              ],
            },
          ],
        },
        {
          label: 'İletişim',
          fields: [
            { name: 'email', type: 'email', label: 'E-posta' },
            { name: 'phone', type: 'text', label: 'Telefon' },
            { name: 'address', type: 'textarea', label: 'Adres' },
          ],
        },
      ],
    },
  ],
}
