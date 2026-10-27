<?php

namespace App\Services;

use App\Models\City;
use App\Models\Pharmacy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PharmacyService
{
    protected array $apiKeys;

    // CollectAPI şehir adı eşleştirmesi
    protected array $cityNameMap = [
        'antalya' => 'Antalya',
        'mersin' => 'Mersin',
        'adana' => 'Adana',
        'hatay' => 'Hatay',
        'isparta' => 'Isparta',
        'burdur' => 'Burdur',
        'osmaniye' => 'Osmaniye',
        'kahramanmaras' => 'Kahramanmaraş',
    ];

    public function __construct()
    {
        $this->apiKeys = config('services.collectapi.keys', []);
    }

    /**
     * Tüm şehirler için nöbetçi eczane verilerini çek ve kaydet
     */
    public function fetchAll(): array
    {
        $cities = City::all();
        $stats = ['success' => 0, 'failed' => 0, 'total_pharmacies' => 0];
        $keyIndex = 0;

        foreach ($cities as $city) {
            $apiCityName = $this->cityNameMap[$city->slug] ?? $city->name;
            $fetched = false;

            // Tüm API key'leri dene, biri başarılı olana kadar
            for ($i = 0; $i < count($this->apiKeys); $i++) {
                $apiKey = $this->apiKeys[($keyIndex + $i) % count($this->apiKeys)] ?? null;
                if (!$apiKey) continue;

                try {
                    $pharmacies = $this->fetchCity($apiCityName, $apiKey);

                    if ($pharmacies !== null) {
                        $this->savePharmacies($city, $pharmacies);
                        $stats['success']++;
                        $stats['total_pharmacies'] += count($pharmacies);
                        Log::info("PharmacyService: {$city->name} — " . count($pharmacies) . " eczane kaydedildi");
                        $fetched = true;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::warning("PharmacyService: {$city->name} key {$i} hatası — " . $e->getMessage());
                }

                usleep(300000); // 300ms bekle, rate limit'e takılmamak için
            }

            if (!$fetched) {
                Log::error("PharmacyService: {$city->name} — tüm key'ler denendi, başarısız");
                $stats['failed']++;
            }

            $keyIndex++;
        }

        return $stats;
    }

    /**
     * Tek bir şehir için CollectAPI'den veri çek
     */
    protected function fetchCity(string $cityName, string $apiKey): ?array
    {
        $response = Http::withHeaders([
            'content-type' => 'application/json',
            'authorization' => 'apikey ' . $apiKey,
        ])->get('https://api.collectapi.com/health/dutyPharmacy', [
            'il' => $cityName,
        ]);

        if ($response->failed()) {
            Log::error("PharmacyService API hatası: HTTP {$response->status()} — {$cityName}");
            return null;
        }

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            Log::error("PharmacyService API başarısız: {$cityName} — " . json_encode($data));
            return null;
        }

        return $data['result'] ?? [];
    }

    /**
     * Çekilen verileri veritabanına kaydet (bugün + yarın için)
     */
    protected function savePharmacies(City $city, array $pharmacies): void
    {
        // API bugünün nöbetçi eczanelerini döner
        $dutyDate = now()->toDateString();

        // Bu şehir + tarih için eski kayıtları temizle
        Pharmacy::where('city_id', $city->id)
            ->where('duty_date', $dutyDate)
            ->delete();

        foreach ($pharmacies as $item) {
            Pharmacy::create([
                'city_id' => $city->id,
                'name' => $item['name'] ?? 'Bilinmiyor',
                'address' => $item['address'] ?? null,
                'phone' => $this->cleanPhone($item['phone'] ?? null),
                'district' => $item['dist'] ?? null,
                'latitude' => $this->parseCoord($item['loc'] ?? null, 'lat'),
                'longitude' => $this->parseCoord($item['loc'] ?? null, 'lng'),
                'duty_date' => $dutyDate,
            ]);
        }
    }

    /**
     * Telefon numarasını temizle
     */
    protected function cleanPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        return preg_replace('/[^\d+]/', '', $phone);
    }

    /**
     * Konum bilgisini parse et (CollectAPI "lat,lng" formatında döner)
     */
    protected function parseCoord(?string $loc, string $type): ?float
    {
        if (!$loc) {
            return null;
        }
        $parts = explode(',', $loc);
        if (count($parts) !== 2) {
            return null;
        }
        return $type === 'lat' ? (float) trim($parts[0]) : (float) trim($parts[1]);
    }
}
