<?php

namespace Database\Seeders;

use App\Models\BotKeywordFilter;
use App\Models\BotSource;
use App\Models\Category;
use App\Models\City;
use App\Services\Bot\BotSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Haber Botu kurulum verisi:
 *   - Akdeniz Bölgesi 8 ili ve haber kategorileri
 *   - Canlı doğrulanmış resmî kaynak kataloğu (database/data/akdeniz-sources.json)
 *   - Başlangıç kelime filtreleri
 *
 * İdempotenttir: tekrar çalıştırıldığında mevcut kayıtları çoğaltmaz,
 * kaynakların selektörlerini günceller.
 */
class AkdenizBotSeeder extends Seeder
{
    /** Akdeniz Bölgesi illeri — menü sırasıyla */
    private const CITIES = ['Antalya', 'Mersin', 'Adana', 'Hatay', 'Kahramanmaraş', 'Osmaniye', 'Isparta', 'Burdur'];

    /**
     * Sitede zaten karşılığı olan kategoriler yeniden açılmasın.
     * Kurulacak slug => sitede bu sluglardan biri varsa atla.
     * (gundemakdeniz.com'da "Ekonomi & Tarım", "Turizm & Yaşam", "Kültür" gibi
     *  birleşik adlar kullanılıyor; sabit liste bunları görmezden gelip menüyü ikizliyordu.)
     */
    private const CATEGORY_ALIASES = [
        'ekonomi' => ['ekonomi-tarim'],
        'tarim' => ['ekonomi-tarim', 'tarim-dunyasi'],
        'turizm' => ['turizm-yasam'],
        'kultur-sanat' => ['kultur'],
    ];

    /** name => [renk, sıra] */
    private const CATEGORIES = [
        'Gündem' => ['#fe0000', 1],
        'Asayiş' => ['#b91c1c', 2],
        'Ekonomi' => ['#047857', 3],
        'Eğitim' => ['#1d4ed8', 4],
        'Sağlık' => ['#0891b2', 5],
        'Spor' => ['#ea580c', 6],
        'Kültür Sanat' => ['#7c3aed', 7],
        'Tarım' => ['#65a30d', 8],
        'Turizm' => ['#0284c7', 9],
    ];

    /** Haber sitesine uygun olmayan içerikleri havuza almadan eleyen başlangıç listesi */
    private const BLOCKED_KEYWORDS = [
        'ihale ilanı', 'satış ilanı', 'kiralama ilanı', 'zeyilname',
        'personel alım ilanı', 'sınav sonuç listesi', 'kura sonuçları',
        'iş bu ilan', 'tebligat', 'imar planı askı',
    ];

    public function run(): void
    {
        $cities = $this->seedCities();
        $categories = $this->seedCategories();
        $this->seedKeywordFilters();
        $this->seedSettings($categories);
        $this->seedSources($cities, $categories);
    }

    /** @return array<string,int> il adı => id */
    private function seedCities(): array
    {
        $map = [];
        foreach (self::CITIES as $order => $name) {
            $city = City::firstOrCreate(
                ['slug' => Str::slug($this->ascii($name))],
                ['name' => $name, 'is_active' => true, 'show_in_menu' => true, 'sort_order' => $order + 1],
            );
            $map[$name] = $city->id;
        }

        $this->command?->info('Şehirler hazır: ' . count($map));

        return $map;
    }

