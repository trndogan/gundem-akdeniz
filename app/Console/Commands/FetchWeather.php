<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Illuminate\Console\Command;

class FetchWeather extends Command
{
    protected $signature = 'weather:fetch';
    protected $description = 'CollectAPI üzerinden hava durumu verilerini çeker ve veritabanına kaydeder';

    public function handle(WeatherService $service): int
    {
        $this->info('Hava durumu verileri çekiliyor...');

        $stats = $service->fetchAll();

        $this->info("Başarılı: {$stats['success']} il");
        $this->info("Başarısız: {$stats['failed']} il");
        $this->info("Toplam tahmin: {$stats['total_forecasts']}");

        if ($stats['failed'] > 0) {
            $this->warn('Bazı iller için veri çekilemedi. Detaylar için log dosyasını kontrol edin.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
