<?php

namespace App\Console\Commands;

use App\Services\PharmacyService;
use Illuminate\Console\Command;

class FetchPharmacies extends Command
{
    protected $signature = 'pharmacy:fetch';
    protected $description = 'CollectAPI üzerinden nöbetçi eczane verilerini çeker ve veritabanına kaydeder';

    public function handle(PharmacyService $service): int
    {
        $this->info('Nöbetçi eczane verileri çekiliyor...');

        $stats = $service->fetchAll();

        $this->info("Başarılı: {$stats['success']} il");
        $this->info("Başarısız: {$stats['failed']} il");
        $this->info("Toplam eczane: {$stats['total_pharmacies']}");

        if ($stats['failed'] > 0) {
            $this->warn('Bazı iller için veri çekilemedi. Detaylar için log dosyasını kontrol edin.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
