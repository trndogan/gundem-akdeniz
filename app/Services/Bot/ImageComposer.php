<?php

namespace App\Services\Bot;

use App\Helpers\PathHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Haber görselini üretir.
 *
 * Hibrit sıra:
 *   1. Kaynakta görsel varsa indirilir, kenarlardan kırpılır, karartılır ve
 *      alt banda başlık basılır (özgünleştirme + marka bütünlüğü).
 *   2. Görsel yoksa Gemini görsel modeliyle habere uygun görsel üretilir.
 *   3. O da olmazsa kategori rozetli marka kartı çizilir, böylece her haberin görseli olur.
 *
 * Çıktı: public_html/images/bot/YYYY/AA/slug.webp — sitenin mevcut görsel kuralıyla aynı;
 * veritabanında "bot/2026/08/slug.webp" göreli yolu tutulur.
 */
class ImageComposer
{
    private int $width;
    private int $height;
    private int $cropPercent;
    private int $darken;
    private bool $titleBand;
    private string $mode;
    private string $font;        // gövde/etiket: Roboto Bold
    private string $fontDisplay; // manşet: Anton (kondens, ağır)
    private string $fontUi;      // künye satırları: Oswald

    private string $coverHeadline = '';

    /** @var array<int,string> */
    private array $coverFacts = [];

    private string $coverEngine;
    private int $coverSimilarity;

    public function __construct()
    {
        $this->width = max(600, BotSettings::int('image_width'));
        $this->height = max(400, BotSettings::int('image_height'));
        $this->cropPercent = min(20, max(0, BotSettings::int('image_crop_percent')));
        $this->darken = min(80, max(0, BotSettings::int('image_darken')));
        $this->titleBand = BotSettings::bool('image_title_band');
        $this->mode = (string) BotSettings::get('image_mode');
        $this->coverEngine = (string) BotSettings::get('cover_engine');
        $this->coverSimilarity = max(50, min(95, BotSettings::int('ai_cover_similarity')));
        $this->font = storage_path('app/bot/fonts/Roboto-Bold.ttf');
        // Manşet fontu yoksa sessizce Roboto'ya düş, kapak yine üretilsin
        $anton = storage_path('app/bot/fonts/Anton-Regular.ttf');
        $oswald = storage_path('app/bot/fonts/Oswald.ttf');
        $this->fontDisplay = is_file($anton) ? $anton : $this->font;
        $this->fontUi = is_file($oswald) ? $oswald : $this->font;
    }

    /**
     * @return array{path:?string,mode:string,error:?string}  path = images klasörüne göre göreli
     */
    public function compose(
        string $title,
        ?string $sourceImageUrl,
        string $category = '',
        ?string $summary = null,
        string $coverHeadline = '',
        array $coverFacts = [],
        bool $aiKapak = true,
    ): array {
        $this->coverHeadline = $coverHeadline;
        $this->coverFacts = array_values(array_filter(array_map('trim', $coverFacts)));

        // Fotoğrafı olan haberlerde kapak kodla çizilir: gerçek fotoğraf solda,
        // manşet sağda. Yapay zekâya kesim yaptırmak, kaynakta olmayan insanları
        // uydurmasına yol açtı (31.08.2026); ayrıca görsel başına ücret ödetiyordu.
        // $aiKapak yalnızca fotoğrafsız vitrin haberlerinde afiş üretimi için kullanılır.

        // 0) Kırmızı manşet kapağı: gerçek fotoğraf + kodla çizilen tipografi
        if ($sourceImageUrl) {
            try {
                $foto = $this->fotoIndir($sourceImageUrl);
                if ($foto !== null && ! $this->belgeMi($foto)) {
                    $canvas = $this->kirmiziKapak(
                        $foto,
                        $this->coverHeadline !== '' ? $this->coverHeadline : $title,
                        $this->coverFacts[0] ?? '',
                    );

                    return ['path' => $this->finish($canvas, $title, $category, false), 'mode' => 'kirmizi', 'error' => null];
                }
            } catch (Throwable $e) {
                BotLogger::warning('Kırmızı kapak üretilemedi', ['hata' => $e->getMessage()], 'image');
            }
        }

        // 1) Fotoğrafsız vitrin haberi: kapağın tamamını yapay zekâ tasarlar.
        // Burada korunacak bir haber karesi yoktur; istem, tanınabilir gerçek kişi
        // üretmeyi açıkça yasaklar ve sahnenin genel kalmasını ister.
        if ($aiKapak && $this->coverEngine === 'ai' && ! $sourceImageUrl) {
            try {
                $sahne = $this->fromAiPoster($title, $category, $summary);
                if ($sahne !== null) {
                    $canvas = $this->kirmiziKapak(
                        $sahne,
                        $this->coverHeadline !== '' ? $this->coverHeadline : $title,
                        $this->coverFacts[0] ?? '',
                    );

                    return ['path' => $this->finish($canvas, $title, $category, false), 'mode' => 'ai_poster', 'error' => null];
                }
            } catch (Throwable $e) {
                BotLogger::warning('AI afiş üretilemedi, koda düşülüyor', ['hata' => $e->getMessage()], 'image');
            }
        }

        // 2) Kaynak görseli
        if ($this->mode !== 'ai' && $sourceImageUrl) {
            try {
                $canvas = $this->fromSourceImage($sourceImageUrl);
                if ($canvas !== null) {
                    return ['path' => $this->finish($canvas, $title, $category, true), 'mode' => 'processed', 'error' => null];
                }
            } catch (Throwable $e) {
                BotLogger::warning('Kaynak görseli işlenemedi', ['hata' => $e->getMessage()], 'image');
            }
        }

        // 3) Marka kartı
        //
        // Eskiden burada bir "AI fotoğrafı + üstüne kod yazısı" yolu vardı. Ürettiği
        // görünüm (düz fotoğrafın üstünde koca yazı kutusu) istenmedi, üstelik afiş
        // yolu başarısız olduğunda ikinci kez ücret ödetiyordu. Kaldırıldı: afiş
        // tutmazsa doğrudan tasarım kartına düşülür, o da ücretsizdir.
        try {
            $canvas = $this->brandCard($title, $category);

            return ['path' => $this->finish($canvas, $title, $category, false), 'mode' => 'placeholder', 'error' => null];
        } catch (Throwable $e) {
            BotLogger::error('Görsel üretilemedi', ['hata' => $e->getMessage()], 'image');

            return ['path' => null, 'mode' => 'none', 'error' => $e->getMessage()];
        }
    }

    // ── Kaynaklar ───────────────────────────────────────────────────────────

    /** @return \GdImage|null */
    private function fromSourceImage(string $url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $response = Http::withHeaders([
            'User-Agent' => SourceDetector::UA,
            'Referer' => $host ? 'https://' . $host : '',
        ])->timeout(30)->withoutVerifying()->get($url);

        if ($response->failed() || strlen($response->body()) < 2000) {
            return null;
        }

        $source = @imagecreatefromstring($response->body());
        if ($source === false) {
            return null;
        }

        // Kenarlardan kırp: kaynak görselden görsel olarak ayrış
        if ($this->cropPercent > 0) {
            $w = imagesx($source);
            $h = imagesy($source);
            $dx = (int) ($w * $this->cropPercent / 100);
            $dy = (int) ($h * $this->cropPercent / 100);
            $cropped = imagecrop($source, ['x' => $dx, 'y' => $dy, 'width' => $w - $dx * 2, 'height' => $h - $dy * 2]);
            if ($cropped !== false) {
                $source = $cropped;
            }
        }

        $canvas = $this->coverFit($source);

        if ($this->darken > 0) {
            $this->overlay($canvas, 0, 0, $this->width, $this->height, [0, 0, 0], $this->darken);
        }

        return $canvas;
    }


