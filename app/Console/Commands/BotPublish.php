<?php

namespace App\Console\Commands;

use App\Models\BotRun;
use App\Services\Bot\PublisherService;
use Illuminate\Console\Command;

/**
 * Onaylanmış taslakları yayın saatlerine göre kademeli yayınlar.
 */
class BotPublish extends Command
{
    protected $signature = 'bot:publish {--all : Kotayı yok say, onaylı tüm taslakları yayınla}';

    protected $description = 'Onaylanmış taslakları slot saatlerine göre yayınlar';

    public function handle(): int
    {
        $run = BotRun::create(['command' => 'publish', 'status' => 'running', 'started_at' => now()]);
        $publisher = new PublisherService();

        if ($this->option('all')) {
            $drafts = \App\Models\BotDraft::where('status', 'approved')->orderBy('created_at')->get();
            $stats = ['published' => 0, 'failed' => 0, 'deficit' => $drafts->count()];
            foreach ($drafts as $draft) {
                $publisher->publish($draft)['ok'] ? $stats['published']++ : $stats['failed']++;
            }
        } else {
            $quota = $publisher->quota();

            $this->line(($quota['mode'] ?? 'interval') === 'slots'
                ? sprintf('  Kota: %d/%d slot geçti → hedef %d, bugün yayınlanan %d, eksik %d',
                    $quota['slots_passed'], $quota['slots_total'], $quota['target'], $quota['published'], $quota['deficit'])
                : sprintf('  Akış kipi: günlük tavan %d, bugün yayınlanan %d, tur başına %d%s',
                    $quota['target'], $quota['published'], $quota['per_run'],
                    $quota['active'] ? '' : ' (yayın penceresi dışında)'));

            $stats = $publisher->publishDue();
        }

        $run->update(['status' => 'success', 'finished_at' => now(), 'stats' => $stats]);
        $this->info("Yayın bitti: {$stats['published']} haber yayınlandı, {$stats['failed']} hata");

        return self::SUCCESS;
    }
}
