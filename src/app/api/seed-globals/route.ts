import { getPayload } from 'payload'
import config from '../../../payload.config'
import { NextResponse } from 'next/server'

export const GET = async () => {
  const payload = await getPayload({ config })

  try {
    // 1. Site Settings
    await payload.updateGlobal({
      slug: 'site-settings',
      data: {
        siteName: 'Gündem Akdeniz',
        siteDescription: "Akdeniz Bölgesi'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.",
        socialLinks: {
          facebook: 'https://facebook.com/gundemakdeniz',
          twitter: 'https://twitter.com/gundemakdeniz',
          instagram: 'https://instagram.com/gundemakdeniz',
        },
        email: 'info@gundemakdeniz.com',
      },
    })

    // 2. Main Menu
    await payload.updateGlobal({
      slug: 'main-menu',
      data: {
        menuItems: [
          { label: 'Gündem', type: 'link', url: '/gundem' },
          {
            label: 'Şehirler',
            type: 'megamenu',
            megaMenuItems: [
              { label: 'Antalya', url: '/antalya' },
              { label: 'Mersin', url: '/mersin' },
              { label: 'Adana', url: '/adana' },
              { label: 'Hatay', url: '/hatay' },
              { label: 'Isparta', url: '/isparta' },
              { label: 'Burdur', url: '/burdur' },
              { label: 'Osmaniye', url: '/osmaniye' },
              { label: 'K.Maraş', url: '/kahramanmaras' },
            ],
          },
          { label: 'Ekonomi', type: 'link', url: '/ekonomi' },
          { label: 'Turizm', type: 'link', url: '/turizm' },
          { label: 'Spor', type: 'link', url: '/spor' },
          { label: 'Teknoloji', type: 'link', url: '/teknoloji' },
        ],
      },
    })

    // 3. Footer Settings
    await payload.updateGlobal({
      slug: 'footer-settings',
      data: {
        description: "Akdeniz Bölgesi'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.",
        columns: [
          {
            title: 'Bölgeler',
            links: [
              { label: 'Antalya Haberleri', url: '/antalya' },
              { label: 'Mersin Haberleri', url: '/mersin' },
              { label: 'Adana Haberleri', url: '/adana' },
              { label: 'Hatay Haberleri', url: '/hatay' },
              { label: 'Isparta Haberleri', url: '/isparta' },
              { label: 'Burdur Haberleri', url: '/burdur' },
              { label: 'Osmaniye Haberleri', url: '/osmaniye' },
              { label: 'K.Maraş Haberleri', url: '/kahramanmaras' },
            ],
          },
          {
            title: 'Kategoriler',
            links: [
              { label: 'Gündem', url: '/gundem' },
              { label: 'Ekonomi', url: '/ekonomi' },
              { label: 'Turizm', url: '/turizm' },
              { label: 'Kültür', url: '/kultur' },
              { label: 'Spor', url: '/spor' },
              { label: 'Teknoloji', url: '/teknoloji' },
              { label: 'Girişim', url: '/girisim' },
              { label: 'Denizcilik', url: '/denizcilik' },
            ],
          },
          {
            title: 'Kurumsal',
            links: [
              { label: 'Hakkımızda', url: '/hakkimizda' },
              { label: 'İletişim', url: '/iletisim' },
              { label: 'Reklam', url: '/reklam' },
              { label: 'Künye', url: '/kunye' },
              { label: 'Gizlilik Politikası', url: '/gizlilik-politikasi' },
              { label: 'Kullanım Koşulları', url: '/kullanim-kosullari' },
              { label: 'Çerez Politikası', url: '/cerez-politikasi' },
              { label: 'RSS', url: '/rss' },
            ],
          },
        ],
        serviceBanners: [
          { icon: 'wb_sunny', title: 'Hava Durumu', subtitle: 'İlçe ilçe detaylı', url: '#' },
          { icon: 'local_pharmacy', title: 'Nöbetçi Eczane', subtitle: 'Canlı listeler', url: '/nobetci-eczane' },
          { icon: 'sentiment_dissatisfied', title: 'Vefat Haberleri', subtitle: 'Taziye ilanları', url: '#' },
          { icon: 'work', title: 'İş İlanları', subtitle: 'Bölgesel fırsatlar', url: '#' },
        ],
        copyrightText: '© 2026 GundemAkdeniz.com - Tüm hakları saklıdır.',
      },
    })

    // 4. Advertisements (placeholder defaults)
    await payload.updateGlobal({
      slug: 'advertisements',
      data: {
        sidebarTop: { enabled: false },
        sidebarBottom: { enabled: false },
        articleMiddle: { enabled: false },
        articleBottom: { enabled: false },
        homepageTop: { enabled: false },
        homepageMiddle: { enabled: false },
      },
    })

    // 5. Static Pages
    const staticPages = [
      {
        title: 'Hakkımızda',
        slug: 'hakkimizda',
        metaDescription: 'Gündem Akdeniz hakkında bilgi edinin.',
        content: {
          root: {
            type: 'root',
            children: [
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Gündem Akdeniz Hakkında' }] },
              { type: 'paragraph', children: [{ type: 'text', text: "Gündem Akdeniz, Akdeniz Bölgesi'nin öncü dijital haber platformudur. Antalya, Mersin, Adana, Hatay, Isparta, Burdur, Osmaniye ve Kahramanmaraş başta olmak üzere bölgedeki tüm gelişmeleri güncel, tarafsız ve güvenilir bir şekilde okuyucularına ulaştırmayı hedeflemektedir." }] },
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Misyonumuz' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Akdeniz Bölgesi halkını doğru ve hızlı bir şekilde bilgilendirmek, bölgenin ekonomik, sosyal ve kültürel gelişimine katkıda bulunmaktır.' }] },
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Vizyonumuz' }] },
              { type: 'paragraph', children: [{ type: 'text', text: "Akdeniz Bölgesi'nin en güvenilir ve en çok okunan dijital haber platformu olmaktır." }] },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1,
          },
        },
      },
      {
        title: 'İletişim',
        slug: 'iletisim',
        metaDescription: 'Gündem Akdeniz iletişim bilgileri.',
        content: {
          root: {
            type: 'root',
            children: [
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Bize Ulaşın' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Haber, reklam, iş birliği ve diğer konularda bizimle iletişime geçebilirsiniz.' }] },
              { type: 'paragraph', children: [
                { type: 'text', text: 'E-posta: ', format: 1 },
                { type: 'text', text: 'info@gundemakdeniz.com' },
              ] },
              { type: 'paragraph', children: [
                { type: 'text', text: 'Adres: ', format: 1 },
                { type: 'text', text: 'Antalya, Türkiye' },
              ] },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1,
          },
        },
      },
      {
        title: 'Reklam',
        slug: 'reklam',
        metaDescription: 'Gündem Akdeniz reklam seçenekleri.',
        content: {
          root: {
            type: 'root',
            children: [
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Reklam Verin' }] },
              { type: 'paragraph', children: [{ type: 'text', text: "Gündem Akdeniz'de reklam vererek Akdeniz Bölgesi'ndeki geniş okuyucu kitlesine ulaşabilirsiniz. Banner, yazı içi reklam ve sponsorlu içerik seçeneklerimiz mevcuttur." }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Reklam talepleriniz için: reklam@gundemakdeniz.com' }] },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1,
          },
        },
      },
      {
        title: 'Künye',
        slug: 'kunye',
        metaDescription: 'Gündem Akdeniz künye bilgileri.',
        content: {
          root: {
            type: 'root',
            children: [
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Künye' }] },
              { type: 'paragraph', children: [
                { type: 'text', text: 'Yayın Sahibi: ', format: 1 },
                { type: 'text', text: 'Gündem Akdeniz Medya' },
              ] },
              { type: 'paragraph', children: [
                { type: 'text', text: 'Sorumlu Yazı İşleri Müdürü: ', format: 1 },
                { type: 'text', text: '(Admin panelden düzenleyin)' },
              ] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Bu sayfa admin panelden düzenlenebilir.' }] },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1,
          },
        },
      },
      {
        title: 'Gizlilik Politikası',
        slug: 'gizlilik-politikasi',
        metaDescription: 'Gündem Akdeniz gizlilik politikası.',
        content: {
          root: {
            type: 'root',
            children: [
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Gizlilik Politikası' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Gündem Akdeniz olarak kişisel verilerinizin korunmasına önem veriyoruz. Bu politika, web sitemizi ziyaret ettiğinizde toplanan bilgilerin nasıl kullanıldığını açıklamaktadır.' }] },
              { type: 'heading', tag: 'h3', children: [{ type: 'text', text: 'Toplanan Bilgiler' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Sitemizi ziyaret ettiğinizde IP adresiniz, tarayıcı bilgileriniz ve ziyaret ettiğiniz sayfalar gibi bilgiler otomatik olarak toplanabilir. Bu bilgiler site performansını iyileştirmek amacıyla kullanılır.' }] },
              { type: 'heading', tag: 'h3', children: [{ type: 'text', text: 'Çerezler' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Sitemiz, deneyiminizi iyileştirmek için çerezler kullanmaktadır. Tarayıcı ayarlarınızdan çerezleri devre dışı bırakabilirsiniz.' }] },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1,
          },
        },
      },
      {
        title: 'Kullanım Koşulları',
        slug: 'kullanim-kosullari',
        metaDescription: 'Gündem Akdeniz kullanım koşulları.',
        content: {
          root: {
            type: 'root',
            children: [
              { type: 'heading', tag: 'h2', children: [{ type: 'text', text: 'Kullanım Koşulları' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Bu web sitesini kullanarak aşağıdaki koşulları kabul etmiş sayılırsınız.' }] },
              { type: 'heading', tag: 'h3', children: [{ type: 'text', text: 'İçerik Hakları' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Sitemizdeki tüm içerikler Gündem Akdeniz\'e aittir. İzinsiz kopyalanması, çoğaltılması veya yayınlanması yasaktır.' }] },
              { type: 'heading', tag: 'h3', children: [{ type: 'text', text: 'Sorumluluk Reddi' }] },
              { type: 'paragraph', children: [{ type: 'text', text: 'Sitemizde yayınlanan haberlerin doğruluğu için azami özen gösterilmekle birlikte, olası hatalardan dolayı sorumluluk kabul edilmez.' }] },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1,
          },
        },
      },
    ]

    for (const page of staticPages) {
      const existing = await payload.find({ collection: 'pages', where: { slug: { equals: page.slug } }, limit: 1 })
      if (existing.docs.length === 0) {
        await payload.create({ collection: 'pages', data: page })
      }
    }

    return NextResponse.json({ success: true, message: 'Tüm global ayarlar ve sayfalar başarıyla yüklendi!' })
  } catch (err) {
    console.error('Seed globals failed:', err)
    return NextResponse.json({ success: false, error: String(err) }, { status: 500 })
  }
}