    /**
     * Kapağın tamamını yapay zekâya tasarlatır: yırtık kenar, ağır manşet,
     * fırça dokulu künye bantları ve konuya uygun ikonlar.
     *
     * Model bazen fotoğrafı korumak yerine sıfırdan uydurur. Haber sitesinde gerçek
     * kişilerin fotoğrafının değiştirilmesi kabul edilemez olduğu için çıktı renk
     * histogramıyla doğrulanır; tutmazsa bir kez daha denenir, yine tutmazsa null
     * döner ve kodun çizdiği manşet devreye girer.
     *
     * @return \GdImage|null
     */
    private function fromAiCover(string $imageUrl, string $category)
    {
        $apiKey = trim((string) BotSettings::get('gemini_api_key'));
        if ($apiKey === '') {
            return null;
        }

        $host = parse_url($imageUrl, PHP_URL_HOST);
        $indirilen = Http::withHeaders([
            'User-Agent' => SourceDetector::UA,
            'Referer' => $host ? 'https://' . $host : '',
        ])->timeout(30)->withoutVerifying()->get($imageUrl);

        if ($indirilen->failed() || strlen($indirilen->body()) < 2000) {
            return null;
        }

        $kaynak = @imagecreatefromstring($indirilen->body());
        if ($kaynak === false) {
            return null;
        }

        // Kaynak bir fotoğraf değil de belge/afiş ise yapay zekâya kesim yaptırma.
        //
        // 31.08.2026: taranmış bir ÇED belgesinden üretilen kapakta model, belgede
        // hiç bulunmayan bir insan kalabalığını sıfırdan çizip haber fotoğrafı gibi
        // sundu. Belgede kesilecek özne yoktur; model boşluğu uydurarak doldurur.
        // Böyle kaynaklarda kod şablonuna düşülür, belgenin kendisi gösterilir.
        if ($this->belgeMi($kaynak)) {
            BotLogger::warning('Kaynak fotoğraf değil (belge/afiş), AI kapak atlandı', [], 'image');

            return null;
        }

        // Benzerlik yalnızca kayıt için ölçülür, kapağı reddetmez.
        //
        // Kesme stilinde fotoğraf arka planından ayrıldığı için renk sinyalinin çoğu
        // kayboluyor. Ölçüm: doğru eşleşmeler 0.36 ve 0.70, yanlış eşleşmeler 0.45 ve
        // 0.45 verdi; doğru bir eşleşme yanlışların ikisinden de düşük çıktığı için
        // ayıran bir eşik yok. Kullanıcı bu stili bilerek seçti: model bir gün
        // fotoğraftaki kişileri uydurursa otomatik olarak yakalanmaz, skor kaydına
        // bakılarak sonradan incelenebilir.
        $hedefHistogram = $this->subjectHistogram($kaynak);
        $model = (string) BotSettings::get('ai_image_model');
        $prompt = $this->coverPrompt($category);

        for ($deneme = 1; $deneme <= 2; $deneme++) {
            $yanit = Http::timeout(200)->withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => base64_encode($indirilen->body())]],
                    ]]],
                    'generationConfig' => ['responseModalities' => ['IMAGE']],
                ]);

            if ($yanit->failed()) {
                BotLogger::warning("AI kapak isteği başarısız (HTTP {$yanit->status()})", ['model' => $model], 'image');
                continue;
            }

            foreach (data_get($yanit->json(), 'candidates.0.content.parts', []) as $parca) {
                $b64 = $parca['inlineData']['data'] ?? $parca['inline_data']['data'] ?? null;
                if ($b64 === null) continue;

                $ikili = base64_decode($b64, true);
                if ($ikili === false) continue;

                $uretilen = @imagecreatefromstring($ikili);
                if ($uretilen === false) continue;

                $skor = $this->histogramKesisimi(
                    $this->subjectHistogram($uretilen),
                    $hedefHistogram,
                );
                BotLogger::info('AI kapak üretildi', ['benzerlik' => round($skor, 3)], 'image');

                // AI kompozisyonu hazır gelir; kırpmak yazıyı keser, sığdırılır
                return $this->containFit($uretilen);
            }
        }

        return null;
    }

    /**
     * Fotoğrafsız haber için kapağın tamamını yapay zekâya tasarlatır.
     *
     * fromAiCover gerçek bir kareyi koruduğu için histogramla denetlenir; burada
     * korunacak kare yoktur, model hem grafiği hem görseli üretir. Bu yol olmadan
     * fotoğrafsız haberler düz lacivert marka kartına düşüyordu.
     *
     * @return \GdImage|null
     */
    private function fromAiPoster(string $title, string $category, ?string $summary)
    {
        $apiKey = trim((string) BotSettings::get('gemini_api_key'));
        if ($apiKey === '') {
            return null;
        }

        $yanit = $this->gorselIste(
            (string) BotSettings::get('ai_image_model'),
            $apiKey,
            [['text' => $this->posterPrompt($title, $category, $summary)]],
        );
        if ($yanit === null) {
            return null;
        }

        foreach (data_get($yanit, 'candidates.0.content.parts', []) as $parca) {
            $b64 = $parca['inlineData']['data'] ?? $parca['inline_data']['data'] ?? null;
            if ($b64 === null) continue;

            $ikili = base64_decode($b64, true);
            if ($ikili === false) continue;

            $uretilen = @imagecreatefromstring($ikili);
            if ($uretilen === false) continue;

            // Ham sahne döner; çerçeveyi ve yazıyı kirmiziKapak çizer
            return $uretilen;
        }

        return null;
    }

    /**
     * Görsel isteği atar ve 429'da bir kez daha dener.
     *
     * Gemini görsel kotası dar; tek bir 429 kapağı marka kartına düşürüyordu.
     *
     * @param  array<int,array<string,mixed>>  $parts
     * @return array<string,mixed>|null
     */
    private function gorselIste(string $model, string $apiKey, array $parts, int $timeout = 200): ?array
    {
        for ($deneme = 1; $deneme <= 2; $deneme++) {
            $yanit = Http::timeout($timeout)->withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => ['responseModalities' => ['IMAGE']],
                ]);

            if (! $yanit->failed()) {
                return $yanit->json();
            }

            BotLogger::warning("AI görsel isteği başarısız (HTTP {$yanit->status()})",
                ['model' => $model, 'deneme' => $deneme], 'image');

            // Kota hatası geçicidir, kısa bekleyip bir kez daha dene
            if ($yanit->status() !== 429 || $deneme === 2) {
                return null;
            }
            sleep(6);
        }

        return null;
    }

    /**
     * Fotoğrafsız haber için sahne görseli istemi.
     *
     * Yalnızca görsel istenir, üzerine yazı istenmez: manşeti ve kompozisyonu
     * kirmiziKapak kodla çizer, böylece tipografi bozulmaz ve düzen gerçek
     * fotoğraflı kapaklarla birebir aynı olur.
     *
     * İnsan üretimi tamamen yasaklanır. Model daha önce kaynakta olmayan bir
     * kalabalığı çizip haber fotoğrafı gibi sunmuştu (31.08.2026); insansız sahne
     * bu riski kökten kaldırır.
     */
    private function posterPrompt(string $title, string $category, ?string $summary): string
    {
        $konu = trim((string) $summary) !== '' ? mb_substr(trim((string) $summary), 0, 300) : $title;

        return "Bir haber görseli üret.\n\n"
            . "HABERİN KONUSU: {$konu}\n\n"
            . "KURALLAR:\n"
            . "- Görselde İNSAN OLMASIN. Yüz, el, silüet, kalabalık veya insan figürü çizme.\n"
            . "- Sahne yalnızca mekan, yapı, nesne, araç, ekipman, manzara veya sembol içersin.\n"
            . "- Gerçekçi bir fotoğraf estetiği olsun; çizim, illüstrasyon veya 3B render değil.\n"
            . "- Görselin üzerine HİÇBİR YAZI, logo, filigran veya rakam ekleme.\n"
            . '- En boy oranı ' . $this->width . ':' . $this->height . '.';
    }

    /**
     * Kapak tasarımı istemi.
     *
     * Kompozisyon: canlı kırmızı zemin ve hafif diyagonal şeritler, kişiler arka
     * planından kesilip kendi renkleriyle sola, iri kondens manşet sağa. Bu düzen
     * denenip onaylandı; renkli fotoğraf ve düz zemin varyantı daha zayıf durdu.
     *
     * Üst başlık modele bırakılmaz, künye bilgisinden verilir: haber grafiğindeki
     * her ifade doğrulanabilir olmalı, model kendi cümlesini uydurmamalı.
     */
    private function coverPrompt(string $category): string
    {
        $manset = $this->upper($this->coverHeadline !== '' ? $this->coverHeadline : $category);
        $ustBaslik = $this->coverFacts[0] ?? '';

        return "Sana gerçek bir haber fotoğrafı veriyorum. Bundan bir haber manşet kapağı tasarla.\n\n"
            . "KOMPOZİSYON:\n"
            . "- Zemin baştan sona canlı kırmızı (#e60000) olsun. Üzerinde yalnızca birkaç adet, aralıkları çok geniş ve zar zor seçilen koyu kırmızı diyagonal şerit bulunsun; sık desen olmasın.\n"
            . "- Fotoğraftaki kişiyi veya grubu arka planından kes ve sola yerleştir. Fotoğraf kendi renkleriyle kalsın, gri tonlamaya çevirme. Yüzler, duruşlar, kıyafetler ve renkler AYNEN korunsun, yeniden çizme.\n"
            . "- Kesilen fotoğraf karenin üst ve alt kenarına dayansın; üstte ve altta boşluk kalmasın.\n"
            . "- Sağ tarafa çok iri, kalın, sıkıştırılmış beyaz manşet: {$manset}\n"
            . ($ustBaslik !== ''
                ? "- Manşetin üstünde küçük siyah bir bant içinde üst başlık: " . $this->upper($ustBaslik) . "\n"
                : "- Manşetin üstünde küçük siyah bir bant içinde kısa üst başlık olsun.\n")
            . "- Metinler BÜYÜK HARF, Türkçe karakterler eksiksiz. Harfler birbirine girmesin.\n"
            . '- En boy oranı ' . $this->width . ':' . $this->height . '.';
    }

    /** Kaynak fotoğrafı indirir; açılamazsa null döner */
    private function fotoIndir(string $url): ?\GdImage
    {
        $host = parse_url($url, PHP_URL_HOST);
        $yanit = Http::withHeaders([
            'User-Agent' => SourceDetector::UA,
            'Referer' => $host ? 'https://' . $host : '',
        ])->timeout(30)->withoutVerifying()->get($url);

        if ($yanit->failed() || strlen($yanit->body()) < 2000) return null;

        $im = @imagecreatefromstring($yanit->body());

        return $im === false ? null : $im;
    }

    /**
     * Kırmızı manşet kapağı: solda gerçek fotoğraf, sağda manşet.
     *
     * Tamamı kodla çizilir. Yapay zekâ kullanılmaz, çünkü kesim yaptırıldığında
     * model kaynakta olmayan insanları uydurup haber fotoğrafı gibi sunabiliyordu
     * (31.08.2026, taranmış ÇED belgesinden üretilen kapak). Burada fotoğraf
     * olduğu gibi yerleştirilir; ne eklenir ne çıkarılır. Ayrıca görsel başına
     * ücret ödenmez ve yazılar bozulmaz.
     */
    private function kirmiziKapak(\GdImage $foto, string $manset, string $ustBaslik): \GdImage
    {
        $tuval = imagecreatetruecolor($this->width, $this->height);
        $beyaz = imagecolorallocate($tuval, 255, 255, 255);
        $siyah = imagecolorallocate($tuval, 12, 12, 14);
        $kirmizi = imagecolorallocate($tuval, 230, 0, 0);
        imagefilledrectangle($tuval, 0, 0, $this->width, $this->height, $kirmizi);

        // Seyrek, zar zor seçilen diyagonal şeritler
        $koyu = imagecolorallocatealpha($tuval, 150, 0, 0, 96);
        $genislik = (int) ($this->width * 0.07);
        for ($x = -$this->height; $x < $this->width; $x += $genislik * 3) {
            imagefilledpolygon($tuval, [
                $x, $this->height,
                $x + $genislik, $this->height,
                $x + $genislik + $this->height, 0,
                $x + $this->height, 0,
            ], $koyu);
        }

        // Fotoğraf solda, üst ve alt kenara dayalı, oranı bozulmadan kırpılır
        $fw = (int) ($this->width * 0.47);
        $this->fotoYerlestir($tuval, $foto, 0, 0, $fw, $this->height);

        // Sağ sütun
        $pad = (int) ($this->width * 0.035);
        $sol = $fw + $pad;
        $ic = $this->width - $sol - $pad;

        // Dikeyde güvenli boşluk: metin bloğu kenarlara dayanmasın
        $padY = (int) ($this->height * 0.09);
        $alan = $this->height - $padY * 2;

        $ustBoyut = 21;
        $ustYuksek = 0;
        $ustMetin = '';
        if ($ustBaslik !== '') {
            $ustMetin = $this->upper(mb_substr(trim($ustBaslik), 0, 40));
            $ustYuksek = $this->glyphHeight($ustBoyut, $this->fontUi) + 20 + 22;   // bant + altındaki boşluk
        }

        // Manşet sütun genişliğini doldursun.
        //
        // fitLines punto yalnızca küçültüyordu; kısa manşetlerde satırlar sütunun
        // yarısında kalıp sağda geniş boşluk bırakıyordu. Burada her satır sayısı
        // için puntoyu genişliğe oturtup dikey alana sığanların en büyüğü seçilir.
        $satirlar = [];
        $boyut = 30;
        $satirYuksek = 0;

        for ($adet = 1; $adet <= 5; $adet++) {
            $adaylar = $this->satirlaraBol($this->upper($manset), $adet, $this->fontDisplay);
            if (count($adaylar) !== $adet && $adet > 1) continue;

            // 100 puntoda ölçülen en geniş satırdan hedef punto oranla bulunur
            $enGenis = 0;
            foreach ($adaylar as $satir) {
                $enGenis = max($enGenis, $this->textWidth($satir, 100, $this->fontDisplay));
            }
            if ($enGenis === 0) continue;

            $aday = (int) min(96, floor($ic / $enGenis * 100));
            if ($aday < 26) continue;

            // Satır yüksekliği bu satırların gerçek kutusundan ölçülür.
            // lineHeight() genel bir dize üzerinden ölçtüğü için puntonun iki katına
            // yakın değer döndürüyordu; blok sığmıyor sanılıp punto gereksiz düşüyordu.
            $enYuksek = 0;
            foreach ($adaylar as $satir) {
                $enYuksek = max($enYuksek, $this->glyphHeight($aday, $this->fontDisplay, $satir));
            }
            $ay = $enYuksek + (int) round($aday * 0.30);
            if ($ustYuksek + $adet * $ay > $alan) continue;

            if ($aday > $boyut) {
                $boyut = $aday;
                $satirlar = $adaylar;
                $satirYuksek = $ay;
            }
        }

        // Hiçbiri sığmadıysa daraltarak zorla sığdır
        if ($satirlar === []) {
            [$satirlar, $boyut] = $this->fitLines($this->upper($manset), $ic, 5, 48, 26, $this->fontDisplay);
            $satirYuksek = $this->lineHeight($boyut, $this->fontDisplay) + 6;
        }

        $blok = $ustYuksek + count($satirlar) * $satirYuksek;
        $y = $padY + (int) (($alan - $blok) / 2);

        if ($ustMetin !== '') {
            $mg = $this->textWidth($ustMetin, $ustBoyut, $this->fontUi);
            $my = $this->glyphHeight($ustBoyut, $this->fontUi);
            imagefilledrectangle($tuval, $sol, $y, min($this->width - $pad, $sol + $mg + 32), $y + $my + 20, $siyah);
            imagettftext($tuval, $ustBoyut, 0, $sol + 16, $y + $my + 10, $beyaz, $this->fontUi, $ustMetin);
            $y += $ustYuksek;
        }

        // Taban çizgisi ölçülen harf yüksekliğinden alınır; punto eklemek kaydırıyordu
        $tabanFarki = $this->glyphHeight($boyut, $this->fontDisplay, $satirlar[0] ?? 'A');
        foreach ($satirlar as $i => $satir) {
            imagettftext($tuval, $boyut, 0, $sol, $y + $tabanFarki + $i * $satirYuksek,
                $beyaz, $this->fontDisplay, $satir);
        }

        return $tuval;
    }

    /**
     * Metni istenen sayıda dengeli satıra böler.
     *
     * Hedef genişlik toplam genişliğin satır sayısına bölümüdür; satırlar birbirine
     * yakın uzunlukta çıkar, böylece punto büyütüldüğünde blok düzgün bir dikdörtgen
     * oluşturur.
     *
     * @return array<int,string>
     */
    private function satirlaraBol(string $metin, int $adet, string $font): array
    {
        $kelimeler = preg_split('/\s+/u', trim($metin)) ?: [];
        if ($kelimeler === []) return [];
        if (count($kelimeler) <= $adet) return $adet === 1 ? [implode(' ', $kelimeler)] : $kelimeler;

        $hedef = $this->textWidth($metin, 100, $font) / $adet;

        $satirlar = [];
        $gecerli = '';
        foreach ($kelimeler as $kelime) {
            $deneme = $gecerli === '' ? $kelime : $gecerli . ' ' . $kelime;
            if ($gecerli !== ''
                && count($satirlar) < $adet - 1
                && $this->textWidth($deneme, 100, $font) > $hedef) {
                $satirlar[] = $gecerli;
                $gecerli = $kelime;
                continue;
            }
            $gecerli = $deneme;
        }
        if ($gecerli !== '') $satirlar[] = $gecerli;

        return $satirlar;
    }

    /** Metnin piksel genişliği */
    private function textWidth(string $metin, int $boyut, string $font): int
    {
        $kutu = imagettfbbox($boyut, 0, $font, $metin);

        return abs($kutu[2] - $kutu[0]);
    }

    /** Fotoğrafı hedef dikdörtgene oranını bozmadan, kırparak yerleştirir */
    private function fotoYerlestir(\GdImage $tuval, \GdImage $foto, int $x, int $y, int $w, int $h): void
    {
        $kw = imagesx($foto);
        $kh = imagesy($foto);
        $olcek = max($w / $kw, $h / $kh);
        $yw = (int) round($kw * $olcek);
        $yh = (int) round($kh * $olcek);

        imagecopyresampled($tuval, $foto, $x, $y,
            (int) (($yw - $w) / 2 / $olcek), (int) (($yh - $h) / 2 / $olcek),
            $w, $h, (int) ($w / $olcek), (int) ($h / $olcek));
    }

    /**
     * Kaynak görsel fotoğraf mı, belge/afiş mi.
     *
     * Belgeler ve afişler düz renk alanlarından oluşur: birkaç renk kareyi kaplar.
     * Fotoğrafta renk dağılımı geniştir. Ölçüt en sık 8 rengin kapladığı oran;
     * ölçümde afişler %90 üstü, fotoğraflar %8-35 bandında çıktı. Eşik ikisinin
     * arasına, %60'a konuldu.
     */
    private function belgeMi(\GdImage $im): bool
    {
        $w = imagesx($im);
        $h = imagesy($im);
        $adim = max(1, (int) (min($w, $h) / 120));

        $kova = [];
        $toplam = 0;
        for ($y = 0; $y < $h; $y += $adim) {
            for ($x = 0; $x < $w; $x += $adim) {
                $rgb = imagecolorat($im, $x, $y);
                // 5 bit hassasiyet: hafif gradyanlar aynı kovaya düşsün
                $anahtar = ((($rgb >> 16) & 0xF8) << 8) | ((($rgb >> 8) & 0xF8) << 3) | (($rgb & 0xF8) >> 3);
                $kova[$anahtar] = ($kova[$anahtar] ?? 0) + 1;
                $toplam++;
            }
        }

        if ($toplam === 0) return false;

        arsort($kova);

        return array_sum(array_slice($kova, 0, 8)) / $toplam >= 0.60;
    }

    /**
     * Öznenin renk histogramı: kırmızı zemin pikselleri sayılmaz.
     *
     * Kapak stilinde fotoğraf arka planından kesilip düz kırmızı zemine oturuyor.
     * Tüm kareyi karşılaştırmak anlamsız, zemin baskın çıkıyor. Zemin rengi hem
     * kaynakta hem üretilen kapakta atlanır; böylece Türk bayrağı gibi kırmızı
     * öğeler iki tarafta da aynı şekilde elenir ve karşılaştırma adil kalır.
     *
     * @return array<int,float>
     */
    private function subjectHistogram(\GdImage $im, int $bins = 4): array
    {
        $hist = array_fill(0, $bins ** 3, 0.0);
        $w = imagesx($im);
        $h = imagesy($im);
        $adim = max(1, (int) (min($w, $h) / 150));
        $toplam = 0;

        for ($y = 0; $y < $h; $y += $adim) {
            for ($x = 0; $x < $w; $x += $adim) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Doygun kırmızı = zemin, sayıma girmez
                if ($r > 110 && $r - $g > 60 && $r - $b > 60) continue;

                $anahtar = (int) ($r * $bins / 256) * $bins * $bins
                    + (int) ($g * $bins / 256) * $bins
                    + (int) ($b * $bins / 256);
                $hist[$anahtar]++;
                $toplam++;
            }
        }

        if ($toplam === 0) return $hist;
        foreach ($hist as $i => $v) $hist[$i] = $v / $toplam;

        return $hist;
    }

    /**
     * Renk histogramı. AI çıktısı kırpıp yakınlaştırabildiği için piksel
     * karşılaştırması işe yaramaz; renk dağılımı sahne kimliğini korur.
     *
     * @return array<int,float>
     */
    private function histogram(\GdImage $im, int $x0, int $y0, int $w, int $h, int $bins = 4): array
    {
        $hist = array_fill(0, $bins ** 3, 0.0);
        $adim = 3;
        $sayac = 0;
        $maxX = min($x0 + $w, imagesx($im));
        $maxY = min($y0 + $h, imagesy($im));

        for ($y = $y0; $y < $maxY; $y += $adim) {
            for ($x = $x0; $x < $maxX; $x += $adim) {
                $rgb = imagecolorat($im, $x, $y);
                $r = (($rgb >> 16) & 0xFF) * $bins >> 8;
                $g = (($rgb >> 8) & 0xFF) * $bins >> 8;
                $b = ($rgb & 0xFF) * $bins >> 8;
                $hist[$r * $bins * $bins + $g * $bins + $b]++;
                $sayac++;
            }
        }

        if ($sayac > 0) {
            foreach ($hist as $i => $v) {
                $hist[$i] = $v / $sayac;
            }
        }

        return $hist;
    }

    /** İki histogramın kesişimi: 1.0 aynı sahne, 0.0 tamamen farklı */
    private function histogramKesisimi(array $a, array $b): float
    {
        $skor = 0.0;
        foreach ($a as $i => $v) {
            $skor += min($v, $b[$i] ?? 0.0);
        }

        return $skor;
    }


    /** @return \GdImage */
    /**
     * Fotoğrafsız haber için tasarım kapak.
     *
     * Eski hali düz lacivert gradyan üstüne ortalanmış beyaz yazıydı ve amatör
     * duruyordu. Artık kapak dilinin geri kalanıyla aynı: koyu zemin, sola yaslı
     * kondens manşet, kırmızı künye bantları, köşede diyagonal aksan.
     *
     * Bu yol para harcamaz; vitrin dışı ve fotoğrafsız haberlerin tamamı buraya
     * düştüğü için görünürlüğü yüksektir.
     */
    private function brandCard(string $title, string $category)
    {
        $canvas = imagecreatetruecolor($this->width, $this->height);
        $beyaz = imagecolorallocate($canvas, 255, 255, 255);
        $kirmizi = $this->accent($canvas);

        // Koyu zemin: lacivert değil, neredeyse siyah. Kırmızı üstünde daha sert durur.
        [$ur, $ug, $ub] = [18, 20, 26];
        [$ar, $ag, $ab] = [6, 7, 10];
        for ($y = 0; $y < $this->height; $y++) {
            $t = $y / $this->height;
            imageline($canvas, 0, $y, $this->width, $y, imagecolorallocate($canvas,
                (int) ($ur + ($ar - $ur) * $t),
                (int) ($ug + ($ag - $ug) * $t),
                (int) ($ub + ($ab - $ub) * $t)));
        }

        // Sağ üstte diyagonal kırmızı kama: boş alanı doldurur, kompozisyona yön verir
        $kama = imagecolorallocatealpha($canvas, 254, 0, 0, 96);
        imagefilledpolygon($canvas, [
            $this->width, 0,
            $this->width, (int) ($this->height * 0.62),
            (int) ($this->width * 0.58), 0,
        ], $kama);

        // İnce diyagonal tarama: düz zeminin ucuz görünmesini engeller
        $cizgi = imagecolorallocatealpha($canvas, 255, 255, 255, 122);
        for ($x = -$this->height; $x < $this->width; $x += 14) {
            imageline($canvas, $x, $this->height, $x + $this->height, 0, $cizgi);
        }

        $pad = (int) ($this->width * 0.055);
        $ic = (int) ($this->width * 0.66);

        $altKenar = $this->chip($canvas, $pad, $pad, $category);

        $manset = $this->coverHeadline !== '' ? $this->coverHeadline : $title;
        [$satirlar, $boyut] = $this->fitLines($this->upper($manset), $ic, 4, 52, 28, $this->fontDisplay);
        $satirYuksek = $this->lineHeight($boyut, $this->fontDisplay);

        $olgular = array_slice($this->coverFacts, 0, 3);
        $olguBoyut = 18;
        $olguYuksek = $this->lineHeight($olguBoyut, $this->fontUi) + 15;

        $blok = count($satirlar) * $satirYuksek + ($olgular ? 26 + count($olgular) * $olguYuksek : 0);
        $y = max($altKenar + 40, (int) (($this->height - $blok) / 2)) + $boyut;

        foreach ($satirlar as $satir) {
            imagettftext($canvas, $boyut, 0, $pad, $y, $beyaz, $this->fontDisplay, $satir);
            $y += $satirYuksek;
        }

        $y += 26;
        foreach ($olgular as $olgu) {
            $metin = $this->upper(mb_substr(trim($olgu), 0, 46));
            $kutu = imagettfbbox($olguBoyut, 0, $this->fontUi, $metin);
            $genislik = abs($kutu[2] - $kutu[0]);
            $yukseklik = abs($kutu[7] - $kutu[1]);

            imagefilledrectangle($canvas, $pad, $y - $yukseklik - 8,
                min($this->width - $pad, $pad + $genislik + 28), $y + 8, $kirmizi);
            imagettftext($canvas, $olguBoyut, 0, $pad + 14, $y, $beyaz, $this->fontUi, $metin);
            $y += $olguYuksek;
        }

        imagefilledrectangle($canvas, 0, $this->height - 7, $this->width, $this->height, $kirmizi);
        $this->brand($canvas, $pad, $this->height - $pad, 'left');

        return $canvas;
    }

    // ── Ortak işlemler ──────────────────────────────────────────────────────

    // Not: imagedestroy() çağrılmıyor. PHP 8.0'dan beri işlevsiz (GdImage nesnesi
    // çöp toplayıcıya bırakılır), PHP 8.5'te ise deprecation uyarısı üretiyor.

    /** Hedef orana göre merkezden kırpıp ölçekler; siyah kenar bırakmaz */
    private function coverFit(\GdImage $source): \GdImage
    {
        $sw = imagesx($source);
        $sh = imagesy($source);
        $scale = max($this->width / $sw, $this->height / $sh);
        $nw = (int) ceil($sw * $scale);
        $nh = (int) ceil($sh * $scale);

        $canvas = imagecreatetruecolor($this->width, $this->height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 0, 0, 0));
        imagecopyresampled(
            $canvas, $source,
            (int) (($this->width - $nw) / 2), (int) (($this->height - $nh) / 2),
            0, 0, $nw, $nh, $sw, $sh,
        );

        return $canvas;
    }

    /**
     * Görseli kırpmadan hedef ölçüye sığdırır, artan yeri görselin baskın rengiyle doldurur.
     * Kompozisyonu hazır gelen görsellerde (AI kapağı, kurum afişi) kırpma yazıyı keser.
     */
    private function containFit(\GdImage $source): \GdImage
    {
        $sw = imagesx($source);
        $sh = imagesy($source);
        $scale = min($this->width / $sw, $this->height / $sh);
        $nw = max(1, (int) round($sw * $scale));
        $nh = max(1, (int) round($sh * $scale));

        [$r, $g, $b] = $this->dominantColor($source);
        $canvas = imagecreatetruecolor($this->width, $this->height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, $r, $g, $b));
        imagecopyresampled(
            $canvas, $source,
            (int) (($this->width - $nw) / 2), (int) (($this->height - $nh) / 2),
            0, 0, $nw, $nh, $sw, $sh,
        );

        return $canvas;
    }

    /**
     * Görselin kenarlarındaki baskın renk. Sığdırma sonrası kalan boşluk bu renkle
     * doldurulunca görsel çerçeveye kaynaşır, siyah bant gibi durmaz.
     *
     * @return array{0:int,1:int,2:int}
     */
    private function dominantColor(\GdImage $im): array
    {
        $w = imagesx($im);
        $h = imagesy($im);
        $kova = [];

        for ($i = 0; $i < 60; $i++) {
            foreach ([[(int) ($w * $i / 60), 2], [(int) ($w * $i / 60), $h - 3],
                      [2, (int) ($h * $i / 60)], [$w - 3, (int) ($h * $i / 60)]] as [$x, $y]) {
                $rgb = imagecolorat($im, max(0, min($w - 1, $x)), max(0, min($h - 1, $y)));
                $anahtar = ((($rgb >> 16) & 0xF0) << 8) | ((($rgb >> 8) & 0xF0) << 4) | (($rgb & 0xF0));
                $kova[$anahtar] = ($kova[$anahtar] ?? 0) + 1;
            }
        }

        if ($kova === []) {
            return [12, 16, 28];
        }

        arsort($kova);
        $anahtar = array_key_first($kova);

        return [($anahtar >> 12) & 0xF0 | 0x08, ($anahtar >> 4) & 0xF0 | 0x08, $anahtar & 0xF0 | 0x08];
    }

    private function overlay(\GdImage $canvas, int $x, int $y, int $w, int $h, array $rgb, int $opacity): void
    {
        $layer = imagecreatetruecolor($w, $h);
        imagefill($layer, 0, 0, imagecolorallocate($layer, $rgb[0], $rgb[1], $rgb[2]));
        imagecopymerge($canvas, $layer, $x, $y, 0, 0, $w, $h, $opacity);
    }

    /** Kapak şablonunu uygular ve webp olarak kaydeder */
    private function finish(\GdImage $canvas, string $title, string $category, bool $drawOverlay): string
    {
        if ($drawOverlay && $this->titleBand && $title !== '') {
            $this->renderCover($canvas, $title, $category);
        }

        $relative = 'bot/' . now()->format('Y/m') . '/' . $this->filename($title);
        $absolute = $this->imagesRoot() . '/' . $relative;
        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        imagewebp($canvas, $absolute, 85);

        return $relative;
    }

    // ── Kapak şablonları ────────────────────────────────────────────────────

    public const TEMPLATES = [
        'auto' => 'Otomatik (habere göre değişir)',
        'scrim' => 'Degrade perde',
        'band' => 'Alt bant',
        'panel' => 'Yan panel',
        'card' => 'İçe gömülü kart',
        'manset' => 'Manşet (yırtık bölme + künye)',
    ];

    /**
     * Şablonu seçer ve çizer.
     *
     * Seçim başlığın hash'inden yapılır: aynı haber her zaman aynı kapağı üretir
     * (görsel yenilendiğinde şaşırtıcı değişim olmaz) ama haberden habere değişir,
     * böylece ana sayfa tek tip görünmez.
     */
    private function renderCover(\GdImage $canvas, string $title, string $category): void
    {
        $secim = (string) BotSettings::get('image_template');
        if ($secim === '' || $secim === 'auto' || ! isset(self::TEMPLATES[$secim])) {
            // Künye bilgisi varsa manşet şablonu da yarışır, yoksa dolu görünmez
            $adaylar = $this->coverFacts !== [] ? ['manset', 'manset', 'scrim', 'band', 'card'] : ['scrim', 'band', 'panel', 'card'];
            $secim = $adaylar[hexdec(substr(md5($title), 0, 4)) % count($adaylar)];
        }

        if ($secim === 'manset' && $this->coverFacts === [] && $this->coverHeadline === '') {
            $secim = 'scrim';   // manşet için içerik yoksa sade şablona düş
        }

        // Bir şablondaki hata görseli komple kaybettirmemeli: en sade şablona düş.
        // coverManset yazılmadan çağrıldığı dönemde haberler marka kartına düşüyordu.
        try {
            match ($secim) {
                'manset' => $this->coverManset($canvas, $title, $category),
                'band' => $this->coverBand($canvas, $title, $category),
                'panel' => $this->coverPanel($canvas, $title, $category),
                'card' => $this->coverCard($canvas, $title, $category),
                default => $this->coverScrim($canvas, $title, $category),
            };
        } catch (Throwable $e) {
            BotLogger::warning('Kapak şablonu çizilemedi, sade şablona düşülüyor', [
                'sablon' => $secim, 'hata' => $e->getMessage(),
            ], 'image');
            $this->coverScrim($canvas, $title, $category);
        }
    }

    /**
     * Manşet: solda künye paneli, sağda fotoğraf açıkta.
     *
     * Şablon listesinde ve seçim havuzunda vardı ama metodu hiç yazılmamıştı;
     * seçildiği her turda ölümcül hata verip haberi düz marka kartına düşürüyordu.
     *
     * Metin fotoğrafın üstünü kaplamaz, kendi panelinde durur: haber karesi
     * okunur kalır.
     */
    private function coverManset(\GdImage $canvas, string $title, string $category): void
    {
        $panel = (int) ($this->width * 0.5);
        $pad = (int) ($this->width * 0.045);
        $ic = $panel - $pad * 2;
        $beyaz = imagecolorallocate($canvas, 255, 255, 255);

        $this->overlay($canvas, 0, 0, $panel, $this->height, [8, 12, 22], 93);
        imagefilledrectangle($canvas, $panel - 5, 0, $panel, $this->height, $this->accent($canvas));

        $manset = $this->coverHeadline !== '' ? $this->coverHeadline : $title;
        [$satirlar, $boyut] = $this->fitLines($this->upper($manset), $ic, 4, 46, 26, $this->fontDisplay);
        $satirYuksek = $this->lineHeight($boyut, $this->fontDisplay);

        $olgular = array_slice($this->coverFacts, 0, 3);
        $olguBoyut = 17;
        $olguYuksek = $this->lineHeight($olguBoyut, $this->fontUi) + 14;

        $rozetY = $pad;
        $altKenar = $this->chip($canvas, $pad, $rozetY, $category);

        // Blok dikeyde ortalanır, rozetin altında kalacak şekilde aşağı itilir
        $blok = count($satirlar) * $satirYuksek + ($olgular ? 24 + count($olgular) * $olguYuksek : 0);
        $y = max($altKenar + 34, (int) (($this->height - $blok) / 2)) + $boyut;

        foreach ($satirlar as $satir) {
            imagettftext($canvas, $boyut, 0, $pad, $y, $beyaz, $this->fontDisplay, $satir);
            $y += $satirYuksek;
        }

        $y += 24;
        foreach ($olgular as $olgu) {
            $metin = $this->upper(mb_substr(trim($olgu), 0, 46));
            $kutu = imagettfbbox($olguBoyut, 0, $this->fontUi, $metin);
            $genislik = abs($kutu[2] - $kutu[0]);
            $yukseklik = abs($kutu[7] - $kutu[1]);

            imagefilledrectangle($canvas, $pad, $y - $yukseklik - 7,
                min($panel - 14, $pad + $genislik + 26), $y + 7, $this->accent($canvas));
            imagettftext($canvas, $olguBoyut, 0, $pad + 13, $y, $beyaz, $this->fontUi, $metin);
            $y += $olguYuksek;
        }

        $this->brand($canvas, $pad, $this->height - $pad, 'left');
    }

    /** Alttan yukarı yumuşayan degrade perde, sol kenarda kırmızı aksan */
    private function coverScrim(\GdImage $canvas, string $title, string $category): void
    {
        $pad = (int) ($this->width * 0.05);
        $this->gradientScrim($canvas, (int) ($this->height * 0.42), 0.92);

        $maxWidth = $this->width - $pad * 2 - 14;
        [$lines, $size] = $this->fitLines($title, $maxWidth, 3, 44, 28);
        $lineHeight = $this->lineHeight($size);
        $blockHeight = count($lines) * $lineHeight;

        $chipY = $this->height - $pad - $blockHeight - $this->chipHeight() - 18;
        $this->chip($canvas, $pad + 14, $chipY, $category);

        $y = $this->height - $pad - $blockHeight + $size;
        $barTop = $y - $size - 4;
        imagefilledrectangle($canvas, $pad, $barTop, $pad + 5, $barTop + $blockHeight + 6, $this->accent($canvas));

        foreach ($lines as $line) {
            $this->text($canvas, $line, $pad + 20, $y, $size, true);
            $y += $lineHeight;
        }

        $this->brand($canvas, $this->width - $pad, $this->height - $pad + 4, 'right');
    }

    /** Klasik alt bant, üst kenarında ince kırmızı çizgi */
    private function coverBand(\GdImage $canvas, string $title, string $category): void
    {
        $pad = (int) ($this->width * 0.042);
        $maxWidth = $this->width - $pad * 2;
        [$lines, $size] = $this->fitLines($title, $maxWidth, 3, 40, 26);
        $lineHeight = $this->lineHeight($size);
        $chipHeight = $this->chipHeight();
        $chipGap = 18;

        $bandHeight = $pad + $chipHeight + $chipGap + count($lines) * $lineHeight + $pad;
        $bandY = $this->height - $bandHeight;

        $this->overlay($canvas, 0, $bandY, $this->width, $bandHeight, [12, 16, 28], 90);
        imagefilledrectangle($canvas, 0, $bandY, $this->width, $bandY + 3, $this->accent($canvas));

        $chipBottom = $this->chip($canvas, $pad, $bandY + $pad, $category);

        $y = $chipBottom + $chipGap + $size;
        foreach ($lines as $line) {
            $this->text($canvas, $line, $pad, $y, $size, false);
            $y += $lineHeight;
        }

        $this->brand($canvas, $this->width - $pad, $this->height - 18, 'right');
    }

    /** Solda dikey panel, fotoğraf sağda kalır */
    private function coverPanel(\GdImage $canvas, string $title, string $category): void
    {
        $panelWidth = (int) ($this->width * 0.46);
        $pad = (int) ($this->width * 0.04);

        $this->overlay($canvas, 0, 0, $panelWidth, $this->height, [10, 14, 26], 88);
        imagefilledrectangle($canvas, $panelWidth - 4, 0, $panelWidth, $this->height, $this->accent($canvas));

        $maxWidth = $panelWidth - $pad * 2;
        [$lines, $size] = $this->fitLines($title, $maxWidth, 5, 38, 24);
        $lineHeight = $this->lineHeight($size);
        $blockHeight = count($lines) * $lineHeight;

        $chipY = (int) (($this->height - $blockHeight) / 2) - $this->chipHeight() - 20;
        $this->chip($canvas, $pad, $chipY, $category);

        $y = (int) (($this->height - $blockHeight) / 2) + $size;
        foreach ($lines as $line) {
            $this->text($canvas, $line, $pad, $y, $size, false);
            $y += $lineHeight;
        }

        $this->brand($canvas, $pad, $this->height - $pad, 'left');
    }

    /** Fotoğrafın üstüne oturan içe gömülü koyu kart */
    private function coverCard(\GdImage $canvas, string $title, string $category): void
    {
        $margin = (int) ($this->width * 0.04);
        $inner = (int) ($this->width * 0.035);
        $maxWidth = $this->width - $margin * 2 - $inner * 2;

        [$lines, $size] = $this->fitLines($title, $maxWidth, 3, 40, 26);
        $lineHeight = $this->lineHeight($size);
        $chipHeight = $this->chipHeight();
        $chipGap = 20;

        // Kart yüksekliği parçalardan toplanır; rozet ve başlık kenardan taşmaz
        $cardHeight = $inner + $chipHeight + $chipGap + count($lines) * $lineHeight + $inner;
        $cardY = $this->height - $margin - $cardHeight;

        $this->gradientScrim($canvas, (int) ($this->height * 0.5), 0.55);
        $this->overlay($canvas, $margin, $cardY, $this->width - $margin * 2, $cardHeight, [10, 14, 26], 92);
        imagefilledrectangle($canvas, $margin, $cardY, $margin + 6, $cardY + $cardHeight, $this->accent($canvas));

        $x = $margin + $inner;
        $chipBottom = $this->chip($canvas, $x, $cardY + $inner, $category);

        // Marka rozetle aynı hizada sağa yaslanır; kartın yüksekliğini artırmaz
        $this->brand($canvas, $this->width - $margin - $inner, $chipBottom - 4, 'right');

        $y = $chipBottom + $chipGap + $size;
        foreach ($lines as $line) {
            $this->text($canvas, $line, $x, $y, $size, false);
            $y += $lineHeight;
        }
    }

    // ── Çizim yardımcıları ──────────────────────────────────────────────────

    private function accent(\GdImage $canvas): int
    {
        return imagecolorallocate($canvas, 254, 0, 0);
    }

    /** Alttan yukarı saydamlaşan siyah perde */
    private function gradientScrim(\GdImage $canvas, int $height, float $strength): void
    {
        $start = $this->height - $height;
        imagealphablending($canvas, true);

        for ($y = $start; $y < $this->height; $y++) {
            $t = ($y - $start) / max(1, $height);
            $opacity = $strength * ($t * $t);                    // altta yoğun, yukarı doğru hızla açılır
            $alpha = (int) round(127 * (1 - $opacity));
            imageline($canvas, 0, $y, $this->width, $y, imagecolorallocatealpha($canvas, 0, 0, 0, $alpha));
        }
    }

    /**
     * Kategori rozeti: kırmızı zemin, harf aralıklı küçük büyük harf.
     *
     * @return int rozetin alt kenarı
     */
    private function chip(\GdImage $canvas, int $x, int $y, string $category): int
    {
        $text = $this->upper(trim($category) !== '' ? $category : 'HABER');
        $size = 15;
        $padX = 14;
        $padY = 9;

        $width = $this->trackedWidth($text, $size, 2);
        $box = imagettfbbox($size, 0, $this->font, $text);
        $height = abs($box[7] - $box[1]);

        imagefilledrectangle($canvas, $x, $y, $x + $width + $padX * 2, $y + $height + $padY * 2, $this->accent($canvas));
        $this->drawTracked($canvas, $text, $x + $padX, $y + $padY + $height, $size, 2, imagecolorallocate($canvas, 255, 255, 255));

        return $y + $height + $padY * 2;
    }

    /** Rozetin kaplayacağı yükseklik (kart yüksekliğini önceden hesaplamak için) */
    private function chipHeight(int $size = 15, int $padY = 9): int
    {
        $box = imagettfbbox($size, 0, $this->font, 'HABER');

        return abs($box[7] - $box[1]) + $padY * 2;
    }

    /** Sağ veya sol alta site adı */
    private function brand(\GdImage $canvas, int $x, int $y, string $align): void
    {
        $text = $this->upper((string) BotSettings::get('watermark_text'));
        if ($text === '') return;

        $size = 14;
        $width = $this->trackedWidth($text, $size, 3);
        $startX = $align === 'right' ? $x - $width : $x;

        $this->drawTracked($canvas, $text, $startX, $y, $size, 3, imagecolorallocatealpha($canvas, 255, 255, 255, 38));
    }

    /** Başlık satırı: hafif gölge + beyaz metin */
    private function text(\GdImage $canvas, string $line, int $x, int $y, int $size, bool $shadow): void
    {
        if ($shadow) {
            imagettftext($canvas, $size, 0, $x + 2, $y + 2, imagecolorallocatealpha($canvas, 0, 0, 0, 60), $this->font, $line);
        }
        imagettftext($canvas, $size, 0, $x, $y, imagecolorallocate($canvas, 255, 255, 255), $this->font, $line);
    }

    /** Harf aralıklı metin çizer (GD'de letter-spacing yok, karakter karakter basılır) */
    private function drawTracked(\GdImage $canvas, string $text, int $x, int $y, int $size, int $tracking, int $color): void
    {
        $length = mb_strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            imagettftext($canvas, $size, 0, $x, $y, $color, $this->font, $char);
            $box = imagettfbbox($size, 0, $this->font, $char);
            $x += abs($box[2] - $box[0]) + $tracking + ($char === ' ' ? 4 : 0);
        }
    }

    /**
     * Satır aralığı. Punto ile sabit oran kullanmak yanlış: fontun gövde yüksekliği
     * puntodan farklıdır ve satırlar üst üste biniyordu (İ noktaları üst satıra giriyordu).
     */
    private function lineHeight(int $size, ?string $font = null): int
    {
        return (int) ($this->glyphHeight($size, $font ?? $this->font) * 1.2);
    }

    /** Verilen puntoda büyük harf + inişli glif yüksekliği (satır aralığı için) */
    /**
     * Harf kutusunun yüksekliği. $metin verilirse o metnin gerçek kutusu ölçülür;
     * verilmezse genel bir örnek kullanılır.
     */
    private function glyphHeight(int $size, string $font, string $metin = 'ÀGgÇy'): int
    {
        $kutu = imagettfbbox($size, 0, $font, $metin);

        return abs($kutu[7] - $kutu[1]);
    }

    private function trackedWidth(string $text, int $size, int $tracking): int
    {
        $width = 0;
        $length = mb_strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $box = imagettfbbox($size, 0, $this->font, $char);
            $width += abs($box[2] - $box[0]) + $tracking + ($char === ' ' ? 4 : 0);
        }

        return $width;
    }

    /**
     * Başlığı verilen genişliğe ve satır sınırına sığdırır.
     *
     * @return array{0:array<int,string>,1:int}  satırlar ve seçilen punto
     */
    private function fitLines(string $title, int $maxWidth, int $maxLines, int $maxSize, int $minSize, ?string $font = null): array
    {
        $font ??= $this->font;
        $text = $this->upper($title);
        $size = $maxSize;
        $lines = $this->wrap($text, $size, $maxWidth, $font);

        while ($size > $minSize && count($lines) > $maxLines) {
            $size -= 2;
            $lines = $this->wrap($text, $size, $maxWidth, $font);
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' .,') . '...';
        }

        return [$lines, $size];
    }

    /**
     * Görsellerin yazılacağı kök dizin.
     *
     * PathHelper web isteğinde DOCUMENT_ROOT'u kullanır ama cron/CLI'da bu değişken
     * yoktur ve uygulama içindeki public/ klasörüne düşer. Bu paylaşımlı hosting
     * kurulumunda web kökü uygulamanın DIŞINDA (/home/hesap/public_html) olduğu için
     * cron ile üretilen görseller siteden erişilemeyen bir yere yazılıyordu.
     * Sıra: DOCUMENT_ROOT → ayar → hesap kökündeki public_html → PathHelper.
     */
    private function imagesRoot(): string
    {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot !== '' && is_dir($docRoot . '/images')) {
            return rtrim($docRoot, '/') . '/images';
        }

        $configured = trim((string) BotSettings::get('public_path'));
        if ($configured !== '' && is_dir($configured)) {
            return rtrim($configured, '/') . '/images';
        }

        // cPanel yerleşimi: uygulama /home/hesap/Uygulama, web kökü /home/hesap/public_html
        $sibling = dirname(base_path()) . '/public_html';
        if (is_dir($sibling)) {
            return $sibling . '/images';
        }

        return PathHelper::getImagesPath();
    }

    private function filename(string $title): string
    {
        $slug = Str::slug($this->trToAscii($title));
        $slug = mb_substr($slug !== '' ? $slug : 'haber', 0, 70);

        return $slug . '-' . substr(md5($title . microtime()), 0, 6) . '.webp';
    }

    private function trToAscii(string $s): string
    {
        return strtr($s, [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'C', 'Ğ' => 'G', 'İ' => 'I', 'Ö' => 'O', 'Ş' => 'S', 'Ü' => 'U',
        ]);
    }

    /** Türkçe doğru büyük harf: i→İ, ı→I */
    private function upper(string $text): string
    {
        return mb_strtoupper(strtr($text, [
            'i' => 'İ', 'ı' => 'I', 'ş' => 'Ş', 'ğ' => 'Ğ', 'ü' => 'Ü', 'ö' => 'Ö', 'ç' => 'Ç',
        ]), 'UTF-8');
    }

    /** TTF ölçüsüne göre satır kırma */
    private function wrap(string $text, int $size, int $maxWidth, ?string $font = null): array
    {
        $font ??= $this->font;
        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $candidate);
            if (abs($box[2] - $box[0]) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') $lines[] = $current;

        return $lines;
    }
}
