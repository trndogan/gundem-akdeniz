<?php

namespace App\Services\Bot;

use App\Models\BotPoolItem;
use Throwable;

/**
 * Yapay zekâ editör: havuzdaki ham haberleri okuyup haber değerine göre puanlar.
 *
 * Neden gerekli: resmî kurumlar haber ile duyuruyu ayırmaz. Kurs sertifikası
 * töreninden dere ıslahına, ihale ilanından valinin ziyaretine kadar her şey aynı
 * listede gelir. Bu katman olmadan Gemini kotası ve günlük yayın limiti değersiz
 * duyurulara harcanır, gerçek haber sıranın arkasında kalır.
 *
 * Puanlama tek çağrıda toplu yapılır: 20 haberin başlığı ve ilk paragrafı bir
 * istemde gider, model her biri için puan ve gerekçe döndürür. Böylece maliyet
 * haber başına bir çağrı değil, tur başına bir çağrıdır.
 */
class CuratorService
{
    /** Ayar okunamazsa kullanılacak parti boyutu */
    private const BATCH = 50;

    /** Modele gönderilen özet uzunluğu; puanlama için ilk paragraf yeterli */
    private const EXCERPT = 320;

    private GeminiService $gemini;

    public function __construct(?GeminiService $gemini = null)
    {
        $this->gemini = $gemini ?? new GeminiService();
    }

    /**
     * Puanlanmamış havuz kayıtlarını değerlendirir.
     *
     * @return array{degerlendirilen:int,elenen:int,onaya:int,manset:int,one_cikan:int,hata:?string}
     */
    public function curate(?int $limit = null): array
    {
        $stats = ['degerlendirilen' => 0, 'elenen' => 0, 'onaya' => 0, 'manset' => 0, 'one_cikan' => 0, 'parti' => 0, 'hata' => null];

        $partiBoyutu = max(5, BotSettings::int('curate_batch_size') ?: self::BATCH);
        $azamiParti = max(1, BotSettings::int('curate_max_batches'));

        // Elle tek seferlik çağrıda ($limit) tek parti çalışır
        if ($limit !== null) {
            $partiBoyutu = min($limit, $partiBoyutu);
            $azamiParti = 1;
        }

        for ($parti = 0; $parti < $azamiParti; $parti++) {
            $items = BotPoolItem::with('source')
                ->where('status', 'new')
                ->whereNull('score')
                ->orderByDesc('id')
                ->limit($partiBoyutu)
                ->get();

            if ($items->isEmpty()) {
                break;
            }

            try {
                $verdicts = $this->askModel($items);
            } catch (Throwable $e) {
                $stats['hata'] = mb_substr($e->getMessage(), 0, 200);
                BotLogger::exception($e, 'ai');
                break;
            }

            if ($verdicts === null) {
                $stats['hata'] = 'Editör yanıtı alınamadı';
                BotLogger::warning('Editör puanlaması yanıtsız kaldı', ['haber' => $items->count()], 'ai');
                break;
            }

            $stats['parti']++;
            $this->applyVerdicts($items, $verdicts, $stats);
        }

        // Manşet ve öne çıkan kararı puanlamadan sonra, tüm pencereye bakılarak verilir
        $terfi = $this->promote();
        $stats['manset'] = $terfi['manset'];
        $stats['one_cikan'] = $terfi['one_cikan'];

        return $stats;
    }

    /**
     * Model kararlarını havuz kayıtlarına uygular.
     *
     * @param  \Illuminate\Support\Collection<int,BotPoolItem>  $items
     * @param  array<int,array<string,mixed>>  $verdicts
     */
    private function applyVerdicts($items, array $verdicts, array &$stats): void
    {
        $minScore = BotSettings::int('curate_min_score');
        $sil = BotSettings::bool('curate_delete_rejected');

        foreach ($items as $item) {
            $verdict = $verdicts[$item->id] ?? null;

            // Model bu kaydı atladıysa bir daha aynı partiye takılmasın diye
            // sıradan haber puanı verilir; aksi halde kuyruk sonsuza kadar tıkanır.
            if ($verdict === null) {
                $item->forceFill([
                    'score' => 50,
                    'curation_reason' => 'Editör yanıtında yer almadı, varsayılan puan verildi',
                    'curated_at' => now(),
                ])->save();
                $stats['onaya']++;
                $stats['degerlendirilen']++;
                continue;
            }

            $score = max(0, min(100, (int) ($verdict['puan'] ?? 0)));
            $reason = mb_substr(trim((string) ($verdict['neden'] ?? '')), 0, 300);

            $item->forceFill([
                'score' => $score,
                'curation_reason' => $reason ?: null,
                'curated_at' => now(),
            ]);

            $stats['degerlendirilen']++;

            if ($score < $minScore) {
                $stats['elenen']++;

                // Haber değeri olmayan kayıt havuzda tutulmaz: gövde metni büyük,
                // listeyi kalabalıklaştırıyor ve bir daha işe yaramıyor.
                if ($sil) {
                    $item->delete();
                    continue;
                }

                $item->status = 'rejected';
                $item->reject_reason = 'Editör elemesi (' . $score . ' puan): ' . ($reason ?: 'haber değeri düşük');
                $item->save();
                continue;
            }

            $stats['onaya']++;
            $item->save();
        }
    }

