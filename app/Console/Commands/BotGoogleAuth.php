<?php

namespace App\Console\Commands;

use App\Services\Bot\BotSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Google Indexing API için tek seferlik OAuth yetkilendirmesi.
 *
 * Servis hesabı anahtarı üretmeyi engelleyen kuruluş politikası
 * (iam.disableServiceAccountKeyCreation) OAuth istemcilerini kapsamaz, bu yüzden
 * yetki kullanıcı hesabı üzerinden alınır. Yetkiyi veren hesap Search Console'da
 * mülkün sahibi olmalıdır.
 *
 * Akış: komut izin bağlantısını yazar, kullanıcı tarayıcıda onaylar, adres
 * çubuğundaki code parametresi komuta geri verilir ve yenileme jetonu saklanır.
 */
class BotGoogleAuth extends Command
{
    protected $signature = 'bot:google-auth
        {--id= : OAuth istemci kimliği}
        {--secret= : OAuth istemci gizli anahtarı}
        {--kod= : Tarayıcıdan dönen code parametresi}';

    protected $description = 'Google Indexing API için OAuth yetkisi alır';

    private const KAPSAM = 'https://www.googleapis.com/auth/indexing';

    private const YONLENDIRME = 'http://localhost';

    public function handle(): int
    {
        if ($this->option('id')) {
            BotSettings::setMany([
                'google_oauth_client_id' => trim((string) $this->option('id')),
                'google_oauth_client_secret' => trim((string) $this->option('secret')),
            ]);
            BotSettings::flush();
            $this->info('İstemci bilgileri kaydedildi.');
        }

        $id = trim((string) BotSettings::get('google_oauth_client_id'));
        $secret = trim((string) BotSettings::get('google_oauth_client_secret'));

        if ($id === '' || $secret === '') {
            $this->error('Önce istemci bilgilerini ver: --id=... --secret=...');

            return self::FAILURE;
        }

        $kod = trim((string) $this->option('kod'));

        if ($kod === '') {
            $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $id,
                'redirect_uri' => self::YONLENDIRME,
                'response_type' => 'code',
                'scope' => self::KAPSAM,
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);

            $this->line('');
            $this->line('1) Bu adresi tarayıcıda aç ve izin ver:');
            $this->line('');
            $this->line($url);
            $this->line('');
            $this->line('2) Tarayıcı "site erişilemiyor" diyecek, bu normal.');
            $this->line('   Adres çubuğundaki  code=  değerini kopyala.');
            $this->line('');
            $this->line('3) Sonra çalıştır:  php artisan bot:google-auth --kod=KOPYALADIGIN_DEGER');
            $this->line('');

            return self::SUCCESS;
        }

        $yanit = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => urldecode($kod),
            'client_id' => $id,
            'client_secret' => $secret,
            'redirect_uri' => self::YONLENDIRME,
            'grant_type' => 'authorization_code',
        ]);

        if ($yanit->failed()) {
            $this->error("Jeton alınamadı (HTTP {$yanit->status()}): " . mb_substr($yanit->body(), 0, 200));

            return self::FAILURE;
        }

        $refresh = (string) data_get($yanit->json(), 'refresh_token');
        if ($refresh === '') {
            $this->error('Yanıtta refresh_token yok. İzin ekranını "prompt=consent" ile yeniden aç.');

            return self::FAILURE;
        }

        BotSettings::setMany(['google_oauth_refresh_token' => $refresh]);
        BotSettings::flush();

        $this->info('Yetki alındı, yenileme jetonu kaydedildi.');
        $this->line('Sınamak için: php artisan bot:google-index --gun=1 --limit=1');

        return self::SUCCESS;
    }
}
