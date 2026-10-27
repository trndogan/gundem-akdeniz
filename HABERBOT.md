# Haber Botu

Akdeniz Bölgesi'ndeki resmî kurumların haberlerini toplayan, Claude ile özgünleştiren (Gemini
yedekte), görselini üreten ve onay kuyruğuna düşüren modül. Panel: `/gaadmin` altında **Haber Botu** grubu.

## Claude

Bot Ayarları > Yapay Zekâ sekmesinde **Claude API anahtarı** doluysa özgünleştirme ve editör
puanlaması önce Claude ile yapılır (`claude_model`, varsayılan `claude-sonnet-5-5`). Claude yanıt
veremezse (kredi bitti, hata, kısa çıktı) aynı haber için aşağıdaki Gemini zinciri otomatik
devreye girer, bot durmaz. Anahtar boşaltılırsa yalnız Gemini çalışır.

- Özgünleştirme `claude_effort` ayarıyla (varsayılan orta), editör puanlaması düşük eforla çalışır.
- Model seçimi ölçümü (6 gerçek haber, kör hakem, 10 üzerinden genel puan / aylık tahmini maliyet,
  günde ~24 haberle): Opus 5.5 8,2 / ~52 $, Sonnet 5.5 8,0 / ~14 $, Haiku 5.5 düşünmesiz
  7,2 / ~0,6 $, önceki Gemini 6,3. Haiku özgünlükte en zayıf çıktı (kaynağa çok yakın yazıyor).
- Talimat sistem istemine konur ve önbelleğe alınır, aynı turdaki haberler talimatı ucuza okur.
- Sıcaklık ayarı Claude'a gönderilmez, yalnız Gemini'de geçerlidir.
- Taslaktaki `ai_model` alanı haberi hangi modelin yazdığını gösterir.
- Kapak görseli üretimi Gemini'de kalır, Claude görsel üretmez.
- Claude hataları Bot Günlüğü'nde `ai` kanalında "Claude hatası HTTP ..." olarak görünür.
  400 ve kredi mesajı kredinin bittiğini, 401 anahtarın yanlış olduğunu gösterir.

SDK: `anthropic-ai/sdk` (composer). Sunucuda composer yoktur, paket yerelde
`composer install --no-dev --no-scripts` ile kurulup `vendor/` rsync ile gönderilir.

## Zincir

```
Resmî kaynak → Havuz → Editör → Gemini → Kapak → Onay kuyruğu → articles
   (crawl)   (bot_pool) (curate) (rewrite) (webp)  (bot_drafts)   (yayın)
```

Her adım ayrı komut olarak çalışır, biri durursa diğerleri etkilenmez.

| Komut | Ne yapar | Zamanlama |
|---|---|---|
| `php artisan bot:crawl` | Tüm kaynakları dolaşır, yeni haberleri havuza yazar | 30 dakikada bir |
| `php artisan bot:curate` | Havuzdaki haberleri haber değerine göre puanlar, değersizleri eler | 30 dakikada bir |
| `php artisan bot:process` | Havuzdaki haberi Gemini'ye verir, kapağını üretir, taslak açar | 30 dakikada bir |
| `php artisan bot:publish` | Onaylı taslakları slot saatlerine göre yayınlar | 30 dakikada bir |
| `php artisan bot:cleanup` | Eski havuz kaydı, günlük ve çalışma özetlerini siler | Her gece 04:30 |
| `php artisan bot:run` | Üçünü sırayla çalıştırır (tek cron isteyenler için) | — |

Cron tek satır:

