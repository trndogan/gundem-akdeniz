<?php

namespace App\Services\Bot;

use App\Models\BotDraft;
use App\Models\BotPoolItem;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ham havuzdaki haberi Gemini ile özgünleştirir, görselini üretir ve onay kuyruğuna taslak yazar.
 *
 * Kategori/şehir ataması sırası: modelin önerisi → kaynağın varsayılanı → genel varsayılan.
 * Model uydurma bir kategori döndürürse eşleşmez ve kaynağın ayarı geçerli olur.
 */
class ProcessorService
{
    private GeminiService $gemini;
    private ImageComposer $images;

    /** @var array<string,int>|null */
    private ?array $categoryMap = null;

    /** @var array<string,int>|null */
    private ?array $cityMap = null;

    public function __construct(?GeminiService $gemini = null, ?ImageComposer $images = null)
    {
        $this->gemini = $gemini ?? new GeminiService();
        $this->images = $images ?? new ImageComposer();
    }

    /**
     * Tek bir havuz kaydını işler.
     *
     * @return array{ok:bool,draft:?BotDraft,error:?string}
     */
    public function process(BotPoolItem $item): array
    {
        $item->update(['status' => 'processing']);

        try {
            $rewritten = $this->gemini->rewrite($item->title, $item->content);

            if ($rewritten === null) {
                $item->update(['status' => 'failed', 'reject_reason' => 'Yapay zekâ yanıtı alınamadı']);

                return ['ok' => false, 'draft' => null, 'error' => 'Yapay zekâ yanıtı alınamadı'];
            }

            $source = $item->source;
            $categoryId = $this->resolveCategory($rewritten['category'], $source?->category_id);
            $cityId = $this->resolveCity($rewritten['city'], $source?->city_id);

            $image = $this->images->compose(
                $rewritten['title'],
                $item->image_url,
                $this->categoryName($categoryId),
                $rewritten['excerpt'],
                $rewritten['cover_headline'] ?? '',
                $rewritten['cover_facts'] ?? [],
                // Yapay zekâ kapağı yalnızca vitrine çıkan haberlere; maliyet orada anlamlı
                (bool) ($item->suggest_headline || $item->suggest_featured),
            );

            $draft = BotDraft::create([
                'pool_id' => $item->id,
                'source_id' => $item->source_id,
                'title' => mb_substr($rewritten['title'], 0, 500),
                'slug' => Str::slug($this->ascii($rewritten['title'])) ?: null,
                'excerpt' => $rewritten['excerpt'] ?: null,
                'body' => $rewritten['content'],
                'tags' => $rewritten['tags'] ?: null,
                'cover_headline' => $rewritten['cover_headline'] ?: null,
                'cover_facts' => $rewritten['cover_facts'] ?: null,
                'seo_title' => mb_substr($rewritten['seo_title'] ?: $rewritten['title'], 0, 255),
                'seo_description' => mb_substr($rewritten['seo_description'] ?: $rewritten['excerpt'], 0, 500),
                'category_id' => $categoryId,
                'city_id' => $cityId,
                'image_path' => $image['path'],
                'image_mode' => $image['mode'],
                'original_url' => $item->url,
                'score' => $item->score,
                'is_headline' => (bool) $item->suggest_headline,
                'is_featured' => (bool) $item->suggest_featured,
                'status' => $this->initialStatus($item),
                'ai_model' => $rewritten['model'] ?? null,
            ]);

            $item->update(['status' => 'used']);

            return ['ok' => true, 'draft' => $draft, 'error' => null];
        } catch (Throwable $e) {
            $item->update(['status' => 'failed', 'reject_reason' => mb_substr($e->getMessage(), 0, 250)]);
            BotLogger::exception($e, 'ai', $item->source_id);

            return ['ok' => false, 'draft' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Taslağın başlangıç durumu.
     *
     * Editör puanı eşiği aşıyorsa taslak onay beklemeden yayın sırasına girer;
     * yine de anında yayınlanmaz, slot kotası gün içine yayar.
     */
    private function initialStatus(BotPoolItem $item): string
    {
        if (BotSettings::bool('auto_approve')) {
            return 'approved';
        }

        $esik = BotSettings::int('curate_autopublish_score');
        if ($esik > 0 && $item->score !== null && $item->score >= $esik) {
            return 'approved';
        }

        return 'draft';
    }

    /** Taslağın görselini yeniden üretir (panelden "görseli yenile" eylemi) */
    public function regenerateImage(BotDraft $draft): bool
    {
        $sourceImage = $draft->poolItem?->image_url;
        $image = $this->images->compose(
            $draft->title,
            $sourceImage,
            $this->categoryName($draft->category_id),
            $draft->excerpt,
            (string) $draft->cover_headline,
            (array) ($draft->cover_facts ?? []),
            (bool) ($draft->is_headline || $draft->is_featured),
        );

        if ($image['path'] === null) {
            return false;
        }

        $draft->update(['image_path' => $image['path'], 'image_mode' => $image['mode']]);

        return true;
    }

    // ── Eşleme ──────────────────────────────────────────────────────────────

    private function resolveCategory(?string $name, ?int $fallback): ?int
    {
        if ($this->categoryMap === null) {
            $this->categoryMap = Category::pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])
                ->all();
        }

        $key = $this->key((string) $name);

        return $this->categoryMap[$key]
            ?? $fallback
            ?? (int) BotSettings::get('default_category_id') ?: null;
    }

    private function resolveCity(?string $name, ?int $fallback): ?int
    {
        if ($this->cityMap === null) {
            $this->cityMap = City::pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [$this->key($name) => $id])
                ->all();
        }

        return $this->cityMap[$this->key((string) $name)] ?? $fallback;
    }

    private function categoryName(?int $id): string
    {
        return $id ? (string) Category::where('id', $id)->value('name') : '';
    }

    /** Karşılaştırma anahtarı: küçük harf + Türkçe karakter sadeleştirme */
    private function key(string $s): string
    {
        return trim(mb_strtolower($this->ascii($s), 'UTF-8'));
    }

    private function ascii(string $s): string
    {
        return strtr($s, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'C', 'Ğ' => 'G', 'İ' => 'I', 'Ö' => 'O', 'Ş' => 'S', 'Ü' => 'U']);
    }
}
