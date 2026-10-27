<?php

namespace App\Console\Commands;

use App\Models\BotRun;
use App\Services\Bot\BotSettings;
use App\Services\Bot\CuratorService;
use Illuminate\Console\Command;

/**
 * Yapay zekâ editör turu: havuzdaki ham haberleri haber değerine göre puanlar.
 * Düşük puanlılar elenir, yüksek puanlılar işleme sırasında öne geçer.
 */
class BotCurate extends Command
{
    protected $signature = 'bot:curate {--limit= : Bu turda değerlendirilecek haber sayısı}';

    protected $description = 'Havuzdaki haberleri yapay zekâ ile puanlar, değersiz duyuruları eler';

    public function handle(): int
    {
        if (! BotSettings::bool('curate_enabled')) {
            $this->warn('Editör katmanı kapalı (Bot Ayarları > Editör).');

            return self::SUCCESS;
        }

        $run = BotRun::create(['command' => 'curate', 'status' => 'running', 'started_at' => now()]);
        $stats = (new CuratorService())->curate($this->option('limit') ? (int) $this->option('limit') : null);

        $run->update([
            'status' => $stats['hata'] ? 'failed' : 'success',
            'error' => $stats['hata'],
            'finished_at' => now(),
            'stats' => $stats,
        ]);

        if ($stats['hata']) {
            $this->error('Editör hatası: ' . $stats['hata']);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Editör bitti: %d haber değerlendirildi (%d çağrı), %d elendi, %d sıraya girdi, %d manşet, %d öne çıkan',
            $stats['degerlendirilen'], $stats['parti'] ?? 0, $stats['elenen'], $stats['onaya'],
            $stats['manset'], $stats['one_cikan'],
        ));

        return self::SUCCESS;
    }
}
