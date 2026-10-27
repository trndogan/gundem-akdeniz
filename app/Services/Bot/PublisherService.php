<?php

namespace App\Services\Bot;

use App\Http\Controllers\SitemapController;
use App\Models\Article;
use App\Models\BotDraft;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Onaylanmış taslağı sitedeki articles tablosuna yayınlar.
 *
 * Yayın anı önemlidir: onaylanan her taslak anında değil, ayarlardaki slot
 * saatlerine göre kademeli yayınlanır. Böylece haberler gün içine yayılır,
 * ana sayfa tek seferde 20 haberle dolmaz.
 */
class PublisherService
{
    /**
     * Taslağı yayınlar.
     *
     * @return array{ok:bool,article:?Article,error:?string}
     */
    public function publish(BotDraft $draft, bool $force = false): array
    {
        if ($draft->status === 'published' && $draft->article_id) {
            return ['ok' => true, 'article' => $draft->article, 'error' => null];
        }

        if (! $force && ! in_array($draft->status, ['approved', 'draft'], true)) {
            return ['ok' => false, 'article' => null, 'error' => 'Taslak yayına uygun durumda değil'];
        }

        $userId = $this->publisherId();
        if ($userId === null) {
            return ['ok' => false, 'article' => null, 'error' => 'Yayıncı kullanıcı tanımlı değil'];
        }

        $categoryId = $draft->category_id ?: (int) BotSettings::get('default_category_id');
        if (! $categoryId) {
            return ['ok' => false, 'article' => null, 'error' => 'Kategori atanmamış'];
        }

        try {
            $article = DB::transaction(function () use ($draft, $userId, $categoryId) {
                $article = Article::create([
                    'title' => $draft->title,
                    'excerpt' => $draft->excerpt,
                    'body' => $draft->body,
                    'category_id' => $categoryId,
                    'city_id' => $draft->city_id,
                    'user_id' => $userId,
                    'image_url' => $draft->image_path,
                    'is_published' => true,
                    'is_headline' => (bool) $draft->is_headline,
                    'is_featured' => (bool) $draft->is_featured,
                    'seo_title' => $draft->seo_title,
                    'seo_description' => $draft->seo_description,
                    'seo_keywords' => $draft->tags,
                    'published_at' => now(),
                ]);

                $draft->update([
                    'status' => 'published',
                    'article_id' => $article->id,
                    'published_at' => now(),
                ]);

                return $article;
            });

            $this->sitemapOnbellegiDusur();

            // Arama motorlarına anında bildir. Başarısızlığı yayını bozmaz:
            // haber zaten yayında, bildirim yalnızca keşfi hızlandırır.
            (new IndexNowService())->submit([$article->url]);          // Bing, Yandex

            $google = new GoogleIndexingService();                      // Google
            if ($google->hazir()) {
                $google->submit([$article->url]);
            }

            return ['ok' => true, 'article' => $article, 'error' => null];
        } catch (Throwable $e) {
            $draft->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
            BotLogger::exception($e, 'publish', $draft->source_id);

            return ['ok' => false, 'article' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sitemap önbelleğini düşürür.
     *
     * Sitemap parçaları bir saat önbelleklenir. Bot 15 dakikada bir yayınladığı
     * için yeni haber sitemap'e bir saate kadar geç düşüyordu; haber sitesinde
     * bu doğrudan indekslenme gecikmesi demek. Yayın anında ilgili anahtarları
     * düşürmek, sitemap'i her istekte yeniden üretmeden güncel tutar.
     */
    protected function sitemapOnbellegiDusur(): void
    {
        Cache::forget('sitemap.index');
        Cache::forget('sitemap.news');
        Cache::forget('sitemap.categories');
        Cache::forget('sitemap.cities');

        // posts sitemap'i sayfalanmıştır; haber sayısı arttıkça sayfa eklenir.
        // Sayfa boyutu denetleyiciden okunur ki iki yerde kaymasın.
        $boyut = SitemapController::POSTS_PER_SITEMAP;
        $sayfa = max(1, (int) ceil(Article::published()->count() / $boyut));
        for ($i = 1; $i <= $sayfa + 1; $i++) {
            Cache::forget("sitemap.posts.{$i}");
        }
    }

    /**
     * Yayın kotası. İki kip vardır:
     *
     * interval: Bot her turda (varsayılan 15 dakika) sırada haber varsa yayınlar.
     *   Haber akışı doğal olur, hazır olan bekletilmez. Günlük tavan yine geçerlidir.
     *
     * slots: Yayın yalnızca tanımlı saat dilimlerinde yapılır ve gün içine oranlanır.
     *   Cron bir süre durursa gün sonunda hedefe yetişilir.
     *
     * @return array{mode:string,target:int,published:int,deficit:int,per_run:int,
     *               slots_passed:int,slots_total:int,active:bool}
     */
    public function quota(): array
    {
        $limit = max(0, BotSettings::int('daily_limit'));
        $publishedToday = BotDraft::where('status', 'published')
            ->whereDate('published_at', today())
            ->count();

        $mode = (string) BotSettings::get('publish_mode');
        $active = $this->withinActiveHours();

        if ($mode !== 'slots') {
            return [
                'mode' => 'interval',
                'target' => $limit,
                'published' => $publishedToday,
                'deficit' => max(0, $limit - $publishedToday),
                'per_run' => max(1, BotSettings::int('publish_per_run')),
                'slots_passed' => 0,
                'slots_total' => 0,
                'active' => $active,
            ];
        }

        $slots = BotSettings::schedule();
        $now = now();
        $passed = 0;
        foreach ($slots as $slot) {
            if (now()->setTimeFromTimeString($slot)->lessThanOrEqualTo($now->copy()->addMinutes(2))) {
                $passed++;
            }
        }

        $total = count($slots);
        $target = match (true) {
            $total === 0 || $limit === 0 => 0,
            $total >= $limit => min($passed, $limit),
            $passed >= $total => $limit,
            default => (int) ceil($limit * $passed / $total),
        };

        return [
            'mode' => 'slots',
            'target' => $target,
            'published' => $publishedToday,
            'deficit' => max(0, $target - $publishedToday),
            'per_run' => max(1, BotSettings::int('publish_per_run')),
            'slots_passed' => $passed,
            'slots_total' => $total,
            'active' => $active,
        ];
    }

    /** Yayın saati penceresi tanımlıysa şu an içinde miyiz */
    public function withinActiveHours(): bool
    {
        $pencere = trim((string) BotSettings::get('publish_active_hours'));
        if ($pencere === '' || ! str_contains($pencere, '-')) {
            return true;
        }

        [$bas, $bit] = array_map('trim', explode('-', $pencere, 2));
        try {
            $simdi = now();

            return $simdi->betweenIncluded(
                now()->setTimeFromTimeString($bas),
                now()->setTimeFromTimeString($bit),
            );
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Kotaya göre onaylı taslakları yayınlar.
     *
     * @return array{published:int,failed:int,deficit:int}
     */
    public function publishDue(): array
    {
        $quota = $this->quota();
        $stats = ['published' => 0, 'failed' => 0, 'deficit' => $quota['deficit']];

        if ($quota['deficit'] <= 0 || ! $quota['active']) {
            return $stats;
        }

        // Aralık kipinde tur başına sabit sayıda yayınlanır, kalan sonraki tura bırakılır
        $adet = $quota['mode'] === 'interval'
            ? min($quota['deficit'], $quota['per_run'])
            : $quota['deficit'];

        // Yüksek haber değerli içerik önce yayınlansın
        $drafts = BotDraft::where('status', 'approved')
            ->orderByRaw('score IS NULL')
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->limit($adet)
            ->get();

        foreach ($drafts as $draft) {
            $result = $this->publish($draft);
            $result['ok'] ? $stats['published']++ : $stats['failed']++;
        }

        return $stats;
    }

    /** Bir sonraki yayın anı — panelde gösterilir */
    public function nextSlot(): ?string
    {
        if ((string) BotSettings::get('publish_mode') !== 'slots') {
            $dakika = 15;
            $sonraki = now()->addMinutes($dakika - (now()->minute % $dakika))->setSecond(0);

            return $sonraki->format('H:i');
        }

        $now = now();
        foreach (BotSettings::schedule() as $slot) {
            if (now()->setTimeFromTimeString($slot)->greaterThan($now)) {
                return $slot;
            }
        }

        return BotSettings::schedule()[0] ?? null;   // yarının ilk slotu
    }

    private function publisherId(): ?int
    {
        $configured = (int) BotSettings::get('publish_user_id');
        if ($configured > 0 && User::whereKey($configured)->exists()) {
            return $configured;
        }

        return User::query()->orderBy('id')->value('id');
    }
}
