<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Gündem', 'slug' => 'gundem', 'color' => '#fe0000', 'sort_order' => 1],
            ['name' => 'Ekonomi & Tarım', 'slug' => 'ekonomi-tarim', 'color' => '#059669', 'sort_order' => 2],
            ['name' => 'Turizm & Yaşam', 'slug' => 'turizm-yasam', 'color' => '#0284c7', 'sort_order' => 3],
            ['name' => 'Asayiş', 'slug' => 'asayis', 'color' => '#7c3aed', 'sort_order' => 4],
            ['name' => 'Spor', 'slug' => 'spor', 'color' => '#ea580c', 'sort_order' => 5],
            ['name' => 'Teknoloji', 'slug' => 'teknoloji', 'color' => '#2563eb', 'sort_order' => 6],
            ['name' => 'Kültür', 'slug' => 'kultur', 'color' => '#d946ef', 'sort_order' => 7],
            ['name' => 'Tarım Dünyası', 'slug' => 'tarim-dunyasi', 'color' => '#16a34a', 'sort_order' => 8, 'show_in_menu' => false],
            ['name' => 'Mavi Akdeniz', 'slug' => 'mavi-akdeniz', 'color' => '#0891b2', 'sort_order' => 9, 'show_in_menu' => false],
            ['name' => 'Gastronomi', 'slug' => 'gastronomi', 'color' => '#f59e0b', 'sort_order' => 10, 'show_in_menu' => false],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
