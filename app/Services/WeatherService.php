<?php

namespace App\Services;

use App\Models\City;
use App\Models\WeatherForecast;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected array $apiKeys;

    protected array $cityNameMap = [
        'antalya' => 'antalya',
        'mersin' => 'mersin',
        'adana' => 'adana',
        'hatay' => 'hatay',
        'isparta' => 'isparta',
        'burdur' => 'burdur',
        'osmaniye' => 'osmaniye',
        'kahramanmaras' => 'kahramanmaraş',
    ];

    public function __construct()
    {
        $this->apiKeys = config('services.collectapi.keys', []);
    }

    public function fetchAll(): array
    {
        $cities = City::all();
        $stats = ['success' => 0, 'failed' => 0, 'total_forecasts' => 0];
        $keyIndex = 0;

        foreach ($cities as $city) {
            $apiCityName = $this->cityNameMap[$city->slug] ?? strtolower($city->name);
            $apiKey = $this->apiKeys[$keyIndex % count($this->apiKeys)] ?? null;

            if (!$apiKey) {
                Log::error("WeatherService: API key bulunamadı (index: {$keyIndex})");
                $stats['failed']++;
                continue;
            }

            try {
                $forecasts = $this->fetchCity($apiCityName, $apiKey);

                if ($forecasts !== null) {
                    $this->saveForecasts($city, $forecasts);
                    $stats['success']++;
                    $stats['total_forecasts'] += count($forecasts);
                    Log::info("WeatherService: {$city->name} — " . count($forecasts) . " günlük tahmin kaydedildi");
                } else {
                    $stats['failed']++;
                }
            } catch (\Exception $e) {
                Log::error("WeatherService: {$city->name} hatası — " . $e->getMessage());
                $stats['failed']++;
            }

            $keyIndex++;
            sleep(1); // Rate limit koruması
        }

        return $stats;
    }

    protected function fetchCity(string $cityName, string $apiKey): ?array
    {
        $response = Http::withHeaders([
            'content-type' => 'application/json',
            'authorization' => 'apikey ' . $apiKey,
        ])->get('https://api.collectapi.com/weather/getWeather', [
            'city' => $cityName,
            'lang' => 'tr',
        ]);

        if ($response->failed()) {
            Log::error("WeatherService API hatası: HTTP {$response->status()} — {$cityName}");
            return null;
        }

        $data = $response->json();

        // Response doğrudan array olarak gelir
        if (isset($data['error'])) {
            Log::error("WeatherService API hatası: {$cityName} — " . ($data['error'] ?? 'bilinmeyen'));
            return null;
        }

        return $data;
    }

    protected function saveForecasts(City $city, array $forecasts): void
    {
        foreach ($forecasts as $index => $item) {
            $forecastDate = now()->addDays($index)->format('Y-m-d');

            $existing = WeatherForecast::where('city_id', $city->id)
                ->whereDate('forecast_date', $forecastDate)
                ->first();

            $data = [
                'description' => $item['description'] ?? null,
                'icon' => $item['icon'] ?? null,
                'temp_min' => $this->parseTemp($item['min'] ?? null),
                'temp_max' => $this->parseTemp($item['max'] ?? null),
                'temp_current' => $this->parseTemp($item['degree'] ?? null),
                'humidity' => $this->parseFloat($item['humidity'] ?? null),
                'wind_speed' => null,
                'wind_direction' => null,
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                WeatherForecast::create(array_merge($data, [
                    'city_id' => $city->id,
                    'forecast_date' => $forecastDate,
                ]));
            }
        }
    }

    protected function parseTemp(?string $value): ?float
    {
        if ($value === null) return null;
        return (float) preg_replace('/[^0-9.\-]/', '', $value);
    }

    protected function parseFloat(?string $value): ?float
    {
        if ($value === null) return null;
        $num = preg_replace('/[^0-9.\-]/', '', $value);
        return $num !== '' ? (float) $num : null;
    }
}
