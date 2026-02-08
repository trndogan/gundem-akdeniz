import config from '../../../payload.config'
import { getPayload } from 'payload'
import { NextResponse } from 'next/server'

export const GET = async () => {
  const payload = await getPayload({ config })

  try {
    // Create Admin User
    const users = await payload.find({
      collection: 'users',
      where: {
        email: {
          equals: 'admin@akdenizgundem.com',
        },
      },
    })

    if (users.totalDocs === 0) {
      await payload.create({
        collection: 'users',
        data: {
          email: 'admin@akdenizgundem.com',
          password: 'password123',
        },
      })
      console.log('Admin user created')
    }

    // Create Categories
    const categories = [
      'Gündem', 'Ekonomi', 'Turizm', 'İklim', 'Girişim', 
      'Kültür', 'Denizcilik', 'Agri-Tech', 'Spor', 'Teknoloji'
    ]
    
    const categoryMap = new Map()

    for (const catName of categories) {
      const existing = await payload.find({
        collection: 'categories',
        where: { name: { equals: catName } },
      })

      if (existing.totalDocs === 0) {
        const doc = await payload.create({
          collection: 'categories',
          data: { name: catName },
        })
        categoryMap.set(catName, doc.id)
        console.log(`Category created: ${catName}`)
      } else {
        categoryMap.set(catName, existing.docs[0].id)
      }
    }

    // Helper to download image
    const downloadImage = async (url: string): Promise<Buffer> => {
        const response = await fetch(url)
        const arrayBuffer = await response.arrayBuffer()
        return Buffer.from(arrayBuffer)
    }

    // Create Media and Articles
    const articles = [
      {
        title: "Antalya'nın Geleceği: Akdeniz Kıyısında Yeni Teknoloji Merkezi",
        category: 'Gündem',
        imageUrl: 'https://images.unsplash.com/photo-1524661135-423995f22d0b?w=1200&h=800&fit=crop',
        excerpt: "Temel atma töreni, bölgenin dijital altyapısına ve sürdürülebilir kalkınma projelerine yapılan 2 milyar dolarlık yatırımın başlangıcını işaret ediyor.",
        content: {
          root: {
            type: 'root',
            children: [
              {
                type: 'paragraph',
                children: [
                  {
                    type: 'text',
                    text: "Temel atma töreni, bölgenin dijital altyapısına ve sürdürülebilir kalkınma projelerine yapılan 2 milyar dolarlık yatırımın başlangıcını işaret ediyor. Antalya, sadece turizm değil, artık teknoloji üssü olma yolunda emin adımlarla ilerliyor.",
                    version: 1
                  }
                ],
                version: 1
              }
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            version: 1
          }
        },
        location: 'antalya',
        isFeatured: true
      },
      {
        title: "Mersin Limanı Genişleme Çalışmaları: 2026 Yol Haritası",
        category: 'Ekonomi',
        imageUrl: 'https://images.unsplash.com/photo-1494412651409-8963ce7935a7?w=400&h=300&fit=crop',
        excerpt: "Yeni yanaşma yerleri 3. çeyrekte hazır olacak.",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Mersin Limanı kapasite artırımı projesi kapsamında..." , version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'mersin',
        isFeatured: false
      },
       {
        title: "Turizm 2026: Yaz Sezonu İçin Rekor Rakamlar Bekleniyor",
        category: 'Turizm',
        imageUrl: 'https://images.unsplash.com/photo-1605649487212-47bdab064df7?w=400&h=300&fit=crop',
        excerpt: "Rezervasyonlar geçen yıla göre yüzde 24 arttı.",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Akdeniz otelleri şimdiden dolmaya başladı...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Akdeniz İklim Zirvesi İlk Konuşmacıları Açıkladı",
        category: 'İklim',
        imageUrl: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=300&fit=crop',
        excerpt: "Sürdürülebilirlik uzmanları Adana'da toplanacak.",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "İklim değişikliği ile mücadele kapsamında...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'adana',
        isFeatured: false
      },
      {
        title: "Kaleiçi Restorasyon Projesi Onaylandı",
        category: 'Kültür',
        imageUrl: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        excerpt: "Tarihi Kaleiçi bölgesi merkezi hükümetten özel restorasyon fonu alacak...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Antalya'nın kalbi Kaleiçi yenileniyor...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Yeni Kruvaziyer Terminali Lüks Turizmi Artıracak",
        category: 'Denizcilik',
        imageUrl: 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&h=400&fit=crop',
        excerpt: "Liman, Akdeniz'in en büyük kruvaziyer gemilerine hazırlanıyor...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Dev gemiler için yeni liman projesi...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Dikey Tarım Projesi Sera Tesislerinde Başladı",
        category: 'Agri-Tech',
        imageUrl: 'https://images.unsplash.com/photo-1464037866556-6812c9d1c72e?w=600&h=400&fit=crop',
        excerpt: "Yenilikçiler artan sıcaklıklarla mücadele için su tasarruflu tarım yöntemleri test ediyor...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Tarımda devrim niteliğinde adım...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Uluslararası Sanat Festivali 2026 Programını Açıkladı",
        category: 'Kültür',
        imageUrl: 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=600&h=400&fit=crop',
        excerpt: "Dünyaca ünlü sanatçılar Akdeniz'in antik tiyatrolarında sahne alacak...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Sanatseverler için dolu dolu bir program...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Akdeniz İhracat Rakamları Açıklandı",
        category: 'Ekonomi',
        imageUrl: 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=600&h=400&fit=crop',
        excerpt: "Bölge ihracatı geçen yılın aynı dönemine göre yüzde 15 artış göstererek rekor tazeledi...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "İhracatta rekor artış...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'mersin',
        isFeatured: false
      },
      {
        title: "Erken Rezervasyon Dönemi Başladı",
        category: 'Turizm',
        imageUrl: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        excerpt: "Otellerde doluluk oranlarının bu yaz yüzde 95'e ulaşması bekleniyor...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Tatilciler elini çabuk tutmalı...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Milli Yüzücülerimizden Altın Madalya",
        category: 'Spor',
        imageUrl: 'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=600&h=400&fit=crop',
        excerpt: "Avrupa Şampiyonası'nda milli sporcularımız göğsümüzü kabarttı...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Altın madalya gururu...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'antalya',
        isFeatured: false
      },
      {
        title: "Teknopark'ta Yeni Girişim Hızlandırma Programı",
        category: 'Teknoloji',
        imageUrl: 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=600&h=400&fit=crop',
        excerpt: "Genç girişimciler için destek ve mentorluk programı başvuruları başladı...",
        content: { root: { type: 'root', children: [{ type: 'paragraph', children: [{ type: 'text', text: "Girişimciler için büyük fırsat...", version: 1}], version: 1}], direction: 'ltr', format: '', indent: 0, version: 1 } },
        location: 'isparta',
        isFeatured: false
      }
    ]

    for (const article of articles) {
      const existing = await payload.find({
        collection: 'articles',
        where: { title: { equals: article.title } },
      })

      if (existing.totalDocs === 0) {
        let imageId = null
        if (article.imageUrl) {
          try {
            const buffer = await downloadImage(article.imageUrl)
            const mediaDoc = await payload.create({
              collection: 'media',
              data: {
                alt: article.title,
              },
              file: {
                data: buffer,
                name: `${article.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.jpg`,
                mimetype: 'image/jpeg',
                size: buffer.length,
              },
            })
            imageId = mediaDoc.id
            console.log(`Image uploaded for: ${article.title}`)
          } catch (e) {
            console.error(`Failed to download/upload image for ${article.title}:`, e)
          }
        }

        await payload.create({
          collection: 'articles',
          data: {
            title: article.title,
            category: categoryMap.get(article.category),
            featuredImage: imageId,
            excerpt: article.excerpt,
            content: article.content,
            location: article.location,
            isFeatured: article.isFeatured,
            publishedAt: new Date().toISOString(),
          },
        })
        console.log(`Article created: ${article.title}`)
      }
    }

    return NextResponse.json({ success: true, message: 'Seeding completed' })
  } catch (err) {
    console.error('Seeding failed:', err)
    return NextResponse.json({ success: false, error: String(err) }, { status: 500 })
  }
}
