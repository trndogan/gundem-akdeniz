<?php

namespace App\Console\Commands;

use App\Models\BotRun;
use App\Models\BotSource;
use App\Services\Bot\BotLogger;
use App\Services\Bot\BotSettings;
use App\Services\Bot\CrawlerService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Resmî kaynakları kademeli tarar.
 *
 * Her turda yalnızca en uzun süredir taranmayan N kaynak işlenir. Tek turda
 * yüzlerce gov.tr isteği atmak hem süreyi uzatıyor hem sunucunun DNS'ini yoruyor;
 * havuz zaten dolu olduğu için tarama sıklığı kritik değil.
 */
class BotCrawl extends Command
{
    protected $signature = 'bot:crawl
        {--limit= : Bu turda taranacak azami kaynak sayısı (0 = sınırsız)}
        {--minutes= : Tur için zaman bütçesi, dakika}
        {--source= : Yalnızca bu kaynak id}';

    protected $description = 'Resmî kaynakları tarar ve haber havuzunu doldurur';

    public function handle(): int
    {
        $run = BotRun::create(['command' => 'crawl', 'status' => 'running', 'started_at' => now()]);

        $limit = $this->option('limit') !== null
            ? (int) $this->option('limit')
            : BotSettings::int('crawl_batch');

        // Tur, kaynak sayısıyla değil süreyle sınırlanır: bir sonraki tur başlamadan biter,
        // yetişemediği kaynaklar sıranın başında kaldığı için bir sonraki turda alınır.
        $budget = $this->option('minutes') !== null
            ? (int) $this->option('minutes')
            : BotSettings::int('crawl_time_budget');
        $deadline = $budget > 0 ? microtime(true) + $budget * 60 : null;

        $query = BotSource::where('is_active', true)->where('consecutive_errors', '<', 5);

        if ($sourceId = $this->option('source')) {
            $query->whereKey($sourceId);
        } else {
            // Yeni eklenen kaynaklar önce, sonra en eski taranan; öncelik yüksek olan öne geçer
            $query->orderByRaw('last_crawled_at IS NULL DESC')
                ->orderByDesc('priority')
                ->orderBy('last_crawled_at');

            if ($limit > 0) {
                $query->limit($limit);
            }
        }

        $sources = $query->get();
        if ($sources->isEmpty()) {
            $this->warn('Taranacak aktif kaynak yok.');
            $run->update(['status' => 'success', 'finished_at' => now(), 'stats' => ['sources' => 0]]);

            return self::SUCCESS;
        }

        $crawler = new CrawlerService();
        $totals = ['sources' => 0, 'saved' => 0, 'seen' => 0, 'errors' => 0, 'skipped' => []];
        $kalan = 0;

        // Liste sayfaları önce toplu ve eşzamanlı çekilir; kaynak başına tek tek
        // beklemek tarama süresinin büyük kısmını harcıyordu.
        $listeler = [];
        foreach ($sources->chunk(60) as $grup) {
            $listeler += $crawler->prefetchLists($grup);
        }

        foreach ($sources as $source) {
            if ($deadline !== null && microtime(true) >= $deadline) {
                $kalan = $sources->count() - $totals['sources'];
                break;
            }

            try {
                $stats = $crawler->crawl($source, $listeler[$source->id] ?? null);
                $totals['sources']++;
                $totals['saved'] += $stats['saved'];
                $totals['seen'] += $stats['seen'];
                if ($stats['error']) $totals['errors']++;

                foreach ($stats['skipped'] as $reason => $count) {
                    $totals['skipped'][$reason] = ($totals['skipped'][$reason] ?? 0) + $count;
                }

                $this->line(sprintf(
                    '  %-45s %2d yeni / %2d görülen%s',
                    mb_substr($source->name, 0, 45),
                    $stats['saved'],
                    $stats['seen'],
                    $stats['error'] ? '  HATA: ' . $stats['error'] : '',
                ));
            } catch (Throwable $e) {
                $totals['errors']++;
                BotLogger::exception($e, 'crawl', $source->id);
                $this->error("  {$source->name}: {$e->getMessage()}");
            }
        }

        if ($kalan > 0) {
            $totals['sure_doldu'] = $kalan;
        }

        $run->update(['status' => 'success', 'finished_at' => now(), 'stats' => $totals]);

        $this->info(sprintf(
            'Tarama bitti: %d kaynak, %d yeni haber, %d hata%s',
            $totals['sources'], $totals['saved'], $totals['errors'],
            $kalan > 0 ? " (süre doldu, {$kalan} kaynak sonraki tura kaldı)" : '',
        ));

        return self::SUCCESS;
    }
}
