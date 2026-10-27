<?php

namespace App\Services\Bot;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * IndexNow: yeni yayınlanan haberi arama motorlarına anında bildirir.
 *
 * Bing ve Yandex bu protokolü resmî olarak destekler, ücretsizdir ve günlük
 * kota istemez. Google IndexNow'ı kullanmaz; Google tarafı sitemap ve news
 * sitemap üzerinden yürür (Google'ın Indexing API'si yalnızca iş ilanı ve
 * canlı yayın içerikleri içindir, haber göndermek desteklenmez).
 *
 * Doğrulama: anahtar, sitenin kökünde <anahtar>.txt olarak yayımlanır ve
 * içeriği anahtarın kendisidir. Arama motoru bu dosyayı okuyup sahipliği teyit eder.
 */
class IndexNowService
{
    private const UC_NOKTA = 'https://api.indexnow.org/indexnow';

    /** Tek istekte bildirilebilecek üst sınır (protokol sınırı 10.000) */
    private const PARTI = 100;

    /**
     * Anahtarı döndürür, yoksa üretir ve kök dizine yazar.
     *
     * Anahtar dosyası sunucuda bulunmazsa arama motoru bildirimleri reddeder;
     * bu yüzden her çağrıda dosyanın varlığı da doğrulanır.
     */
    public function anahtar(): ?string
    {
        $anahtar = trim((string) BotSettings::get('indexnow_key'));
        if ($anahtar === '') {
            $anahtar = bin2hex(random_bytes(16));
            BotSettings::setMany(['indexnow_key' => $anahtar]);
            BotSettings::flush();
        }

        $dosya = $this->kokDizin() . '/' . $anahtar . '.txt';
        if (! is_file($dosya)) {
            if (! is_dir(dirname($dosya))) return null;
            @file_put_contents($dosya, $anahtar);
            @chmod($dosya, 0644);
        }

        return is_file($dosya) ? $anahtar : null;
    }

    /**
     * URL'leri bildirir.
     *
     * @param  array<int,string>  $urls
     * @return array{ok:bool,gonderilen:int,durum:?int,hata:?string}
     */
    public function submit(array $urls): array
    {
        $urls = array_values(array_unique(array_filter($urls)));
        if ($urls === []) {
            return ['ok' => true, 'gonderilen' => 0, 'durum' => null, 'hata' => null];
        }

        $anahtar = $this->anahtar();
        if ($anahtar === null) {
            return ['ok' => false, 'gonderilen' => 0, 'durum' => null,
                'hata' => 'Anahtar dosyası site köküne yazılamadı'];
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'gundemakdeniz.com';
        $gonderilen = 0;
        $sonDurum = null;

        foreach (array_chunk($urls, self::PARTI) as $parti) {
            try {
                $yanit = Http::timeout(30)->asJson()->post(self::UC_NOKTA, [
                    'host' => $host,
                    'key' => $anahtar,
                    'keyLocation' => "https://{$host}/{$anahtar}.txt",
                    'urlList' => $parti,
                ]);
                $sonDurum = $yanit->status();

                // 200 kabul, 202 kuyruğa alındı; ikisi de başarıdır
                if (! in_array($sonDurum, [200, 202], true)) {
                    BotLogger::warning("IndexNow reddetti (HTTP {$sonDurum})",
                        ['adet' => count($parti), 'yanit' => mb_substr($yanit->body(), 0, 200)], 'publish');

                    return ['ok' => false, 'gonderilen' => $gonderilen, 'durum' => $sonDurum,
                        'hata' => mb_substr($yanit->body(), 0, 200)];
                }

                $gonderilen += count($parti);
            } catch (Throwable $e) {
                BotLogger::warning('IndexNow çağrılamadı', ['hata' => $e->getMessage()], 'publish');

                return ['ok' => false, 'gonderilen' => $gonderilen, 'durum' => null, 'hata' => $e->getMessage()];
            }
        }

        return ['ok' => true, 'gonderilen' => $gonderilen, 'durum' => $sonDurum, 'hata' => null];
    }

    /**
     * Sitenin kök dizini.
     *
     * ImageComposer ile aynı sorun burada da geçerli: cron'da DOCUMENT_ROOT yoktur,
     * bu yüzden sırayla web kökü, ayardaki yol ve kardeş public_html denenir.
     */
    private function kokDizin(): string
    {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot !== '' && is_dir($docRoot)) return rtrim($docRoot, '/');

        $ayar = trim((string) BotSettings::get('public_path'));
        if ($ayar !== '' && is_dir($ayar)) return rtrim($ayar, '/');

        $kardes = dirname(base_path()) . '/public_html';
        if (is_dir($kardes)) return $kardes;

        return public_path();
    }
}
