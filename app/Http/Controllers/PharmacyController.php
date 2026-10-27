<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Pharmacy;
use Illuminate\Support\Facades\Cache;

class PharmacyController extends Controller
{
    public function show(string $citySlug)
    {
        $city = City::where('slug', $citySlug)->firstOrFail();

        $pharmacies = Cache::remember("pharmacy.{$city->id}." . today()->toDateString(), 3600, function () use ($city) {
            // Önce bugünün verisini ara, yoksa en son çekilen veriyi göster
            $data = Pharmacy::byCity($city->id)
                ->today()
                ->orderBy('district')
                ->orderBy('name')
                ->get();

            if ($data->isEmpty()) {
                $data = Pharmacy::byCity($city->id)
                    ->where('duty_date', Pharmacy::byCity($city->id)->max('duty_date'))
                    ->orderBy('district')
                    ->orderBy('name')
                    ->get();
            }

            return $data;
        });

        $allCities = Cache::remember('pharmacy.cities', 86400, function () {
            return City::active()->orderBy('sort_order')->get();
        });

        return view('pharmacy.show', compact('city', 'pharmacies', 'allCities'));
    }

    public function index()
    {
        $cities = City::active()->orderBy('sort_order')->get();
        return view('pharmacy.index', compact('cities'));
    }
}
