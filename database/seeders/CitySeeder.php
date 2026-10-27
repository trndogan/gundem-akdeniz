<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Antalya', 'slug' => 'antalya', 'sort_order' => 1],
            ['name' => 'Mersin', 'slug' => 'mersin', 'sort_order' => 2],
            ['name' => 'Adana', 'slug' => 'adana', 'sort_order' => 3],
            ['name' => 'Hatay', 'slug' => 'hatay', 'sort_order' => 4],
            ['name' => 'Isparta', 'slug' => 'isparta', 'sort_order' => 5],
            ['name' => 'Burdur', 'slug' => 'burdur', 'sort_order' => 6],
            ['name' => 'Osmaniye', 'slug' => 'osmaniye', 'sort_order' => 7],
            ['name' => 'Kahramanmaraş', 'slug' => 'kahramanmaras', 'sort_order' => 8],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