    /**
     * Manşet ve öne çıkan işaretlerini belirler.
     *
     * Mutlak eşik tek başına işe yaramıyor: resmî kurum haberleri nadiren 85 puan
     * alır, dolayısıyla hiç manşet çıkmaz. Gerçek editörlük görecelidir, bugün elde
     * ne varsa en iyisi manşettir. Bu yüzden son X saatteki tüm puanlı haberler
     * sıralanır ve en iyileri günlük kota kadar terfi ettirilir.
     *
     * @return array{manset:int,one_cikan:int}
     */
    public function promote(): array
    {
        $mod = (string) BotSettings::get('promotion_mode');
        $pencere = max(6, BotSettings::int('promotion_window_hours'));
        $taban = BotSettings::int('promotion_floor_score');

        $aday = BotPoolItem::where('status', 'new')
            ->whereNotNull('score')
            ->where('curated_at', '>=', now()->subHours($pencere))
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->get();

        if ($aday->isEmpty()) {
            return ['manset' => 0, 'one_cikan' => 0];
        }

        // Mutlak kip: sabit eşiklerle karar ver
        if ($mod === 'absolute') {
            $mansetEsik = BotSettings::int('curate_headline_score');
            $oneCikanEsik = BotSettings::int('curate_featured_score');

            foreach ($aday as $item) {
                $item->forceFill([
                    'suggest_headline' => $item->score >= $mansetEsik,
                    'suggest_featured' => $item->score >= $oneCikanEsik,
                ])->save();
            }

            return [
                'manset' => $aday->where('score', '>=', $mansetEsik)->count(),
                'one_cikan' => $aday->where('score', '>=', $oneCikanEsik)->count(),
            ];
        }

        // Göreceli kip: bugün yayınlanmış manşet/öne çıkan sayısı kotadan düşülür
        $mansetKota = max(0, BotSettings::int('headline_per_day') - $this->bugunYayinlanan('is_headline'));
        $oneCikanKota = max(0, BotSettings::int('featured_per_day') - $this->bugunYayinlanan('is_featured'));

        $manset = 0;
        $oneCikan = 0;

        foreach ($aday as $item) {
            $mansetlik = false;
            $oneCikanlik = false;

            // Taban puanın altı hiçbir koşulda öne çıkmaz; kötü günün en iyisi manşet olmasın
            if ($item->score >= $taban) {
                if ($manset < $mansetKota) {
                    $mansetlik = true;
                    $manset++;
                } elseif ($oneCikan < $oneCikanKota) {
                    $oneCikanlik = true;
                    $oneCikan++;
                }
            }

            $item->forceFill([
                'suggest_headline' => $mansetlik,
                'suggest_featured' => $oneCikanlik,
            ])->save();
        }

        return ['manset' => $manset, 'one_cikan' => $oneCikan];
    }

    /** Bugün bu bayrakla yayınlanmış haber sayısı */
    private function bugunYayinlanan(string $alan): int
    {
        return \App\Models\BotDraft::where('status', 'published')
            ->whereDate('published_at', today())
            ->where($alan, true)
            ->count();
    }

    /**
     * Toplu puanlama isteği.
     *
     * @param  \Illuminate\Support\Collection<int,BotPoolItem>  $items
     * @return array<int,array<string,mixed>>|null  havuz id => karar
     */
    private function askModel($items): ?array
    {
        $liste = '';
        foreach ($items as $item) {
            $ozet = trim(preg_replace('/\s+/u', ' ', mb_substr($item->content, 0, self::EXCERPT)) ?? '');
            $liste .= "ID {$item->id}\n"
                . "Kurum: " . ($item->source?->name ?? 'bilinmiyor') . "\n"
                . "Başlık: {$item->title}\n"
                . "Giriş: {$ozet}\n\n";
        }

        $prompt = (string) BotSettings::get('curator_prompt')
            . "\n\nAşağıda " . $items->count() . " haber var. HER BİRİ için ayrı bir kayıt döndür, hiçbirini atlama.\n\n"
            . "DEĞERLENDİRİLECEK HABERLER:\n\n" . $liste;

        $response = $this->gemini->ask($prompt);
        if ($response === null) {
            return null;
        }

        // Model bazen {"haberler":[...]} bazen doğrudan [...] döndürür
        $rows = $response['haberler'] ?? $response['items'] ?? (array_is_list($response) ? $response : null);
        if (! is_array($rows)) {
            return null;
        }

        $verdicts = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'])) continue;
            $verdicts[(int) $row['id']] = $row;
        }

        return $verdicts !== [] ? $verdicts : null;
    }
}
