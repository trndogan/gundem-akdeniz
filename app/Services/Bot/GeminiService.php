<?php

namespace App\Services\Bot;

use Anthropic\Client as ClaudeClient;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use App\Models\BotGeminiKey;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Haber metnini yapay zeka ile özgünleştirir.
 *
 * Sağlayıcı sırası: Claude anahtarı tanımlıysa önce Claude. Yanıt alınamazsa
 * (kredi bitti, hata, kısa çıktı) Gemini zinciri yedek olarak devreye girer:
 * önce ücretsiz anahtar havuzu (en az kullanılan sırayla), tükenirse ayarlardaki
 * ana anahtar. 403 alan anahtar kalıcı, 429 alan anahtar gün sonuna kadar devre
 * dışı bırakılır.
 */
class GeminiService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const MIN_CALL_INTERVAL = 4.5;   // 15 RPM sınırının altında kal
    private const MAX_ATTEMPTS = 3;
    private const BACKOFF = [4, 8];          // saniye

    private static float $lastCallAt = 0.0;

    private string $prompt;
    private string $paidKey;
    private string $paidModel;
    private string $freeModel;
    private float $temperature;
    private string $claudeKey;
    private string $claudeModel;
    private string $claudeEffort;
    private ?ClaudeClient $claude = null;

    public function __construct()
    {
        $this->prompt = (string) BotSettings::get('ai_prompt');
        $this->paidKey = trim((string) BotSettings::get('gemini_api_key'));
        $this->paidModel = (string) BotSettings::get('gemini_model');
        $this->freeModel = (string) BotSettings::get('gemini_free_model');
        $this->temperature = (float) BotSettings::get('ai_temperature');
        $this->claudeKey = trim((string) BotSettings::get('claude_api_key'));
        $this->claudeModel = (string) BotSettings::get('claude_model');
        $this->claudeEffort = (string) BotSettings::get('claude_effort');
    }

    /**
     * Ham haberi özgünleştirir.
     *
     * @return array{title:string,content:string,excerpt:string,tags:string,seo_title:string,
     *               seo_description:string,city:string,category:string,model:string}|null
     */
    public function rewrite(string $title, string $content): ?array
    {
        $instructions = $this->prompt . "\n\n" . $this->taxonomyBlock();
        $input = "Başlık: {$title}\n\nİçerik: {$content}";
        $fullPrompt = $instructions . "\n\n" . $input;
        $originalLength = mb_strlen($content);

        // 0) Claude
        if ($this->claudeKey !== '') {
            $result = $this->callClaude($instructions, $input, $this->claudeEffort);
            if (is_array($result) && ! $this->tooShort($result['content'], $originalLength)) {
                return $result + ['model' => $this->claudeModel];
            }
        }

        // 1) Ücretsiz anahtar havuzu
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $key = $this->nextFreeKey();
            if ($key === null) break;

            $result = $this->call($fullPrompt, $key->api_key, $key->model ?: $this->freeModel);

            if ($result === 'KEY_BANNED') {
                $key->update(['is_active' => false, 'last_error' => 'HTTP 403 — anahtar reddedildi']);
                BotLogger::warning('Gemini anahtarı kalıcı devre dışı bırakıldı', ['key' => $key->label], 'ai');
                continue;
            }
            if ($result === 'RATE_LIMITED') {
                $key->update(['is_exhausted' => true, 'last_error' => 'Günlük kota doldu']);
                continue;
            }
            if (is_array($result)) {
                if ($this->tooShort($result['content'], $originalLength)) {
                    break;   // kalite düşük, ana anahtara geç
                }
                $key->increment('today_usage');

                return $result + ['model' => $key->model ?: $this->freeModel];
            }
        }

        // 2) Ana (ücretli) anahtar
        if ($this->paidKey !== '') {
            $result = $this->call($fullPrompt, $this->paidKey, $this->paidModel);
            if (is_array($result) && ! $this->tooShort($result['content'], $originalLength)) {
                return $result + ['model' => $this->paidModel];
            }
        }

        if ($this->paidKey === '' && $this->nextFreeKey() === null) {
            BotLogger::error('Gemini çağrılamadı: kullanılabilir anahtar yok', [], 'ai');
        }

        return null;
    }

    /**
     * Sitenin gerçek kategori ve şehir adlarını isteme ekler.
     * Liste sabit yazılırsa sitenin taksonomisiyle ayrışır ve model olmayan
     * kategori adı uydurur; bu da yeni kategori açılmasına yol açar.
     */
    private function taxonomyBlock(): string
    {
        // Menüde gizli kategoriler özel amaçlıdır (örn. Vefat Haberleri); bot bunlara atama yapmasın
        $categories = Cache::remember('bot.taxonomy.categories', 300, fn () => Category::where('is_active', true)
            ->where('show_in_menu', true)
            ->orderBy('sort_order')
            ->pluck('name')
            ->all());
        $cities = Cache::remember('bot.taxonomy.cities', 300, fn () => City::where('is_active', true)->orderBy('sort_order')->pluck('name')->all());

        return "KATEGORİ LİSTESİ (birebir bu adlardan biri): " . implode(', ', $categories)
            . "\nŞEHİR LİSTESİ (birebir bu adlardan biri, emin değilseniz boş): " . implode(', ', $cities);
    }

    /**
     * Serbest biçimli JSON sorgusu. Haber yeniden yazma dışındaki işler (editör
     * puanlaması gibi) bunu kullanır; yanıt şemaya sokulmadan olduğu gibi çözümlenir.
     *
     * @return array<string,mixed>|null
     */
    public function ask(string $prompt): ?array
    {
        // Puanlama ucuz bir iş; Claude düşük eforla çalışır
        if ($this->claudeKey !== '') {
            $result = $this->callClaude('', $prompt, 'low', raw: true);
            if (is_array($result)) {
                return $result;
            }
        }

        // Gemini'de önce ücretsiz havuzu yorar, ana anahtarı yazıya saklar
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $key = $this->nextFreeKey();
            if ($key === null) break;

            $result = $this->call($prompt, $key->api_key, $key->model ?: $this->freeModel, raw: true);

            if ($result === 'KEY_BANNED') {
                $key->update(['is_active' => false, 'last_error' => 'HTTP 403 — anahtar reddedildi']);
                continue;
            }
            if ($result === 'RATE_LIMITED') {
                $key->update(['is_exhausted' => true, 'last_error' => 'Günlük kota doldu']);
                continue;
            }
            if (is_array($result)) {
                $key->increment('today_usage');

                return $result;
            }
        }

        if ($this->paidKey !== '') {
            $result = $this->call($prompt, $this->paidKey, $this->paidModel, raw: true);
            if (is_array($result)) {
                return $result;
            }
        }

        return null;
    }

    /** Markdown sarmalını ve kesik JSON'u toparlayıp diziye çevirir */
    private function parseJson(string $text): ?array
    {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        if (preg_match('/[\{\[].*/s', $text, $m)) {
            $text = $m[0];
        }

        $data = json_decode($text, true);

        return is_array($data) ? $data : null;
    }

    /** Panelde "bağlantıyı test et" için — kısa bir istekle anahtarı doğrular */
    public function testKey(string $apiKey, string $model): array
    {
        $response = $this->call('Yalnızca şu JSON\'u döndür: {"title":"test","content":"<p>test</p>"}', $apiKey, $model);

        return match (true) {
            $response === 'KEY_BANNED' => ['ok' => false, 'message' => 'Anahtar reddedildi (HTTP 403)'],
            $response === 'RATE_LIMITED' => ['ok' => false, 'message' => 'Kota dolu (HTTP 429)'],
            is_array($response) => ['ok' => true, 'message' => 'Anahtar çalışıyor'],
            default => ['ok' => false, 'message' => 'Yanıt alınamadı'],
        };
    }

    // ── İç işleyiş ──────────────────────────────────────────────────────────

    /**
     * Claude Messages API çağrısı. Talimat sistem istemine gider ve önbelleğe
     * alınır; aynı turda işlenen haberler talimatı yeniden tam fiyattan ödemez.
     * Her hatada null döner, çağıran Gemini zincirine geçer.
     *
     * @param  bool  $raw  true ise yanıt metni ham döner (haber yeniden yazma şemasına sokulmaz)
     * @return array<string,mixed>|null
     */
    private function callClaude(string $instructions, string $input, string $effort, bool $raw = false): ?array
    {
        $this->claude ??= new ClaudeClient(apiKey: $this->claudeKey);

        try {
            $message = $this->claude->beta->messages->create(
                maxTokens: 16000,
                messages: [['role' => 'user', 'content' => $input]],
                model: $this->claudeModel,
                system: $instructions === '' ? null : [
                    ['type' => 'text', 'text' => $instructions, 'cacheControl' => ['type' => 'ephemeral']],
                ],
                outputConfig: ['effort' => $effort],
                // Model politika gerekçesiyle reddederse sunucu yedek modelde yeniden dener
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (APIStatusException $e) {
            // 401 anahtar, 400 çoğunlukla kredi bitti, 429 limit; SDK geçici hataları zaten iki kez dener
            BotLogger::error("Claude hatası HTTP {$e->status}", [
                'model' => $this->claudeModel,
                'mesaj' => mb_substr($e->getMessage(), 0, 300),
            ], 'ai');

            return null;
        } catch (APIConnectionException) {
            BotLogger::warning('Claude bağlantı hatası', ['model' => $this->claudeModel], 'ai');

            return null;
        } catch (Throwable $e) {
            BotLogger::exception($e, 'ai');

            return null;
        }

        if ($message->stopReason === 'max_tokens') {
            BotLogger::warning('Claude yanıtı token sınırına takıldı, kesik yanıt reddedildi', ['model' => $this->claudeModel], 'ai');

            return null;
        }
        if ($message->stopReason === 'refusal') {
            BotLogger::warning('Claude haberi işlemeyi reddetti', ['model' => $this->claudeModel], 'ai');

            return null;
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        if (trim($text) === '') {
            BotLogger::warning('Claude boş yanıt döndü', ['model' => $this->claudeModel], 'ai');

            return null;
        }

        $parsed = $raw ? $this->parseJson($text) : $this->parse($text);
        if ($parsed === null) {
            BotLogger::error('Claude yanıtı çözümlenemedi', [
                'model' => $this->claudeModel,
                'ilk_200' => mb_substr($text, 0, 200),
            ], 'ai');
        }

        return $parsed;
    }

    private function nextFreeKey(): ?BotGeminiKey
    {
        $today = now()->toDateString();

        // Gün değiştiyse sayaçları sıfırla
        BotGeminiKey::where(fn ($q) => $q->whereNull('last_reset_date')->orWhere('last_reset_date', '<', $today))
            ->update(['today_usage' => 0, 'is_exhausted' => false, 'last_reset_date' => $today]);

        return BotGeminiKey::where('is_active', true)
            ->where('is_exhausted', false)
            ->whereColumn('today_usage', '<', 'daily_limit')
            ->orderBy('today_usage')
            ->first();
    }

    /**
     * @param  bool  $raw  true ise yanıt metni ham döner (haber yeniden yazma şemasına sokulmaz)
     * @return array<string,mixed>|string|null dizi = başarı, string = hata kodu
     */
    private function call(string $prompt, string $apiKey, string $model, bool $raw = false): array|string|null
    {
        $this->throttle();

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'topP' => 0.9,
                'maxOutputTokens' => 65536,
                'responseMimeType' => 'application/json',
            ],
        ];

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::timeout(120)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post(sprintf(self::ENDPOINT, $model), $payload);

                if ($response->status() === 403) return 'KEY_BANNED';

                if ($response->status() === 429) {
                    if ($attempt < self::MAX_ATTEMPTS) {
                        sleep(self::BACKOFF[$attempt - 1]);
                        continue;
                    }

                    return 'RATE_LIMITED';
                }

                if (in_array($response->status(), [500, 502, 503, 504], true)) {
                    if ($attempt < self::MAX_ATTEMPTS) {
                        sleep(self::BACKOFF[$attempt - 1]);
                        continue;
                    }

                    return null;
                }

                if ($response->failed()) {
                    BotLogger::error("Gemini hatası HTTP {$response->status()}", ['model' => $model], 'ai');

                    return null;
                }

                $data = $response->json();
                $text = data_get($data, 'candidates.0.content.parts.0.text', '');
                $finish = data_get($data, 'candidates.0.finishReason', '');

                if ($text === '') {
                    BotLogger::warning('Gemini boş yanıt döndü', ['model' => $model], 'ai');

                    return null;
                }
                if ($finish === 'MAX_TOKENS') {
                    BotLogger::warning('Gemini yanıtı token sınırına takıldı, kesik yanıt reddedildi', ['model' => $model], 'ai');

                    return null;
                }

                $parsed = $raw ? $this->parseJson($text) : $this->parse($text);
                if ($parsed === null) {
                    BotLogger::error('Gemini yanıtı çözümlenemedi', [
                        'model' => $model,
                        'ilk_200' => mb_substr($text, 0, 200),
                    ], 'ai');
                }

                return $parsed;
            } catch (ConnectionException) {
                if ($attempt < self::MAX_ATTEMPTS) {
                    sleep(self::BACKOFF[$attempt - 1]);
                    continue;
                }

                return null;
            } catch (Throwable $e) {
                BotLogger::exception($e, 'ai');

                return null;
            }
        }

        return null;
    }

    /** Aynı süreçte art arda çağrı yapılırsa dakikalık limite takılmamak için beklet */
    private function throttle(): void
    {
        $elapsed = microtime(true) - self::$lastCallAt;
        if (self::$lastCallAt > 0 && $elapsed < self::MIN_CALL_INTERVAL) {
            usleep((int) ((self::MIN_CALL_INTERVAL - $elapsed) * 1_000_000));
        }
        self::$lastCallAt = microtime(true);
    }

    /** Model bazen JSON'u markdown içine sarar veya sonunu keser; kurtarmayı dener */
    private function parse(string $text): ?array
    {
        $text = trim($text);

        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        if (preg_match('/\{.*/s', $text, $m)) {
            $text = $m[0];
        }

        $data = json_decode($text, true);

        // Kesik JSON: açık string ve süslü parantezi kapatmayı dene
        if (! is_array($data)) {
            $repaired = rtrim($text);
            if (! str_ends_with($repaired, '}')) {
                $quotes = preg_match_all('/(?<!\\\\)"/', $repaired);
                if ($quotes % 2 === 1) $repaired .= '"';
                $repaired .= '}';
            }
            $data = json_decode($repaired, true);
        }

        if (! is_array($data) || empty($data['title']) || empty($data['content'])) {
            return null;
        }

        return [
            'title' => $this->clean((string) $data['title']),
            'content' => $this->toHtml((string) $data['content']),
            'excerpt' => $this->clean((string) ($data['excerpt'] ?? '')),
            'tags' => $this->clean((string) ($data['tags'] ?? '')),
            'seo_title' => $this->clean((string) ($data['seo_title'] ?? $data['title'])),
            'seo_description' => $this->clean((string) ($data['seo_description'] ?? $data['excerpt'] ?? '')),
            'city' => $this->clean((string) ($data['city'] ?? '')),
            'category' => $this->clean((string) ($data['category'] ?? '')),
            'cover_headline' => mb_substr($this->clean((string) ($data['cover_headline'] ?? '')), 0, 90),
            'cover_facts' => $this->coverFacts($data['cover_facts'] ?? null),
        ];
    }

    /**
     * Kapak künyelerini normalize eder: dizi ya da virgüllü metin gelebilir.
     *
     * @return array<int,string>
     */
    private function coverFacts(mixed $raw): array
    {
        $items = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? explode(',', $raw) : []);

        $facts = [];
        foreach ($items as $item) {
            $fact = $this->clean((string) $item);
            if ($fact === '') continue;
            $facts[] = mb_substr($fact, 0, 40);
            if (count($facts) === 3) break;
        }

        return $facts;
    }

    /** Uzun tire yasağı proje kuralı; modelin kaçırdığı yerleri burada da temizle */
    private function clean(string $s): string
    {
        $s = str_replace(['—', '–'], ['', '-'], $s);

        return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }

    private function toHtml(string $content): string
    {
        $content = str_replace(['—', '–'], ['', '-'], $content);

        if (str_contains($content, '<p>') || str_contains($content, '<h2>')) {
            return trim($content);
        }

        $content = preg_replace('/^\*\*(.+?)\*\*$/m', '<h2>$1</h2>', $content) ?? $content;

        $html = '';
        foreach (preg_split('/\n{2,}/', trim($content)) ?: [] as $block) {
            $block = trim($block);
            if ($block === '') continue;
            $html .= str_starts_with($block, '<h2>') ? $block . "\n" : '<p>' . $block . "</p>\n";
        }

        return trim($html);
    }

    /** Kesik veya bozuk yanıtı yakalar: çıktı orijinalin çok altındaysa reddet */
    private function tooShort(string $aiContent, int $originalLength): bool
    {
        $aiLength = mb_strlen(strip_tags($aiContent));
        // Alt sınır kaynağın yarısı, tabanı 400, tavanı 2200 karakter (~340 kelime).
        // Eski eşik (çeyreği, tavan 800) fazla gevşekti: 742 kelimelik bir kaynakta
        // 133 kelimelik çıktı bile geçiyordu, haberler kaynaktan kısa kalıyordu.
        $minimum = min(max(300, (int) ($originalLength * 0.4)), 1500);

        if ($aiLength < $minimum) {
            BotLogger::warning('AI içeriği çok kısa, reddedildi', [
                'ai_karakter' => $aiLength,
                'orijinal_karakter' => $originalLength,
                'asgari' => $minimum,
            ], 'ai');

            return true;
        }

        return false;
    }
}
