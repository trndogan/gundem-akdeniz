<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Tek cron girdisiyle tüm zinciri çalıştırır: tara → işle → yayınla.
 * Paylaşımlı hostingde tek cron satırı yeterli olsun diye vardır.
 */
class BotRunAll extends Command
{
    protected $signature = 'bot:run';

    protected $description = 'Tarama, işleme ve yayın adımlarını sırayla çalıştırır';

    public function handle(): int
    {
        $this->components->info('1/3 Kaynaklar taranıyor');
        $this->call('bot:crawl');

        $this->components->info('2/3 Haberler özgünleştiriliyor');
        $this->call('bot:process');

        $this->components->info('3/3 Onaylı taslaklar yayınlanıyor');
        $this->call('bot:publish');

        return self::SUCCESS;
    }
}