    /** @return array<string,int> kategori adı => id */
    private function seedCategories(): array
    {
        $map = [];
        $skipped = 0;

        foreach (self::CATEGORIES as $name => [$color, $order]) {
            $slug = Str::slug($this->ascii($name));

            // Sitede eşdeğeri varsa yeni kategori açma, mevcut olana bağlan
            $existing = null;
            foreach (self::CATEGORY_ALIASES[$slug] ?? [] as $aliasSlug) {
                $existing = Category::where('slug', $aliasSlug)->first();
                if ($existing) break;
            }

            if ($existing) {
                $map[$name] = $existing->id;
                $skipped++;
                continue;
            }

            $category = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'color' => $color, 'is_active' => true, 'show_in_menu' => true, 'sort_order' => $order],
            );
            $map[$name] = $category->id;
        }

        // Sitenin kendi kategorileri de eşleşebilsin (kaynak kataloğu bunlara işaret edebilir)
        foreach (Category::pluck('id', 'name') as $name => $id) {
            $map[$name] ??= $id;
        }

        $this->command?->info('Kategoriler hazır: ' . count($map) . ($skipped > 0 ? " ({$skipped} tanesi sitedeki mevcut kategoriye bağlandı)" : ''));

        return $map;
    }

    private function seedKeywordFilters(): void
    {
        foreach (self::BLOCKED_KEYWORDS as $keyword) {
            BotKeywordFilter::firstOrCreate(
                ['keyword' => $keyword],
                ['mode' => 'block', 'scope' => 'both', 'is_active' => true],
            );
        }

        $this->command?->info('Kelime filtreleri hazır: ' . count(self::BLOCKED_KEYWORDS));
    }

    private function seedSettings(array $categories): void
    {
        // Yalnız kayıtlı değeri olmayan ayarlar yazılır; operatörün düzenlemesi ezilmez
        $existing = array_keys(\Illuminate\Support\Facades\DB::table('bot_settings')->pluck('value', 'key')->toArray());

        $defaults = BotSettings::DEFAULTS;
        $defaults['default_category_id'] = (string) ($categories['Gündem'] ?? '');
        $defaults['publish_user_id'] = (string) (\App\Models\User::query()->orderBy('id')->value('id') ?? '');

        foreach ($defaults as $key => $value) {
            if (! in_array($key, $existing, true)) {
                BotSettings::set($key, $value);
            }
        }

        BotSettings::flush();
        $this->command?->info('Bot ayarları hazır');
    }

    private function seedSources(array $cities, array $categories): void
    {
        $path = database_path('data/akdeniz-sources.json');
        if (! is_file($path)) {
            $this->command?->warn("Kaynak kataloğu bulunamadı: {$path}");

            return;
        }

        $catalog = json_decode((string) file_get_contents($path), true);
        if (! is_array($catalog)) {
            $this->command?->error('Kaynak kataloğu okunamadı');

            return;
        }

        $created = $updated = 0;
        foreach ($catalog as $row) {
            if (empty($row['list_url'])) continue;

            $source = BotSource::firstOrNew(['list_url' => $row['list_url']]);
            $exists = $source->exists;

            $source->fill([
                'name' => $row['name'],
                'institution_type' => $row['institution_type'] ?? 'diger',
                'site_url' => $row['site_url'] ?? null,
                'type' => $row['type'] ?? 'html',
                'city_id' => isset($row['city']) ? ($cities[$row['city']] ?? null) : null,
                'category_id' => $categories[$row['category'] ?? ''] ?? $categories['Gündem'] ?? null,
                'link_selector' => $row['link_selector'] ?? null,
                'content_selector' => $row['content_selector'] ?? null,
                'image_selector' => $row['image_selector'] ?? null,
                'avg_words' => (int) ($row['avg_words'] ?? 0),
                'priority' => $this->priorityFor($row['institution_type'] ?? 'diger'),
                'notes' => $row['notes'] ?? null,
            ]);

            // Yeni kayıtta aktif başlat; mevcut kaydın operatör tarafından yapılmış
            // aktiflik tercihini bozma
            if (! $exists) {
                $source->is_active = true;
            }

            $source->save();
            $exists ? $updated++ : $created++;
        }

        $this->command?->info("Resmî kaynaklar: {$created} eklendi, {$updated} güncellendi");
    }

    /** Valilik ve büyükşehir haberleri daha değerli; tarama sırasında öne alınır */
    private function priorityFor(string $type): int
    {
        return match ($type) {
            'valilik' => 100,
            'belediye' => 80,
            'kaymakamlik' => 60,
            'il_mudurlugu' => 50,
            'universite' => 40,
            'kalkinma_ajansi' => 30,
            'oda_borsa' => 20,
            default => 10,
        };
    }

    private function ascii(string $s): string
    {
        return strtr($s, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'C', 'Ğ' => 'G', 'İ' => 'I', 'Ö' => 'O', 'Ş' => 'S', 'Ü' => 'U']);
    }
}
