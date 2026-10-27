<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gundemakdeniz.com',
            'password' => bcrypt('password'),
        ]);

        $this->call([
            CitySeeder::class,
            CategorySeeder::class,
            ArticleSeeder::class,
            BreakingNewsSeeder::class,
            PageSeeder::class,
        ]);
    }
}
