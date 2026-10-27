<?php

namespace Database\Seeders;

use App\Models\BreakingNews;
use Illuminate\Database\Seeder;

class BreakingNewsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['title' => 'Antalya\'da yeni altyapı projesi açıklandı', 'sort_order' => 1],
            ['title' => 'Mersin limanında yüzde 15 büyüme kaydedildi', 'sort_order' => 2],
            ['title' => 'Adana\'da iklim zirvesi gelecek ay yapılacak', 'sort_order' => 3],
            ['title' => 'Bölgesel teknoloji merkezi için finansman sağlandı', 'sort_order' => 4],
            ['title' => 'Hatay\'da yeniden inşa çalışmaları hızlandı', 'sort_order' => 5],
        ];

        foreach ($items as $item) {
            BreakingNews::create($item);
        }
    }
}
