<?php

namespace App\Services\Bot;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Indexing API ile yayınlanan haberleri Google'a bildirir.
 *
 * Kapsam uyarısı: Google bu API'yi resmî olarak yalnızca JobPosting ve
 * BroadcastEvent içeren sayfalar için destekler ve kötüye kullanımın erişim
 * iptaliyle sonuçlanabileceğini yazar. Haber gönderimi bu kapsamın dışındadır;
 * kullanım kararı site sahibine aittir (30.08.2026'da bilerek verildi).
 *
 * İki kimlik yolu desteklenir:
 *
 * 1. OAuth kullanıcı yetkisi (varsayılan). Bir kez izin verilir, alınan yenileme
 *    jetonu saklanır. Yetkiyi veren Google hesabı Search Console'da mülkün sahibi
 *    olmalıdır. Servis hesabı anahtarı oluşturmayı engelleyen kuruluş politikası
 *    (iam.disableServiceAccountKeyCreation) bu yolu etkilemez.
 *
 * 2. Servis hesabı JSON anahtarı. Varsa kullanılır; hesabın e-postası Search
 *    Console'da mülke "sahip" olarak eklenmiş olmalıdır.
 *
 * Her iki yolda da yetki eksikse Google isteklere 403 döner.
 */
class GoogleIndexingService
{
    private const KAPSAM = 'https://www.googleapis.com/auth/indexing';

    private const JETON_UC = 'https://oauth2.googleapis.com/token';

    private const YAYIN_UC = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    /** Google'ın varsayılan günlük kotası. Aşan istekler 429 döner. */
    private const GUNLUK_KOTA = 200;

    /**
     * URL'leri bildirir.
     *
     * @param  array<int,string>  $urls
     * @param  string  $tip  URL_UPDATED veya URL_DELETED
     * @return array{ok:bool,gonderilen:int,atlanan:int,hatalar:array<int,string>}
     */
    public function submit(array $urls, string $tip = 'URL_UPDATED'): array
    {
        $urls = array_values(array_unique(array_filter($urls)));
        $sonuc = ['ok' => true, 'gonderilen' => 0, 'atlanan' => 0, 'hatalar' => []];
        if ($urls === []) return $sonuc;

        $jeton = $this->erisimJetonu();
        if ($jeton === null) {
            $sonuc['ok'] = false;
            $sonuc['hatalar'][] = 'Servis hesabı anahtarı okunamadı veya jeton alınamadı';

            return $sonuc;
        }

        foreach ($urls as $url) {
            if ($this->bugunKullanilan() >= self::GUNLUK_KOTA) {
                $sonuc['atlanan']++;
                continue;
            }

            try {
                $yanit = Http::timeout(30)->withToken($jeton)
                    ->post(self::YAYIN_UC, ['url' => $url, 'type' => $tip]);

                if ($yanit->successful()) {
                    $sonuc['gonderilen']++;
                    $this->sayacArtir();
                    continue;
                }

                $mesaj = (string) data_get($yanit->json(), 'error.message', $yanit->body());
                $sonuc['hatalar'][] = "HTTP {$yanit->status()}: " . mb_substr($mesaj, 0, 140);
                $sonuc['ok'] = false;

                // Kota bittiyse kalanları denemenin anlamı yok
                if ($yanit->status() === 429) {
                    $sonuc['atlanan'] += count($urls) - $sonuc['gonderilen'] - count($sonuc['hatalar']);
                    break;
                }
            } catch (Throwable $e) {
                $sonuc['ok'] = false;
                $sonuc['hatalar'][] = mb_substr($e->getMessage(), 0, 140);
            }
        }

        if ($sonuc['hatalar'] !== []) {
            BotLogger::warning('Google Indexing API hatası', [
                'ilk_hata' => $sonuc['hatalar'][0], 'gonderilen' => $sonuc['gonderilen'],
            ], 'publish');
        }

        return $sonuc;
    }

    /** Servis hesabı yapılandırılmış mı */
    public function hazir(): bool
    {
        if ($this->suresiDoldu()) return false;

        return $this->oauthKimlik() !== null || $this->kimlik() !== null;
    }

    /**
     * Deneme süresi doldu mu.
     *
     * Ölçüm sonucu: publish çağrıları HTTP 200 dönüyor ama Google'ın metadata
     * kaydında URL oluşmuyor (404). Yani istek kabul ediliyor, işlenmiyor; API
     * yalnızca JobPosting ve BroadcastEvent sayfalarını işliyor. Yine de bir süre
     * açık tutulup gözlemlenmesi istendi (31.08.2026). Tarih geçince kendiliğinden
     * susar, ayar dosyasına dönüp kapatmak gerekmez.
     */
    public function suresiDoldu(): bool
    {
        $bitis = trim((string) BotSettings::get('google_indexing_expires_at'));
        if ($bitis === '') return false;

        try {
            return now()->greaterThan(\Illuminate\Support\Carbon::parse($bitis)->endOfDay());
        } catch (Throwable) {
            return false;   // bozuk tarih yüzünden sessizce kapanmasın
        }
    }

    /**
     * OAuth kimlik bilgileri.
     *
     * @return array{client_id:string,client_secret:string,refresh_token:string}|null
     */
    private function oauthKimlik(): ?array
    {
        $id = trim((string) BotSettings::get('google_oauth_client_id'));
        $secret = trim((string) BotSettings::get('google_oauth_client_secret'));
        $refresh = trim((string) BotSettings::get('google_oauth_refresh_token'));

        if ($id === '' || $secret === '' || $refresh === '') return null;

        return ['client_id' => $id, 'client_secret' => $secret, 'refresh_token' => $refresh];
    }

    /** Bugün kaç bildirim gönderildi */
    public function bugunKullanilan(): int
    {
        return (int) Cache::get($this->sayacAnahtari(), 0);
    }

    public function kalanKota(): int
    {
        return max(0, self::GUNLUK_KOTA - $this->bugunKullanilan());
    }

    private function sayacArtir(): void
    {
        Cache::put($this->sayacAnahtari(), $this->bugunKullanilan() + 1, now()->endOfDay());
    }

    private function sayacAnahtari(): string
    {
        return 'google_indexing.' . now()->toDateString();
    }

    /**
     * Erişim jetonu. Google jetonu 1 saat geçerli verir, 55 dakika önbelleklenir.
     */
    private function erisimJetonu(): ?string
    {
        return Cache::remember('google_indexing.token', now()->addMinutes(55), function () {
            // OAuth yenileme jetonu varsa onu kullan
            $oauth = $this->oauthKimlik();
            if ($oauth !== null) {
                try {
                    $y = Http::timeout(30)->asForm()->post(self::JETON_UC, [
                        'grant_type' => 'refresh_token',
                        'client_id' => $oauth['client_id'],
                        'client_secret' => $oauth['client_secret'],
                        'refresh_token' => $oauth['refresh_token'],
                    ]);
                } catch (Throwable $e) {
                    BotLogger::warning('Google jetonu alınamadı', ['hata' => $e->getMessage()], 'publish');

                    return null;
                }

                if ($y->failed()) {
                    BotLogger::warning("Google OAuth jetonu reddedildi (HTTP {$y->status()})",
                        ['yanit' => mb_substr($y->body(), 0, 200)], 'publish');

                    return null;
                }

                return (string) data_get($y->json(), 'access_token') ?: null;
            }

            $kimlik = $this->kimlik();
            if ($kimlik === null) return null;

            $jwt = $this->imzaliJwt($kimlik);
            if ($jwt === null) return null;

            try {
                $yanit = Http::timeout(30)->asForm()->post(self::JETON_UC, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
            } catch (Throwable $e) {
                BotLogger::warning('Google jetonu alınamadı', ['hata' => $e->getMessage()], 'publish');

                return null;
            }

            if ($yanit->failed()) {
                BotLogger::warning("Google jetonu reddedildi (HTTP {$yanit->status()})",
                    ['yanit' => mb_substr($yanit->body(), 0, 200)], 'publish');

                return null;
            }

            return (string) data_get($yanit->json(), 'access_token') ?: null;
        });
    }

    /** RS256 imzalı JWT üretir */
    private function imzaliJwt(array $kimlik): ?string
    {
        $simdi = time();
        $bas = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $govde = $this->b64(json_encode([
            'iss' => $kimlik['client_email'],
            'scope' => self::KAPSAM,
            'aud' => self::JETON_UC,
            'iat' => $simdi,
            'exp' => $simdi + 3600,
        ]));

        $imza = '';
        if (! openssl_sign("{$bas}.{$govde}", $imza, $kimlik['private_key'], OPENSSL_ALGO_SHA256)) {
            BotLogger::warning('JWT imzalanamadı: özel anahtar geçersiz olabilir', [], 'publish');

            return null;
        }

        return "{$bas}.{$govde}." . $this->b64($imza);
    }

    private function b64(string $veri): string
    {
        return rtrim(strtr(base64_encode($veri), '+/', '-_'), '=');
    }

    /**
     * Servis hesabı JSON anahtarını okur.
     *
     * @return array{client_email:string,private_key:string}|null
     */
    private function kimlik(): ?array
    {
        $yol = storage_path('app/bot/google-indexing.json');
        if (! is_file($yol)) return null;

        $veri = json_decode((string) file_get_contents($yol), true);
        if (! is_array($veri) || empty($veri['client_email']) || empty($veri['private_key'])) {
            return null;
        }

        return ['client_email' => $veri['client_email'], 'private_key' => $veri['private_key']];
    }
}
