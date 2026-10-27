<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $antalya = City::where('slug', 'antalya')->first();
        $mersin = City::where('slug', 'mersin')->first();
        $adana = City::where('slug', 'adana')->first();
        $hatay = City::where('slug', 'hatay')->first();
        $isparta = City::where('slug', 'isparta')->first();

        $gundem = Category::where('slug', 'gundem')->first();
        $ekonomi = Category::where('slug', 'ekonomi-tarim')->first();
        $turizm = Category::where('slug', 'turizm-yasam')->first();
        $spor = Category::where('slug', 'spor')->first();
        $teknoloji = Category::where('slug', 'teknoloji')->first();
        $kultur = Category::where('slug', 'kultur')->first();

        $articles = [
            // Manşet (Headline)
            [
                'title' => 'Antalya\'nın Geleceği: Akdeniz Kıyısında Yeni Teknoloji Merkezi',
                'excerpt' => 'Temel atma töreni, bölgenin dijital altyapısına ve sürdürülebilir kalkınma projelerine yapılan 2 milyar dolarlık yatırımın başlangıcını işaret ediyor.',
                'body' => '<p>Antalya\'da düzenlenen temel atma töreniyle birlikte, Akdeniz kıyısında yeni bir teknoloji merkezi inşa edilmeye başlandı. Proje, bölgenin dijital altyapısını güçlendirmeyi ve sürdürülebilir kalkınmaya katkı sağlamayı hedefliyor.</p><p>2 milyar dolarlık yatırımla hayata geçirilecek olan merkez, yapay zeka, siber güvenlik ve yeşil teknoloji alanlarında faaliyet gösterecek. Merkezin 2028 yılında tam kapasiteyle hizmet vermesi planlanıyor.</p><p>Proje kapsamında 5.000\'den fazla nitelikli istihdam oluşturulması hedefleniyor. Ayrıca üniversitelerle işbirliği protokolleri imzalanarak, genç girişimcilere mentorluk ve kuluçka programları sunulacak.</p>',
                'category_id' => $gundem->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1524661135-423995f22d0b?w=1200&h=800&fit=crop',
                'is_headline' => true,
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now()->subMinutes(12),
            ],
            // Featured Articles
            [
                'title' => 'Mersin Limanı Genişleme Çalışmaları: 2026 Yol Haritası',
                'excerpt' => 'Yeni yanaşma yerleri 3. çeyrekte hazır olacak.',
                'body' => '<p>Mersin Uluslararası Limanı\'ndaki genişleme çalışmaları hız kesmeden devam ediyor. Yeni yanaşma yerlerinin 2026 yılının üçüncü çeyreğinde tamamlanması planlanıyor.</p><p>Genişleme projesi kapsamında limanın yıllık konteyner kapasitesi 3 milyon TEU\'ya çıkarılacak. Bu sayede Mersin Limanı, Doğu Akdeniz\'in en büyük konteyner limanı olma hedefine bir adım daha yaklaşacak.</p><p>Proje tamamlandığında bölge ekonomisine yıllık 500 milyon dolarlık ek katkı sağlanması bekleniyor.</p>',
                'category_id' => $ekonomi->id,
                'city_id' => $mersin->id,
                'image_url' => 'https://images.unsplash.com/photo-1494412651409-8963ce7935a7?w=400&h=300&fit=crop',
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now()->subHours(1),
            ],
            [
                'title' => 'Turizm 2026: Yaz Sezonu İçin Rekor Rakamlar Bekleniyor',
                'excerpt' => 'Rezervasyonlar geçen yıla göre yüzde 24 arttı.',
                'body' => '<p>Akdeniz Bölgesi\'nde 2026 yaz sezonu için turizm beklentileri oldukça yüksek. Erken rezervasyon verileri, geçen yılın aynı dönemine göre yüzde 24\'lük bir artış gösteriyor.</p><p>Özellikle Antalya, Alanya ve Side bölgelerinde otellerin doluluk oranlarının yüzde 95\'e ulaşması bekleniyor. Avrupa\'dan gelen charter uçuş sayısında da önemli artışlar kaydedildi.</p><p>Turizm Bakanlığı yetkilileri, bu yıl Akdeniz kıyılarına 20 milyondan fazla turist gelmesini beklediklerini açıkladı.</p>',
                'category_id' => $turizm->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1605649487212-47bdab064df7?w=400&h=300&fit=crop',
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'Akdeniz İklim Zirvesi İlk Konuşmacıları Açıkladı',
                'excerpt' => 'Sürdürülebilirlik uzmanları Adana\'da toplanacak.',
                'body' => '<p>Adana\'da düzenlenecek Akdeniz İklim Zirvesi\'nin ilk konuşmacı listesi açıklandı. Dünya çapında tanınan sürdürülebilirlik uzmanları ve iklim bilimciler, bölgenin iklim değişikliğiyle mücadele stratejilerini tartışacak.</p><p>Zirve, 3 gün sürecek ve 50\'den fazla ülkeden katılımcı bekleniyor. Ana temalar arasında su kaynakları yönetimi, tarımda iklim adaptasyonu ve yenilenebilir enerji yer alıyor.</p>',
                'category_id' => $gundem->id,
                'city_id' => $adana->id,
                'image_url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=300&fit=crop',
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now()->subHours(3),
            ],
            [
                'title' => 'Yerel Ekonomi: Akdeniz Girişimleri Yükselişte',
                'excerpt' => 'Yatırım turları rekor seviyelere ulaşıyor.',
                'body' => '<p>Akdeniz Bölgesi\'ndeki girişimcilik ekosistemi hızla büyümeye devam ediyor. Son bir yılda bölgedeki startup\'lara yapılan yatırımlar rekor seviyelere ulaştı.</p><p>Özellikle tarım teknolojileri, turizm-tech ve lojistik alanlarındaki girişimler yatırımcıların ilgisini çekiyor. Bölgedeki teknopark ve kuluçka merkezleri de tam kapasite çalışıyor.</p>',
                'category_id' => $ekonomi->id,
                'city_id' => null,
                'image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=400&h=300&fit=crop',
                'is_featured' => false,
                'is_published' => true,
                'published_at' => now()->subHours(4),
            ],
            // Regional News - Antalya
            [
                'title' => 'Kaleiçi Restorasyon Projesi Onaylandı',
                'excerpt' => 'Tarihi Kaleiçi bölgesi merkezi hükümetten özel restorasyon fonu alacak...',
                'body' => '<p>Antalya\'nın tarihi Kaleiçi bölgesi için hazırlanan kapsamlı restorasyon projesi onaylandı. Merkezi hükümetten alınan özel fon ile tarihi yapılar restore edilecek.</p><p>Proje kapsamında 200\'den fazla tarihi yapı onarılacak ve bölgenin altyapısı yenilenecek. Restorasyon çalışmalarının 3 yıl içinde tamamlanması hedefleniyor.</p><p>UNESCO Dünya Mirası geçici listesinde yer alan Kaleiçi, bu proje ile kalıcı listeye alınma şansını artıracak.</p>',
                'category_id' => $kultur->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'Yeni Kruvaziyer Terminali Lüks Turizmi Artıracak',
                'excerpt' => 'Liman, Akdeniz\'in en büyük kruvaziyer gemilerine hazırlanıyor...',
                'body' => '<p>Antalya Limanı\'nda inşa edilen yeni kruvaziyer terminali, lüks turizm segmentinde önemli bir atılım olacak. Terminal, dünyanın en büyük kruvaziyer gemilerini ağırlayabilecek kapasitede tasarlandı.</p><p>Yeni terminal ile yılda 500.000\'den fazla kruvaziyer yolcusunun Antalya\'yı ziyaret etmesi bekleniyor. Bu da şehrin turizm gelirine önemli katkı sağlayacak.</p>',
                'category_id' => $turizm->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(5),
            ],
            [
                'title' => 'Dikey Tarım Projesi Sera Tesislerinde Başladı',
                'excerpt' => 'Yenilikçiler artan sıcaklıklarla mücadele için su tasarruflu tarım yöntemleri test ediyor...',
                'body' => '<p>Antalya\'nın sera bölgesinde başlatılan dikey tarım projesi, iklim değişikliğine karşı yenilikçi çözümler sunuyor. Proje kapsamında su tasarruflu ve yüksek verimli tarım yöntemleri test ediliyor.</p><p>Geleneksel seracılığa göre yüzde 90 daha az su kullanan sistem, aynı alanda 10 kat daha fazla ürün yetiştirmeyi mümkün kılıyor.</p>',
                'category_id' => $teknoloji->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1464037866556-6812c9d1c72e?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Uluslararası Sanat Festivali 2026 Programını Açıkladı',
                'excerpt' => 'Dünyaca ünlü sanatçılar Akdeniz\'in antik tiyatrolarında sahne alacak...',
                'body' => '<p>Akdeniz Uluslararası Sanat Festivali\'nin 2026 programı açıklandı. Festival kapsamında dünyaca ünlü sanatçılar, bölgenin antik tiyatrolarında performanslar sergileyecek.</p><p>Aspendos, Side ve Perge antik tiyatrolarında gerçekleştirilecek etkinlikler, kültür ve tarihi bir arada sunacak. Festival, 2 hafta sürecek ve 100\'den fazla etkinlik yer alacak.</p>',
                'category_id' => $kultur->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            // Latest News
            [
                'title' => 'Akdeniz İhracat Rakamları Açıklandı',
                'excerpt' => 'Bölge ihracatı geçen yılın aynı dönemine göre yüzde 15 artış göstererek rekor tazeledi...',
                'body' => '<p>Akdeniz Bölgesi\'nin ihracat rakamları açıklandı. Bölge, geçen yılın aynı dönemine göre yüzde 15\'lik bir artış kaydederek ihracat rekoru kırdı.</p><p>Özellikle tarım ürünleri, tekstil ve otomotiv yan sanayi sektörlerinde önemli artışlar yaşandı. Mersin Limanı üzerinden gerçekleştirilen ihracat, toplam rakamın yüzde 40\'ını oluşturdu.</p>',
                'category_id' => $ekonomi->id,
                'city_id' => $mersin->id,
                'image_url' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subMinutes(30),
            ],
            [
                'title' => 'Erken Rezervasyon Dönemi Başladı',
                'excerpt' => 'Otellerde doluluk oranlarının bu yaz yüzde 95\'e ulaşması bekleniyor...',
                'body' => '<p>Akdeniz Bölgesi\'nde erken rezervasyon dönemi rekor taleple başladı. Otellerde doluluk oranlarının bu yaz yüzde 95\'e ulaşması bekleniyor.</p><p>Turizm sektörü temsilcileri, erken rezervasyon kampanyalarının büyük ilgi gördüğünü ve fiyatların geçen yıla göre makul seviyelerde tutulduğunu belirtti.</p>',
                'category_id' => $turizm->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHour(),
            ],
            [
                'title' => 'Milli Yüzücülerimizden Altın Madalya',
                'excerpt' => 'Avrupa Şampiyonası\'nda milli sporcularımız göğsümüzü kabarttı...',
                'body' => '<p>Avrupa Yüzme Şampiyonası\'nda milli sporcularımız büyük başarı elde etti. Akdeniz Bölgesi\'nden yetişen genç yüzücüler, altın madalya kazanarak ülkemizi gururlandırdı.</p><p>Antalya\'da yetişen 19 yaşındaki sporcu, 200 metre serbest stilde Avrupa şampiyonu oldu. Antrenörler, bölgedeki yüzme altyapısının gelişmesinin bu başarıda büyük rol oynadığını vurguladı.</p>',
                'category_id' => $spor->id,
                'city_id' => $antalya->id,
                'image_url' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'Teknopark\'ta Yeni Girişim Hızlandırma Programı',
                'excerpt' => 'Genç girişimciler için destek ve mentorluk programı başvuruları başladı...',
                'body' => '<p>Isparta Teknopark\'ta yeni bir girişim hızlandırma programı başlatıldı. Program, genç girişimcilere mentorluk, ofis alanı ve yatırımcı ağına erişim imkanı sunuyor.</p><p>6 aylık program süresince girişimciler, iş modellerini geliştirme, pazarlama stratejisi oluşturma ve yatırım turu hazırlığı konularında destek alacak.</p>',
                'category_id' => $teknoloji->id,
                'city_id' => $isparta->id,
                'image_url' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(3),
            ],
            // Extra articles for other cities
            [
                'title' => 'Hatay\'da Yeniden İnşa Çalışmaları Hızlandı',
                'excerpt' => 'Deprem sonrası yeniden yapılanma süreci büyük bir ivme kazandı.',
                'body' => '<p>Hatay\'da deprem sonrası başlatılan yeniden inşa çalışmaları büyük bir hızla devam ediyor. Şehir merkezinde yeni konut projeleri ve altyapı çalışmaları eş zamanlı olarak yürütülüyor.</p><p>Toplam 50.000 konutun inşası planlanırken, bunların 15.000\'i tamamlanma aşamasına geldi. Modern deprem yönetmeliğine uygun inşa edilen yapılar, en yüksek güvenlik standartlarını karşılıyor.</p>',
                'category_id' => $gundem->id,
                'city_id' => $hatay->id,
                'image_url' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(4),
            ],
            [
                'title' => 'Adana Lezzet Festivali Başlıyor',
                'excerpt' => 'Adana kebabından şalgama, yöresel lezzetler festivalde buluşuyor.',
                'body' => '<p>Adana\'nın dünyaca ünlü mutfağı, bu yıl düzenlenecek Lezzet Festivali ile bir kez daha gündeme geliyor. Festival kapsamında yöresel lezzetler, şef gösterileri ve yemek yarışmaları düzenlenecek.</p><p>3 gün sürecek festivale 200\'den fazla restoran ve yiyecek üreticisi katılacak. Adana kebabı, şalgam, bici bici ve daha birçok yöresel lezzet ziyaretçilere sunulacak.</p>',
                'category_id' => $kultur->id,
                'city_id' => $adana->id,
                'image_url' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(6),
            ],
            [
                'title' => 'Mersin\'de Yeni Serbest Bölge Yatırımları',
                'excerpt' => 'Serbest bölgede 10 yeni fabrika kurulacak, 3.000 kişiye istihdam sağlanacak.',
                'body' => '<p>Mersin Serbest Bölgesi\'nde 10 yeni fabrika yatırımı için anlaşmalar imzalandı. Toplam 500 milyon dolarlık yatırımla 3.000 kişiye istihdam sağlanacak.</p><p>Yatırımlar ağırlıklı olarak gıda işleme, plastik ve kimya sektörlerinde gerçekleşecek. Serbest bölgenin stratejik konumu ve liman yakınlığı, yatırımcılar için en büyük avantaj olarak öne çıkıyor.</p>',
                'category_id' => $ekonomi->id,
                'city_id' => $mersin->id,
                'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&h=400&fit=crop',
                'is_published' => true,
                'published_at' => now()->subHours(7),
            ],
        ];

        foreach ($articles as $articleData) {
            $articleData['user_id'] = 1;
            Article::create($articleData);
        }
    }
}
