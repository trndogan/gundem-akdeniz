<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Hakkımızda',
                'slug' => 'hakkimizda',
                'sort_order' => 1,
                'body' => '<h2>Gündem Akdeniz Hakkında</h2>
<p>Gündem Akdeniz, 2024 yılında Akdeniz Bölgesi\'nin sesini duyurmak amacıyla kurulan bağımsız bir dijital haber platformudur. Antalya, Mersin, Adana, Hatay, Isparta, Burdur, Osmaniye ve Kahramanmaraş illerinden güncel haberleri okuyucularına ulaştırmayı hedeflemektedir.</p>

<h3>Misyonumuz</h3>
<p>Akdeniz Bölgesi\'ndeki gelişmeleri tarafsız, hızlı ve güvenilir bir şekilde kamuoyuyla paylaşmak; bölgenin ekonomik, sosyal ve kültürel dinamiklerini geniş kitlelere aktarmaktır.</p>

<h3>Vizyonumuz</h3>
<p>Türkiye\'nin en güvenilir bölgesel haber platformu olmak ve dijital gazetecilik alanında yenilikçi yaklaşımlarla sektöre öncülük etmektir.</p>

<h3>Değerlerimiz</h3>
<ul>
<li><strong>Tarafsızlık:</strong> Haberleri olduğu gibi, herhangi bir siyasi veya ticari baskı altında kalmadan aktarırız.</li>
<li><strong>Hız:</strong> Gelişmeleri anında takip eder, okuyucularımıza en kısa sürede ulaştırırız.</li>
<li><strong>Güvenilirlik:</strong> Doğrulanmış bilgileri paylaşır, kaynaklarımızı titizlikle kontrol ederiz.</li>
<li><strong>Yerellik:</strong> Akdeniz Bölgesi\'nin nabzını tutar, yerel dinamikleri en iyi şekilde yansıtırız.</li>
</ul>

