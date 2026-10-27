<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Bot\IndexNowService;
use Illuminate\Console\Command;

/**
 * Yayındaki haberleri IndexNow ile arama motorlarına bildirir.
 *
 * Yeni haberler yayın anında zaten bildiriliyor; bu komut geriye dönük gönderim
 * ve protokol arızası sonrası yeniden deneme içindir.
 */
class BotIndexNow extends Command
{
    protected $signature = 'bot:indexnow
        {--gun=0 : Yalnızca son N günde yayınlananlar (0 = tümü)}
        {--limit=0 : En fazla kaç URL gönderilsin (0 = sınırsız)}';

    protected $description = 'Yayındaki haberleri IndexNow ile bildirir';

    public function handle(IndexNowService $indexNow): int
    {
        $anahtar = $indexNow->anahtar();
        if ($anahtar === null) {
            $this->error('IndexNow anahtar dosyası site köküne yazılamadı.');

            return self::FAILURE;
        }
        $this->line("anahtar dosyası: /{$anahtar}.txt");

        $sorgu = Article::published()->latest('published_at');
        if ((int) $this->option('gun') > 0) {
            $sorgu->where('published_at', '>=', now()->subDays((int) $this->option('gun')));
        }
        if ((int) $this->option('limit') > 0) {
            $sorgu->limit((int) $this->option('limit'));
        }

        $urls = $sorgu->get()->map(fn (Article $a) => $a->url)->all();
        if ($urls === []) {
            $this->info('Bildirilecek haber yok.');

            return self::SUCCESS;
        }

        $this->line(count($urls) . ' haber bildiriliyor...');
        $sonuc = $indexNow->submit($urls);

        if (! $sonuc['ok']) {
            $this->error("Başarısız (HTTP {$sonuc['durum']}): {$sonuc['hata']}");

            return self::FAILURE;
        }

        $this->info("{$sonuc['gonderilen']} URL bildirildi (HTTP {$sonuc['durum']}).");

        return self::SUCCESS;
    }
}
