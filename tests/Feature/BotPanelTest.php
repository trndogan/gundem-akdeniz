<?php

namespace Tests\Feature;

use App\Models\BotDraft;
use App\Models\BotPoolItem;
use App\Models\BotSource;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Services\Bot\BotSettings;
use App\Services\Bot\PublisherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Haber Botu panel ekranlarının açıldığını ve yayın zincirinin çalıştığını doğrular.
 * Ağa çıkmaz: tarama ve yapay zekâ adımları bu testin kapsamı dışındadır.
 */
class BotPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Editör',
            'email' => 'editor@test.local',
            'password' => bcrypt('secret'),
        ]);

        BotSettings::flush();
    }

    public function test_bot_ekranlari_aciliyor(): void
    {
        $source = $this->createSource();

        $urls = [
            '/gaadmin/bot-dashboard',
            '/gaadmin/bot-drafts',
            '/gaadmin/bot-sources',
            '/gaadmin/bot-sources/create',
            '/gaadmin/bot-sources/' . $source->id . '/edit',
            '/gaadmin/bot-pool-items',
            '/gaadmin/bot-settings-page',
            '/gaadmin/bot-gemini-keys',
            '/gaadmin/bot-keyword-filters',
            '/gaadmin/bot-logs',
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->user)
                ->get($url)
                ->assertSuccessful();
        }
    }

    public function test_taslak_makaleye_yayinlaniyor(): void
    {
        $source = $this->createSource();
        $category = Category::create(['name' => 'Gündem', 'slug' => 'gundem', 'color' => '#fe0000']);
        $city = City::create(['name' => 'Adana', 'slug' => 'adana']);

        BotSettings::set('publish_user_id', (string) $this->user->id);
        BotSettings::set('default_category_id', (string) $category->id);

        $draft = BotDraft::create([
            'source_id' => $source->id,
            'title' => 'Valilik koordinasyon toplantısı yapıldı',
            'excerpt' => 'Kısa özet.',
            'body' => '<p>Birinci paragraf.</p><p>İkinci paragraf.</p>',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'image_path' => 'bot/2026/08/ornek.webp',
            'image_mode' => 'processed',
            'status' => 'approved',
        ]);

        $result = (new PublisherService())->publish($draft);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertSame('published', $draft->fresh()->status);

        $article = $result['article'];
        $this->assertTrue($article->is_published);
        $this->assertSame($city->id, $article->city_id);
        $this->assertSame('bot/2026/08/ornek.webp', $article->image_url);
        $this->assertNotEmpty($article->slug);
    }

    public function test_yayin_kotasi_slot_saatlerine_gore_hesaplaniyor(): void
    {
        BotSettings::set('daily_limit', '20');
        BotSettings::set('publish_schedule', '00:01,00:02,23:58,23:59');

        $quota = (new PublisherService())->quota();

        // Günün ilk iki slotu geçmiş, son ikisi gelmemiş olmalı
        $this->assertSame(4, $quota['slots_total']);
        $this->assertSame(2, $quota['slots_passed']);
        $this->assertSame(10, $quota['target']);   // 20 limit × 2/4 slot
        $this->assertSame(10, $quota['deficit']);
    }

    public function test_havuz_kaydi_kaynaga_bagli(): void
    {
        $source = $this->createSource();

        $item = BotPoolItem::create([
            'source_id' => $source->id,
            'url' => 'https://www.adana.gov.tr/ornek-haber',
            'content_hash' => md5('https://www.adana.gov.tr/ornek-haber'),
            'title' => 'Örnek haber',
            'content' => str_repeat('kelime ', 120),
            'word_count' => 120,
        ]);

        $this->assertSame($source->id, $item->source->id);
        $this->assertSame('new', $item->status);
    }

    private function createSource(): BotSource
    {
        return BotSource::create([
            'name' => 'Adana Valiliği',
            'institution_type' => 'valilik',
            'site_url' => 'https://www.adana.gov.tr',
            'list_url' => 'https://www.adana.gov.tr/haberler',
            'link_selector' => 'a.card',
            'content_selector' => 'div.detail-content-container',
        ]);
    }
}
