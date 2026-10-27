<?php

namespace App\Console\Commands;

use App\Models\BotDraft;
use App\Models\BotPoolItem;
use App\Models\BotRun;
use App\Services\Bot\BotSettings;
use App\Services\Bot\ProcessorService;
use Illuminate\Console\Command;

/**
 * Havuzdaki ham haberleri Gemini ile özgünleştirip görselleriyle onay kuyruğuna taşır.
 *
 * Günlük taslak limiti aşılmışsa hiç işlem yapılmaz; böylece API kotası
 * yayınlanmayacak taslaklara harcanmaz.
 */
class BotProcess extends Command
{
    protected $signature = 'bot:process {--limit= : Bu turda işlenecek haber sayısı} {--pool= : Yalnızca bu havuz kaydı}';

    protected $description = 'Havuzdaki haberleri yapay zekâ ile özgünleştirir ve taslak üretir';

    public function handle(): int
    {
        $run = BotRun::create(['command' => 'process', 'status' => 'running', 'started_at' => now()]);
        $limit = (int) ($this->option('limit') ?: BotSettings::int('process_batch'));

        $todayDrafts = BotDraft::whereDate('created_at', today())->count();
        $dailyLimit = BotSettings::int('daily_limit');

        if ($this->option('pool') === null && $dailyLimit > 0 && $todayDrafts >= $dailyLimit) {
            $this->warn("Günlük taslak limiti dolu ({$todayDrafts}/{$dailyLimit}), işleme atlandı.");
            $run->update(['status' => 'success', 'finished_at' => now(), 'stats' => ['limit_reached' => true]]);

            return self::SUCCESS;
        }

        $remaining = $dailyLimit > 0 ? max(0, $dailyLimit - $todayDrafts) : $limit;
        $limit = max(1, min($limit, $remaining));

        $query = BotPoolItem::where('status', 'new');
        if ($poolId = $this->option('pool')) {
            $query->whereKey($poolId);
        } else {
            // Önce editör puanı, sonra kaynak önceliği: yüksek haber değerli
            // içerik günlük limit dolmadan işlensin
            $query->leftJoin('bot_sources', 'bot_pool.source_id', '=', 'bot_sources.id')
                ->orderByRaw('bot_pool.score IS NULL')
                ->orderByDesc('bot_pool.score')
                ->orderByDesc('bot_sources.priority')
                ->orderBy('bot_pool.created_at')
                ->select('bot_pool.*')
                ->limit($limit);
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->warn('İşlenecek haber yok.');
            $run->update(['status' => 'success', 'finished_at' => now(), 'stats' => ['items' => 0]]);

            return self::SUCCESS;
        }

        $processor = new ProcessorService();
        $stats = ['processed' => 0, 'failed' => 0, 'images' => []];

        foreach ($items as $item) {
            $result = $processor->process($item);

            if ($result['ok']) {
                $stats['processed']++;
                $draft = $result['draft'];
                $mode = $draft->image_mode ?? 'none';
                $stats['images'][$mode] = ($stats['images'][$mode] ?? 0) + 1;
                $rozet = $draft->is_headline ? ' MANŞET' : ($draft->is_featured ? ' öne çıkan' : '');
                $this->line(sprintf('  ✓ %-52s [%s puan%s | görsel: %s]', mb_substr($draft->title, 0, 52), $draft->score ?? '-', $rozet, $mode));
            } else {
                $stats['failed']++;
                $this->error('  ✗ ' . mb_substr($item->title, 0, 55) . ' — ' . $result['error']);
            }
        }

        $run->update(['status' => 'success', 'finished_at' => now(), 'stats' => $stats]);
        $this->info("İşleme bitti: {$stats['processed']} taslak, {$stats['failed']} hata");

        return self::SUCCESS;
    }
}
