<?php

namespace App\Console\Commands;

use App\Models\BotDraft;
use App\Models\BotPoolItem;
use App\Models\BotRun;
use App\Services\Bot\BotLogger;
use Illuminate\Console\Command;

/**
 * Havuz, günlük ve çalışma kayıtlarını budar; veritabanı zamanla şişmesin.
 */
class BotCleanup extends Command
{
    protected $signature = 'bot:cleanup {--days=30 : Bu günden eski kayıtlar silinir}';

    protected $description = 'Eski havuz kayıtlarını, günlükleri ve çalışma özetlerini temizler';

    public function handle(): int
    {
        $days = max(3, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Kullanılmış veya reddedilmiş havuz kayıtları: içerik gövdesi büyük, silinmeli
        $pool = BotPoolItem::whereIn('status', ['used', 'rejected', 'failed'])
            ->where('created_at', '<', $cutoff)->delete();

        // Hiç işlenmemiş ama bayatlamış kayıtlar
        $stale = BotPoolItem::where('status', 'new')
            ->where('created_at', '<', now()->subDays(max(3, (int) ($days / 3))))->delete();

        $logs = BotLogger::prune($days);
        $runs = BotRun::where('started_at', '<', $cutoff)->delete();

        // Yayınlanmış taslakların gövdesi articles tablosunda zaten var
        $drafts = BotDraft::where('status', 'published')
            ->where('published_at', '<', $cutoff)
            ->update(['body' => '', 'excerpt' => null]);

        $this->info("Temizlik: havuz {$pool}+{$stale}, günlük {$logs}, çalışma {$runs}, taslak gövdesi {$drafts}");

        return self::SUCCESS;
    }
}