```bash
* * * * * cd /home/gundemakdeniz/Akdenizgundemlaravel && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## Tarama hızı

Tarama **kaynak sayısıyla değil süreyle** sınırlanır. Her tur tüm aktif kaynakları
öncelik ve en eski taranma sırasına göre dolaşır, `crawl_time_budget` (varsayılan 20
dakika) dolunca durur ve kalanlar sıranın başında olduğu için bir sonraki turda alınır.

Ölçüm: kaynak başına ortalama **5 saniye**, 228 kaynak yaklaşık **19 dakika**. Yani bir
haberin fark edilme gecikmesi en fazla tarama aralığı kadardır, tur süresi kadar değil.

Sabit sayı (`crawl_batch`) kullanmak isterseniz sıfırdan büyük bir değer yazın; o zaman
tur o kadar kaynakla sınırlanır ve tam tur süresi uzar. Sıfır bırakmak önerilir.

Bir haberin kaynaktan siteye çıkma süresi:

```
tarama (≤30 dk) → editör (≤30 dk) → işleme (≤30 dk) → yayın slotu
```

Yani tipik olarak 1 ila 1,5 saat. Yüksek puanlı haberler işleme ve yayın sırasında öne
geçtiği için havuz kalabalık olsa bile beklemez.

## Kaynaklar nasıl çekiliyor

Kurum siteleri farklı CMS kullandığı için selektörler elle yazılmıyor.
`App\Services\Bot\SourceDetector` sayfayı analiz edip kendisi buluyor:

- **Liste sayfası:** Linkler DOM ata zincirine göre gruplanır. Aynı haber kartından
  üretilen linkler aynı imzayı paylaşır, menü linkleri farklı imzada kalır. Aday gruplar
  arasından seçim, her gruptan bir detay sayfası çekilip gövde uzunluğu ve tarih varlığına
  bakılarak yapılır. Böylece "kurumsal menü" grubu haber grubuyla karışmaz.
- **Detay sayfası:** Readability benzeri skorlama. Paragraf yoğunluğu ebeveyn düğümlere
  dağıtılır, link yoğunluğu ceza olarak düşülür, `icerik|content|detay` gibi sınıf adları
  puan ekler, `menu|share|reklam` puan düşürür.
- **Görsel:** Gövdedeki ilk uygun görsel → `og:image` → sayfadaki haber klasörü desenli görsel.
- **Tarih:** `article:published_time`, `<time datetime>`, JSON-LD `datePublished`,
  son çare olarak metin içindeki `28.08.2026` / `28 Ağustos 2026` kalıbı.

Selektörler kaynağa kaydedilir ama zorunlu değildir. Boşsa ya da tutmazsa çalışma anında
otomatik tespit devreye girer, yani kurum sitesini yenilediğinde kaynak tamamen susmaz.

Kurulum katalogu `database/data/akdeniz-sources.json` içinde. 230 kaynağın tamamı canlı
doğrulandı: haber listesi bulundu, iki ayrı detay sayfasından gövde çıkarıldı, ortalama
kelime sayısı ölçüldü.

### Katalog dağılımı

| Kurum türü | Adet |
|---|---|
| Kaymakamlık | 98 |
| Belediye | 66 |
| İl müdürlüğü | 41 |
| Üniversite | 9 |
| Valilik | 8 |
| Oda / borsa | 7 |
| Kalkınma ajansı | 2 |

İl bazında: Antalya 44, Adana 33, Hatay 31, Mersin 27, Isparta 25, Kahramanmaraş 25,
Burdur 22, Osmaniye 17. Yedi kaynağın ili atanmadı (aynı ilçe adı birden fazla ilde
geçtiği için); panelde **Resmî Kaynaklar > Şehirsiz** sekmesinden atanabilir.

### Yeni kaynak ekleme

Panelde **Resmî Kaynaklar > Kaynak ekle**:

1. Kurum ana sayfasını yazın, **Haber sayfasını bul** düğmesine basın.
2. **Selektörleri otomatik algıla** ile link ve gövde selektörleri doldurulur.
3. **Canlı test** gerçekten haber çekilip çekilmediğini örnek başlıklarla gösterir.

Komut satırından denemek için:

```bash
php tools/bot-detect.php --site=https://www.ornek.bel.tr
php tools/bot-detect.php --url=https://www.ornek.bel.tr/haberler
```

## Editör katmanı

Resmî kurumlar haber ile duyuruyu ayırmaz: ihale ilanı, kurs sertifikası töreni ve 140
milyon liralık yatırım aynı listede gelir. Bu katman olmadan Gemini kotası ve günlük yayın
limiti değersiz duyurulara harcanır, gerçek haber sıranın arkasında kalır.

`bot:curate` havuzdaki puanlanmamış haberleri **tek çağrıda toplu** değerlendirir (20
haberin başlığı ve ilk paragrafı bir istemde gider), her biri için 0-100 arası puan ve kısa
gerekçe döner. Maliyet haber başına değil, tur başına bir çağrıdır.

| Puan | Anlamı |
|---|---|
| 85-100 | Manşetlik: geniş kitleyi ilgilendiren karar, yatırım, kriz, afet |
| 70-84 | Öne çıkarılabilir: somut yatırım, hizmet, ölçülebilir sonuç |
| 45-69 | Sıradan haber: rutin faaliyet, tören, ziyaret |
| 0-44 | Haber değeri yok: ihale, askı duyurusu, sertifika töreni, nezaket ziyareti |

Puan yalnızca **elemede** mutlak kullanılır (eşik altı havuzda elenir, Gemini'ye hiç gitmez).

### Manşet kararı göreceli verilir

Resmî kurum haberleri nadiren 85 puan alır. Mutlak eşik kullanılsa günlerce manşet çıkmaz,
oysa gerçek editörlük görecelidir: bugün elde ne varsa en iyisi manşettir. Bu yüzden
`promotion_mode` varsayılan olarak `relative`'dir:

- Son `promotion_window_hours` (48) saatteki puanlı haberler sıralanır
- En iyi `headline_per_day` (2) tanesi manşet, sonraki `featured_per_day` (6) tanesi öne çıkan olur
- `promotion_floor_score` (55) altındaki hiçbir haber terfi etmez: kötü günün en iyisi manşet olmasın
- Gün içinde yayınlanmış manşetler kotadan düşülür, sıralama her editör turunda tazelenir

Gerçek bir turdan sıralama:

```
83  MANŞET     Seyhan Belediyesi'nin eğitim desteği 281 öğrenciyi üniversiteli yaptı
82  MANŞET     Zorkun Yaylası'nda ultra maraton (81 il, 6 ülke, 1100 sporcu)
78  öne çıkan  Pozantı'da kapalı yüzme havuzu hizmete açıldı
77  öne çıkan  Temizlik hizmetlerine 41 araçlık yatırım
62             FIFA standartlarında halı saha
45             Ulucami Mahallesi'nde asfalt mesaisi
```

`absolute` kipe geçilirse sabit puan eşikleri kullanılır. Eşiklerin tamamı
Bot Ayarları > Editör sekmesindedir. `Onaysız yayın eşiği` sıfırdan
büyük yapılırsa o puanın üstündeki haberler onay kuyruğunu atlar ve doğrudan yayın sırasına
girer; yine de anında yayınlanmaz, slot kotası gün içine yayar.

İşleme ve yayın sırası da puana göredir: yüksek haber değerli içerik günlük limit dolmadan
işlenir ve gün içinde önce yayınlanır.

Elenen örnekler: "16 kursiyere sertifika verildi" (25), "başkanın yurt dışı protokol
ziyareti" (30), "30 kişilik yaz kampının son gün gezisi" (40).

## Filtreler

Havuza girmeden önce uygulanır:

- **Kelime sayısı:** `min_words` (varsayılan 80) altındaki duyurular alınmaz.
- **Aynı gövde:** Bir kaynaktan çekilen iki haberin gövdesi aynıysa selektör yanlış bloğu
  gösteriyordur; kayıt alınmaz ve kaynak panelde hatalı işaretlenir.
- **Yaş:** `max_age_days` (varsayılan 7) günden eski haberler alınmaz. `0` yazarsanız kapanır.
- **Kelime filtresi:** İhale ilanı, tebligat gibi haber değeri olmayan içerikler elenir.
  Panelden düzenlenir. `Engelle` kelimesi geçerse atar, `Zorunlu` tanımlıysa en az biri geçmelidir.
- **Benzerlik:** Son 48 saatteki başlıklarla `similarity_threshold` (varsayılan %70) üzerinde
  benzeşen haber alınmaz. Aynı olayı valilik ve belediye ayrı ayrı duyurduğunda tekrarı önler.

## Kapak üretimi

`cover_engine` ayarı kapağı kimin tasarlayacağını belirler:

- **ai (varsayılan):** Gemini haber metninden kısa manşet ve 2-3 künye bilgisi yazar, sonra
  kaynak fotoğrafın üzerine kapağı tasarlar: yırtık kağıt kenarı, fırça dokulu kırmızı
  bantlar, konuya uygun ikonlar. Model bazen fotoğrafı korumak yerine uydurduğu için çıktı
  **renk histogramıyla doğrulanır**; orijinale benzerlik eşiğin altındaysa reddedilir, bir
  kez daha denenir, yine tutmazsa kodun çizdiği manşete düşülür. Ölçüm: fotoğrafı koruyan
  çıktı 0.90, uyduran 0.60 civarı verir, eşik 0.75'tir.
- **code:** Kapağı kod çizer. Anında, ücretsiz, her seferinde aynı kalitede. Beş şablon
  vardır (degrade perde, alt bant, yan panel, gömülü kart, manşet) ve `image_template`
  otomatikse şablon haberin başlığından seçilir: aynı haber hep aynı kapağı alır ama
  haberden habere değişir.

Fontlar `storage/app/bot/fonts` altındadır: Anton (manşet), Oswald (künye), Roboto Bold.

Fotoğrafın kendisi için `image_mode` ayarı geçerlidir:

- **hybrid (varsayılan):** Kaynakta görsel varsa indirilir, kenarlardan `%8` kırpılır,
  `%30` karartılır, alta yarı saydam bant ve Türkçe büyük harfli başlık basılır. Görsel yoksa
  Gemini görsel modeli devreye girer. O da olmazsa kategori rozetli marka kartı çizilir.
- **source:** Yapay zekâ görseli hiç kullanılmaz, kaynak yoksa marka kartı.
- **ai:** Her haberde sıfırdan görsel üretilir.

Çıktı `public_html/images/bot/YYYY/AA/baslik-slug.webp`, veritabanında `bot/2026/08/...`
göreli yolu tutulur. Sitenin mevcut görsel kuralıyla aynıdır.

## Yayın kotası

Onaylanan haberler tek seferde yayınlanmaz. `publish_schedule` içindeki saatlere göre
gün içine yayılır:

```
hedef = günlük_limit × (geçen slot sayısı / toplam slot sayısı)
```

Bugün yayınlanan sayı hedefin altındaysa aradaki fark kadar onaylı taslak yayınlanır.
Cron bir süre çalışmazsa gün sonunda hedefe yetişilir. Durum **Bot Kontrol Paneli**'nde görünür.

## Gemini anahtarları

İki katman:

1. **Ücretsiz havuz** (`Gemini Anahtarları` ekranı): En az kullanılan anahtardan başlanır.
   `403` alan anahtar kalıcı, `429` alan anahtar gün sonuna kadar devre dışı bırakılır.
   Sayaçlar her gece sıfırlanır.
2. **Ana anahtar** (Bot Ayarları > Yapay Zekâ): Havuz tükendiğinde devreye girer.
   Görsel üretimi de bu anahtarı kullanır.

Çağrılar arasında 4,5 saniye bekleme uygulanır (15 RPM sınırı). `429` ve `5xx` yanıtlarında
4 ve 8 saniyelik iki tekrar denenir.

## Ekranlar

| Ekran | İşlev |
|---|---|
| Bot Kontrol Paneli | Durum kartları, yayın kotası, son çalışmalar, sorunlu kaynaklar, elle tetikleme |
| Onay Kuyruğu | Taslakları inceleme, düzenleme, onaylama, yayınlama, görseli yenileme |
| Resmî Kaynaklar | Kaynak CRUD, otomatik algılama, canlı test, toplu şehir atama |
| Haber Havuzu | Ham haberler, editör puanı ve gerekçesi, önizleme, tek tek taslak üretme |
| Bot Ayarları | Yapay zekâ, toplama, editör, görsel ve yayın ayarları |
| Gemini Anahtarları | Anahtar havuzu, kota takibi, test |
| Kelime Filtreleri | Engelli ve zorunlu kelimeler |
| Bot Günlüğü | Tarama, yapay zekâ, görsel, yayın kayıtları |

## Sorun giderme

**Kaynak üst üste hata veriyor:** Kurum sitesi şablonunu değiştirmiş olabilir.
Kaynak satırındaki **Selektörleri yenile** düğmesi çoğu durumda çözer. Beş hatadan sonra
kaynak tarama sırasına alınmaz, panelde **Sorunlu** sekmesinde listelenir.

**Taslak üretilmiyor:** Bot Ayarları'nda günlük limit dolmuş olabilir, ya da kullanılabilir
Gemini anahtarı kalmamıştır. Kontrol panelindeki "Gemini kotası" kartı durumu gösterir.

**Havuz doluyor ama haber az geliyor:** `max_age_days` filtresi devrede olabilir. Resmî
kurumlar seyrek haber yayınlar; 7 günlük pencere valilikler için genelde yeterli, ilçe
belediyeleri için 14-30 gün daha uygun olabilir.

**Görsel üretilmiyor:** `storage/app/bot/fonts/Roboto-Bold.ttf` dosyasının yüklendiğini ve
`public_html/images/bot` klasörünün yazılabilir olduğunu kontrol edin.

**Görsel üretiliyor ama sitede görünmüyor:** Görseller web köküne yazılmalıdır. `PathHelper`
web isteğinde `DOCUMENT_ROOT` kullanır, cron'da bu değişken yoktur. `ImageComposer` bu yüzden
kökü kendisi çözer (`DOCUMENT_ROOT` → `public_path` ayarı → hesap kökündeki `public_html`).
Otomatik tespit yanılırsa Bot Ayarları'ndaki `public_path` alanına tam yolu yazın,
örn. `/home/gundemakdeniz/public_html`.

**"Yapay zekâ yanıtı alınamadı":** Çoğunlukla model adı yanlıştır. Google model adlarını
değiştirir ve olmayan ada 404 döner. Geçerli listeyi görmek için:

```bash
curl -s -H "x-goog-api-key: ANAHTAR" https://generativelanguage.googleapis.com/v1beta/models \
  | grep -o '"name": "models/[^"]*"'
```

Kurulumda kullanılanlar: ana model `gemini-3.6-flash`, ücretsiz havuz `gemini-3.1-flash-lite`,
görsel `gemini-3-pro-image`.

## Kurulum

```bash
tools/deploy-bot.sh --host <ip> --port <port> --user <kullanıcı> --key ~/.ssh/id_ed25519
```

Betik yedek alır, dosyaları yükler, migration ve seeder'ı çalıştırır, önbellekleri tazeler,
izinleri düzeltir ve cron satırını kurar. `--dry-run` ile ne yapacağını önce görebilirsiniz.

Elle kurulum:

```bash
php artisan migrate --force
php artisan db:seed --class=AkdenizBotSeeder --force
php artisan config:clear && php artisan view:clear
```

Yeni composer paketi gerekmez; modül `symfony/dom-crawler`, `symfony/css-selector` ve GD
üzerine kuruludur, üçü de projede zaten var.
