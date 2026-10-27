<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Bot\BotSettings;
use App\Services\Bot\GoogleIndexingService;
use Illuminate\Console\Command;

/**
 * Yayındaki haberleri Google Indexing API ile bildirir.
 *
 * Yeni haberler yayın anında zaten bildiriliyor; bu komut geriye dönük gönderim
 * içindir. Günlük kota 200'dür, komut kotayı aşmaz.
 */
class BotGoogleIndex extends Command
{
    protected $signature = 'bot:google-index
        {--gun=1 : Son N günde yayınlananlar}
        {--limit=0 : En fazla kaç URL (0 = kalan kota kadar)}
        {--baskanlar : Haber yerine belediye başkanı sayfalarını bildir}';

    protected $description = 'Haberleri Google Indexing API ile bildirir';

    public function handle(GoogleIndexingService $google): int
    {
        if (! $google->hazir()) {
            $this->error('Servis hesabı anahtarı yok: storage/app/bot/google-indexing.json');

            return self::FAILURE;
        }

        $kalan = $google->kalanKota();
        $this->line("bugün kullanılan: {$google->bugunKullanilan()} | kalan kota: {$kalan}");
        if ($kalan === 0) {
            $this->warn('Günlük kota dolu, yarın devam.');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit') ?: $kalan;

        // Başkan sayfaları bir kez bildirilir. Kotanın dolduğu gün atlanır,
        // ertesi gün kaldığı yerden devam eder; hepsi bitince ayar işaretlenir.
        if ($this->option('baskanlar')) {
            if (BotSettings::get('mayors_indexed_at')) {
                $this->info('Başkan sayfaları zaten bildirildi.');

                return self::SUCCESS;
            }

            $urls = \Illuminate\Support\Facades\DB::table('mayors')
                ->where('is_active', 1)->pluck('slug')
                ->map(fn ($s) => url('/belediye-baskanlari/' . $s))
                ->take(min($limit, $kalan))->all();

            $this->line(count($urls) . ' başkan sayfası bildiriliyor...');
            $s = $google->submit($urls);
            $this->info("gönderilen: {$s['gonderilen']} | atlanan: {$s['atlanan']}");

            if ($s['gonderilen'] >= \Illuminate\Support\Facades\DB::table('mayors')->where('is_active', 1)->count()) {
                BotSettings::setMany(['mayors_indexed_at' => now()->toDateTimeString()]);
                BotSettings::flush();
                $this->info('Tümü bildirildi, işaretlendi.');
            }

            return self::SUCCESS;
        }

        $urls = Article::published()
            ->where('published_at', '>=', now()->subDays((int) $this->option('gun')))
            ->latest('published_at')
            ->limit(min($limit, $kalan))
            ->get()
            ->map(fn (Article $a) => $a->url)
            ->all();

        if ($urls === []) {
            $this->info('Bildirilecek haber yok.');

            return self::SUCCESS;
        }

        $this->line(count($urls) . ' haber bildiriliyor...');
        $s = $google->submit($urls);

        $this->info("gönderilen: {$s['gonderilen']} | atlanan: {$s['atlanan']}");
        foreach (array_slice($s['hatalar'], 0, 3) as $h) {
            $this->error('  ' . $h);
        }

        return $s['ok'] || $s['gonderilen'] > 0 ? self::SUCCESS : self::FAILURE;
    }
}
