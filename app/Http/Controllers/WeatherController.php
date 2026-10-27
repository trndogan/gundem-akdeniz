<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Setting;
use App\Models\WeatherForecast;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    public function show(string $citySlug)
    {
        $city = City::where('slug', $citySlug)->firstOrFail();

        $forecasts = Cache::remember("weather.{$city->id}", 3600, function () use ($city) {
            return WeatherForecast::byCity($city->id)
                ->week()
                ->orderBy('forecast_date')
                ->get();
        });

        $today = $forecasts->first();

        $allCities = Cache::remember('weather.cities', 86400, function () {
            return City::active()->orderBy('sort_order')->get();
        });

        return view('weather.show', compact('city', 'forecasts', 'today', 'allCities'));
    }

    public function index()
    {
        $cities = City::active()->orderBy('sort_order')->get();

        $todayWeather = Cache::remember('weather.index.today', 3600, function () use ($cities) {
            $data = [];
            foreach ($cities as $city) {
                $data[$city->id] = WeatherForecast::byCity($city->id)->today()->first();
            }
            return $data;
        });

        $seoTitle = Setting::get('weather_seo_title');
        $seoDescription = Setting::get('weather_seo_description');
        $weatherContent = Setting::get('weather_content');

        return view('weather.index', compact('cities', 'todayWeather', 'seoTitle', 'seoDescription', 'weatherContent'));
    }
}