<h3>Ekibimiz</h3>
<p>Deneyimli gazeteciler, editörler ve teknoloji uzmanlarından oluşan ekibimiz, 7/24 çalışarak bölgeden en güncel haberleri sizlere sunmaktadır. Her bir ekip üyemiz, gazetecilik etiğine bağlı kalarak görevini sürdürmektedir.</p>',
            ],
            [
                'title' => 'İletişim',
                'slug' => 'iletisim',
                'template' => 'contact',
                'sort_order' => 2,
                'body' => '<h2>Bize Ulaşın</h2>
<p>Haber ihbarlarınız, önerileriniz, şikayetleriniz veya reklam talepleriniz için aşağıdaki iletişim kanallarından bize ulaşabilirsiniz.</p>

<h3>Genel İletişim</h3>
<ul>
<li><strong>E-posta:</strong> info@gundemakdeniz.com</li>
<li><strong>Telefon:</strong> +90 (242) 000 00 00</li>
<li><strong>WhatsApp İhbar Hattı:</strong> +90 (542) 000 00 00</li>
</ul>

<h3>Adres</h3>
<p>Gündem Akdeniz Medya A.Ş.<br>
Fener Mah. Tekelioğlu Cad. No:12/5<br>
Muratpaşa / Antalya, Türkiye</p>

<h3>Haber İhbarı</h3>
<p>Çevrenizdeki önemli gelişmeleri, olayları ve haberleri bize bildirin. Haber ihbar hattımız 7/24 açıktır. Gönderdiğiniz bilgiler editör ekibimiz tarafından değerlendirilecektir.</p>

<h3>Çalışma Saatleri</h3>
<p>Haber merkezimiz 7 gün 24 saat aktiftir.<br>
İdari birimlerimiz: Pazartesi - Cuma, 09:00 - 18:00</p>',
            ],
            [
                'title' => 'Reklam',
                'slug' => 'reklam',
                'sort_order' => 3,
                'body' => '<h2>Reklam ve İş Birliği</h2>
<p>Gündem Akdeniz, Akdeniz Bölgesi\'nin en çok ziyaret edilen haber platformlarından biridir. Markanızı bölgenin en geniş dijital kitlesine ulaştırmak için doğru adrestesiniz.</p>

<h3>Neden Gündem Akdeniz?</h3>
<ul>
<li><strong>Aylık 2+ milyon sayfa görüntüleme</strong></li>
<li><strong>500.000+ tekil ziyaretçi</strong></li>
<li><strong>Hedefli bölgesel kitle:</strong> Antalya, Mersin, Adana, Hatay ve çevre iller</li>
<li><strong>Yüksek etkileşim oranları</strong></li>
<li><strong>SEO uyumlu içerik yapısı</strong></li>
</ul>

<h3>Reklam Alanları</h3>
<ul>
<li><strong>Header Banner:</strong> Tüm sayfalarda görünen üst banner alanı</li>
<li><strong>Sidebar Reklamları:</strong> Haber detay sayfalarında yan panel</li>
<li><strong>İçerik Arası:</strong> Haber akışı içinde native reklam alanları</li>
<li><strong>Footer Banner:</strong> Sayfa altı geniş banner alanı</li>
<li><strong>Haber İçi:</strong> Haber detay sayfalarında metin arası reklam</li>
</ul>

<h3>İletişim</h3>
<p>Reklam ve iş birliği talepleriniz için:<br>
<strong>E-posta:</strong> reklam@gundemakdeniz.com<br>
<strong>Telefon:</strong> +90 (242) 000 00 01</p>',
            ],
            [
                'title' => 'Künye',
                'slug' => 'kunye',
                'sort_order' => 4,
                'body' => '<h2>Künye</h2>

<h3>Yayın Bilgileri</h3>
<ul>
<li><strong>Yayın Adı:</strong> Gündem Akdeniz</li>
<li><strong>Yayın Türü:</strong> İnternet Haber Sitesi</li>
<li><strong>Yayın Adresi:</strong> gundemakdeniz.com</li>
<li><strong>Kuruluş Tarihi:</strong> 2024</li>
</ul>

<h3>İmtiyaz Sahibi</h3>
<p>Gündem Akdeniz Medya A.Ş.</p>

<h3>Genel Yayın Yönetmeni</h3>
<p>Sorumlu Yazı İşleri Müdürü olarak atanacaktır.</p>

<h3>Editörler</h3>
<ul>
<li><strong>Haber Editörü:</strong> Haber Merkezi</li>
<li><strong>Ekonomi Editörü:</strong> Ekonomi Masası</li>
<li><strong>Spor Editörü:</strong> Spor Servisi</li>
<li><strong>Kültür-Sanat Editörü:</strong> Kültür Masası</li>
</ul>

<h3>Teknik Bilgiler</h3>
<ul>
<li><strong>Hosting:</strong> Türkiye lokasyonlu sunucular</li>
<li><strong>Altyapı:</strong> Laravel 11, Filament PHP</li>
<li><strong>Tasarım:</strong> Tailwind CSS, Livewire 3</li>
</ul>

<p><em>Bu site, 5187 sayılı Basın Kanunu ve 5651 sayılı İnternet Kanunu kapsamında yayın yapmaktadır.</em></p>',
            ],
            [
                'title' => 'Gizlilik Politikası',
                'slug' => 'gizlilik-politikasi',
                'sort_order' => 5,
                'body' => '<h2>Gizlilik Politikası</h2>
<p><em>Son güncelleme: Şubat 2026</em></p>

<p>Gündem Akdeniz olarak kişisel verilerinizin korunmasına büyük önem veriyoruz. Bu gizlilik politikası, sitemizi ziyaret ettiğinizde hangi bilgilerin toplandığını ve bu bilgilerin nasıl kullanıldığını açıklamaktadır.</p>

<h3>Toplanan Bilgiler</h3>
<p>Sitemizi ziyaret ettiğinizde aşağıdaki bilgiler otomatik olarak toplanabilir:</p>
<ul>
<li>IP adresi</li>
<li>Tarayıcı türü ve sürümü</li>
<li>Ziyaret edilen sayfalar ve ziyaret süresi</li>
<li>Yönlendiren site (referrer)</li>
<li>Cihaz bilgileri (masaüstü, mobil, tablet)</li>
</ul>

<h3>Çerezler (Cookies)</h3>
<p>Sitemiz, kullanıcı deneyimini iyileştirmek amacıyla çerezler kullanmaktadır. Çerezler hakkında detaylı bilgi için Çerez Politikası sayfamızı inceleyebilirsiniz.</p>

<h3>Bilgilerin Kullanımı</h3>
<p>Toplanan bilgiler aşağıdaki amaçlarla kullanılmaktadır:</p>
<ul>
<li>Site performansının izlenmesi ve iyileştirilmesi</li>
<li>İçerik önerilerinin kişiselleştirilmesi</li>
<li>İstatistiksel analizlerin yapılması</li>
<li>Yasal yükümlülüklerin yerine getirilmesi</li>
</ul>

<h3>Üçüncü Taraf Hizmetler</h3>
<p>Sitemizde Google Analytics gibi üçüncü taraf analiz araçları kullanılmaktadır. Bu hizmetlerin kendi gizlilik politikaları bulunmaktadır.</p>

<h3>Haklarınız</h3>
<p>6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında aşağıdaki haklara sahipsiniz:</p>
<ul>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
<li>İşlenmişse buna ilişkin bilgi talep etme</li>
<li>Kişisel verilerinizin silinmesini veya yok edilmesini isteme</li>
</ul>

<p>Talepleriniz için <strong>kvkk@gundemakdeniz.com</strong> adresine başvurabilirsiniz.</p>',
            ],
            [
                'title' => 'Kullanım Koşulları',
                'slug' => 'kullanim-kosullari',
                'sort_order' => 6,
                'body' => '<h2>Kullanım Koşulları</h2>
<p><em>Son güncelleme: Şubat 2026</em></p>

<p>Bu web sitesini kullanarak aşağıdaki koşulları kabul etmiş sayılırsınız. Lütfen bu koşulları dikkatlice okuyunuz.</p>

<h3>Genel Hükümler</h3>
<p>Gündem Akdeniz (gundemakdeniz.com) internet sitesinde yayınlanan tüm içerikler, Gündem Akdeniz Medya A.Ş.\'nin mülkiyetindedir. İzinsiz kopyalanması, çoğaltılması veya yeniden yayınlanması yasaktır.</p>

<h3>İçerik Kullanımı</h3>
<ul>
<li>Sitedeki haberler yalnızca kişisel bilgilenme amacıyla kullanılabilir.</li>
<li>Ticari amaçla içerik kullanımı için yazılı izin alınması zorunludur.</li>
<li>Haberlere link verilmesi serbesttir, ancak içeriğin tamamının kopyalanması yasaktır.</li>
<li>Alıntı yapılması durumunda kaynak belirtilmesi zorunludur.</li>
</ul>

<h3>Sorumluluk Reddi</h3>
<p>Gündem Akdeniz, yayınlanan haberlerin doğruluğu konusunda azami özeni gösterir. Ancak haberlerdeki bilgilerin eksiksiz ve hatasız olduğunu garanti etmez. Haberlere dayanarak alınan kararlardan doğacak zararlardan Gündem Akdeniz sorumlu tutulamaz.</p>

<h3>Kullanıcı Yorumları</h3>
<p>Kullanıcılar tarafından yapılan yorumların sorumluluğu yorum sahibine aittir. Gündem Akdeniz, uygunsuz bulunan yorumları önceden bildirimde bulunmaksızın kaldırma hakkını saklı tutar.</p>

<h3>Değişiklikler</h3>
<p>Gündem Akdeniz, bu kullanım koşullarını önceden bildirimde bulunmaksızın değiştirme hakkını saklı tutar. Güncel koşullar her zaman bu sayfada yayınlanacaktır.</p>',
            ],
            [
                'title' => 'Çerez Politikası',
                'slug' => 'cerez-politikasi',
                'sort_order' => 7,
                'body' => '<h2>Çerez Politikası</h2>
<p><em>Son güncelleme: Şubat 2026</em></p>

<p>Bu çerez politikası, Gündem Akdeniz web sitesinde kullanılan çerezler hakkında sizi bilgilendirmek amacıyla hazırlanmıştır.</p>

<h3>Çerez Nedir?</h3>
<p>Çerezler, web sitelerinin tarayıcınıza gönderdiği küçük metin dosyalarıdır. Tarayıcınızda saklanarak siteyi bir sonraki ziyaretinizde sizi tanımamıza yardımcı olurlar.</p>

<h3>Kullandığımız Çerez Türleri</h3>

<h4>Zorunlu Çerezler</h4>
<p>Sitenin düzgün çalışması için gerekli olan çerezlerdir. Oturum yönetimi ve güvenlik amacıyla kullanılır. Bu çerezler devre dışı bırakılamaz.</p>

<h4>Performans Çerezleri</h4>
<p>Ziyaretçilerin siteyi nasıl kullandığını anlamamıza yardımcı olan çerezlerdir. Google Analytics bu kategoride yer almaktadır.</p>

<h4>İşlevsellik Çerezleri</h4>
<p>Dil tercihi, tema seçimi gibi kişiselleştirme ayarlarınızı hatırlamak için kullanılır.</p>

<h4>Reklam Çerezleri</h4>
<p>Size ilgi alanlarınıza uygun reklamlar göstermek amacıyla kullanılabilir. Üçüncü taraf reklam ağları tarafından yerleştirilebilir.</p>

<h3>Çerezleri Yönetme</h3>
<p>Tarayıcı ayarlarınızdan çerezleri kontrol edebilir, silebilir veya engelleyebilirsiniz. Ancak çerezleri devre dışı bırakmanız durumunda sitenin bazı özellikleri düzgün çalışmayabilir.</p>

<h3>Tarayıcı Ayarları</h3>
<ul>
<li><strong>Chrome:</strong> Ayarlar → Gizlilik ve Güvenlik → Çerezler</li>
<li><strong>Firefox:</strong> Seçenekler → Gizlilik ve Güvenlik</li>
<li><strong>Safari:</strong> Tercihler → Gizlilik</li>
<li><strong>Edge:</strong> Ayarlar → Çerezler ve site izinleri</li>
</ul>

<p>Bu politika hakkında sorularınız için <strong>info@gundemakdeniz.com</strong> adresinden bize ulaşabilirsiniz.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::create($page);
        }
    }
}
