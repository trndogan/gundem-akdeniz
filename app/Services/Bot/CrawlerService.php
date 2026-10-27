<?php

namespace App\Services\Bot;

use App\Models\BotKeywordFilter;
use App\Models\BotPoolItem;
use App\Models\BotSource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Resmî kaynakları tarar, haber gövdelerini çıkarır ve ham havuza yazar.
 *
 * Selektörler kaynakta kayıtlıysa onlar kullanılır; boşsa SourceDetector devreye
 * girip sayfayı kendi analiz eder. Böylece kurum sitesi şablonunu değiştirdiğinde
 * kaynak tamamen susmaz, otomatik moda düşer.
 */
class CrawlerService
{
    private SourceDetector $detector;

    /** @var array<int,array{title:string,normalized:string}>|null */
    private ?array $recentTitles = null;

    /** @var array<int,BotKeywordFilter>|null */
    private ?array $filters = null;

    private int $minWords;
    private int $maxAgeDays;
    private int $similarityThreshold;
    private int $maxLinks;
    private int $concurrency;

    public function __construct(?SourceDetector $detector = null)
    {
        $this->detector = $detector ?? new SourceDetector();
        $this->minWords = BotSettings::int('min_words');
        $this->maxAgeDays = BotSettings::int('max_age_days');
        $this->similarityThreshold = BotSettings::int('similarity_threshold');
        $this->maxLinks = BotSettings::int('max_links_per_source');
        $this->concurrency = max(1, BotSettings::int('crawl_concurrency'));
    }

    /**
     * Bir kaynağı tarar.
     *
     * @return array{saved:int,seen:int,skipped:array<string,int>,error:?string}
     */
    public function crawl(BotSource $source, ?array $listPage = null): array
    {
        $stats = ['saved' => 0, 'seen' => 0, 'skipped' => [], 'error' => null];
        $this->contentSignatures = [];

        try {
            $items = $source->type === 'rss'
                ? $this->collectFromFeed($source, $stats)
                : $this->collectFromHtml($source, $stats, $listPage);

            foreach ($items as $item) {
                if ($this->store($item, $stats)) {
                    $stats['saved']++;
                }
            }

            // Aynı gövde birden fazla habere çekildiyse selektör yanlış bloğu gösteriyordur.
            // Kaynak sessizce çöp üretmesin diye hata olarak işaretlenir ve panelde görünür.
            $tekrar = $stats['skipped']['ayni_govde'] ?? 0;
            if ($tekrar >= 2 && $stats['saved'] === 0) {
                $source->forceFill([
                    'consecutive_errors' => $source->consecutive_errors + 1,
                    'last_error' => "Her habere aynı gövde çekiliyor ({$tekrar} tekrar). İçerik selektörü yanlış bloğu gösteriyor olabilir; sayfa JavaScript ile yükleniyorsa bu kaynak statik olarak çekilemez.",
                ]);
                BotLogger::warning("Kaynakta gövde tekrarı: {$source->name}", ['tekrar' => $tekrar], 'crawl', $source->id);
            } else {
                $source->forceFill([
                    'last_success_at' => now(),
                    'consecutive_errors' => 0,
                    'last_error' => null,
                    'fetched_total' => $source->fetched_total + $stats['saved'],
                ]);
            }
        } catch (Throwable $e) {
            $stats['error'] = mb_substr($e->getMessage(), 0, 300);
            $source->forceFill([
                'consecutive_errors' => $source->consecutive_errors + 1,
                'last_error' => $stats['error'],
            ]);
            BotLogger::error("Kaynak taranamadı: {$source->name}", ['hata' => $stats['error']], 'crawl', $source->id);
        } finally {
            // Başarı ya da hata fark etmez: sıraya girsin ki yavaş kaynak rotasyonu tıkamasın
            $source->forceFill(['last_crawled_at' => now()])->save();
        }

        return $stats;
    }

    // ── Toplama ─────────────────────────────────────────────────────────────

    /** @return array<int,array<string,mixed>> */
    private function collectFromHtml(BotSource $source, array &$stats, ?array $listPage = null): array
    {
        $page = $listPage ?? $this->detector->fetch($source->list_url);
        if ($page === null) {
            throw new \RuntimeException('Liste sayfası açılamadı');
        }

        $links = $this->extractLinks($page['html'], $page['url'], $source);
        $stats['seen'] = count($links);

        // Havuzda olmayanları ayıkla, sonra hepsini tek seferde eşzamanlı çek.
        // Detay sayfalarını sırayla çekmek tarama süresinin büyük kısmını yiyordu.
        $bekleyen = [];
        foreach (array_slice($links, 0, $this->maxLinks) as $link) {
            $hash = md5($link['url']);
            if ($this->alreadyInPool($hash)) {
                $stats['skipped']['mevcut'] = ($stats['skipped']['mevcut'] ?? 0) + 1;
                continue;
            }
            $bekleyen[$link['url']] = $link;
        }

        if ($bekleyen === []) {
            return [];
        }

        $sayfalar = $this->detector->fetchMany(array_combine(array_keys($bekleyen), array_keys($bekleyen)), $this->concurrency);

        $items = [];
        foreach ($bekleyen as $url => $link) {
            $detail = $sayfalar[$url] ?? null;
            if ($detail === null) {
                $stats['skipped']['detay_acilmadi'] = ($stats['skipped']['detay_acilmadi'] ?? 0) + 1;
                continue;
            }

            $body = $this->extractDetail($detail['html'], $detail['url'], $source);
            if ($body['content'] === '') {
                $stats['skipped']['govde_yok'] = ($stats['skipped']['govde_yok'] ?? 0) + 1;
                continue;
            }

            // Kart başlıkları "28.08.2026 Vali ..." biçiminde gelir: tarihi ayır, başlıktan çıkar
            $rawTitle = $this->pickTitle($link['title'], $body, $source);
            if ($rawTitle === '') {
                $stats['skipped']['baslik_yok'] = ($stats['skipped']['baslik_yok'] ?? 0) + 1;
                continue;
            }

            $title = $this->cleanTitle($rawTitle);
            $content = $this->stripLeadingTitle($body['content'], $title);

            $items[] = [
                'source_id' => $source->id,
                'url' => $url,
                'content_hash' => md5($url),
                'title' => mb_substr($title, 0, 500),
                'content' => $content,
                'image_url' => $body['image_url'],
                'word_count' => $this->detector->wordCount($content),
                'source_published_at' => $body['published_at'] ?? $this->detector->dateFromText(mb_substr($rawTitle, 0, 24)),
            ];
        }

        return $items;
    }

    /**
     * Birden çok kaynağın liste sayfasını eşzamanlı çeker.
     *
     * @param  \Illuminate\Support\Collection<int,BotSource>  $sources
     * @return array<int,array{html:string,url:string,code:int}>  kaynak id => sayfa
     */
    public function prefetchLists($sources): array
    {
        $adresler = [];
        foreach ($sources as $source) {
            $adresler[$source->id] = $source->list_url;
        }

        return $this->detector->fetchMany($adresler, $this->concurrency);
    }

    /** @return array<int,array<string,mixed>> */
    private function collectFromFeed(BotSource $source, array &$stats): array
    {
        $res = $this->detector->fetch($source->list_url);
        if ($res === null) {
            throw new \RuntimeException('Besleme açılamadı');
        }

        $xml = @simplexml_load_string($res['html'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \RuntimeException('Besleme çözümlenemedi');
        }

        $entries = $xml->channel->item ?? $xml->entry ?? [];
        $stats['seen'] = count($entries);

        $items = [];
        foreach ($entries as $entry) {
            if (count($items) >= $this->maxLinks) break;

            $title = $this->detector->normalize((string) ($entry->title ?? ''));
            $url = trim((string) ($entry->link['href'] ?? $entry->link ?? ''));
            if ($title === '' || $url === '') continue;

            $hash = md5($url);
            if ($this->alreadyInPool($hash)) {
                $stats['skipped']['mevcut'] = ($stats['skipped']['mevcut'] ?? 0) + 1;
                continue;
            }

            $pubDate = (string) ($entry->pubDate ?? $entry->published ?? $entry->updated ?? '');
            $publishedAt = $pubDate !== '' && ($ts = strtotime($pubDate)) !== false ? date('Y-m-d H:i:s', $ts) : null;

            $detail = $this->detector->fetch($url);
            if ($detail === null) continue;

            $body = $this->extractDetail($detail['html'], $detail['url'], $source);
            if ($body['content'] === '') {
                $stats['skipped']['govde_yok'] = ($stats['skipped']['govde_yok'] ?? 0) + 1;
                continue;
            }

            $title = $this->cleanTitle($title);
            $content = $this->stripLeadingTitle($body['content'], $title);

            $items[] = [
                'source_id' => $source->id,
                'url' => $url,
                'content_hash' => $hash,
                'title' => mb_substr($title, 0, 500),
                'content' => $content,
                'image_url' => $body['image_url'] ?: $this->imageFromFeedEntry($entry),
                'word_count' => $this->detector->wordCount($content),
                'source_published_at' => $publishedAt ?? $body['published_at'],
            ];
        }

        return $items;
    }

    /** RSS öğesindeki enclosure / media:content görselleri */
    private function imageFromFeedEntry(\SimpleXMLElement $entry): ?string
    {
        if (isset($entry->enclosure['url'])) return (string) $entry->enclosure['url'];

        $media = $entry->children('media', true);
        if (isset($media->content['url'])) return (string) $media->content['url'];
        if (isset($media->thumbnail['url'])) return (string) $media->thumbnail['url'];

        $html = (string) ($entry->children('content', true)->encoded ?? $entry->description ?? '');
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) return $m[1];

        return null;
    }

    /**
     * Liste sayfasından haber linklerini çıkarır.
     *
     * @return array<int,array{url:string,title:string}>
     */
    private function extractLinks(string $html, string $baseUrl, BotSource $source): array
    {
        $links = [];

        if ($source->link_selector) {
            try {
                $crawler = new Crawler(SourceDetector::stripNoise($html), $baseUrl);
                $crawler->filter($source->link_selector)->each(function (Crawler $node) use (&$links, $baseUrl) {
                    $href = $this->detector->absolutize((string) $node->attr('href'), $baseUrl);
                    if ($href === '' || ! $this->detector->sameHost($href, $baseUrl)) return;

                    $text = $this->detector->normalize($node->text(''));
                    if ($text === '') {
                        $img = $node->filter('img[alt]');
                        if ($img->count() > 0) $text = $this->detector->normalize((string) $img->first()->attr('alt'));
                    }

                    $links[strtok($href, '#')] = ['url' => strtok($href, '#'), 'title' => $text];
                });
            } catch (Throwable) {
                $links = [];
            }
        }

        // Selektör yok ya da tutmadı → otomatik grup tespiti
        if (count($links) < 2) {
            $groups = $this->detector->linkGroups($html, $baseUrl, 1);
            foreach ($groups[0]['links'] ?? [] as $l) {
                $links[$l['href']] = ['url' => $l['href'], 'title' => $l['text']];
            }
        }

        return array_values($links);
    }

    /**
     * Detay sayfasından gövde, görsel ve tarihi çıkarır.
     *
     * @return array{content:string,title:string,image_url:?string,words:int,published_at:?string}
     */
    private function extractDetail(string $html, string $url, BotSource $source): array
    {
        $result = ['content' => '', 'title' => '', 'h1' => '', 'og_title' => '', 'page_title' => '',
            'image_url' => null, 'words' => 0, 'published_at' => null];

        // 1) Kayıtlı selektörle dene
        if ($source->content_selector) {
            $extracted = $this->detector->extractWith($html, $url, $source->content_selector);
            if ($extracted['words'] >= 40) {
                $result['content'] = $this->cleanContent($extracted['text']);
                $result['words'] = $this->detector->wordCount($result['content']);
            }
        }

        // 2) Tutmadıysa otomatik tespite düş
        if ($result['words'] < 40) {
            $auto = $this->detector->extractBody($html);
            if ($auto['content_selector'] !== null) {
                $full = $this->detector->extractWith($html, $url, $auto['content_selector']);
                $result['content'] = $this->cleanContent($full['text'] ?: $auto['text']);
                $result['words'] = $this->detector->wordCount($result['content']);
                $result['image_url'] = $auto['image_url'];
                $result['published_at'] = $auto['published_at'];
            }
        }

        // Görsel: kayıtlı selektör → otomatik tespit
        if ($result['image_url'] === null) {
            $result['image_url'] = $this->extractImage($html, $url, $source);
        }
        if ($result['image_url'] !== null) {
            $result['image_url'] = $this->detector->absolutize($result['image_url'], $url);
        }

        if ($result['published_at'] === null) {
            $result['published_at'] = $this->extractPublishedAt($html, $result['content']);
        }

        $result['h1'] = $this->firstMatch($html, '#<h1[^>]*>(.*?)</h1>#is');
        $result['og_title'] = $this->firstMatch($html, '#<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']#i');
        $result['page_title'] = $this->firstMatch($html, '#<title[^>]*>(.*?)</title>#is');
        $result['title'] = $result['h1'] ?: $result['og_title'] ?: $result['page_title'];

        return $result;
    }

    private function extractImage(string $html, string $url, BotSource $source): ?string
    {
        if (! $source->image_selector) {
            return null;
        }

        try {
            $crawler = new Crawler(SourceDetector::stripNoise($html), $url);
            $node = $crawler->filter($source->image_selector);
            if ($node->count() === 0) return null;

            // meta[property="og:image"] gibi öznitelik tabanlı selektörler
            if (str_starts_with(trim($source->image_selector), 'meta')) {
                $content = (string) $node->first()->attr('content');

                return $content !== '' ? $content : null;
            }

            $first = $node->first();
            $src = (string) ($first->attr('data-src') ?: $first->attr('data-original') ?: $first->attr('src'));

            return ($src !== '' && ! str_starts_with($src, 'data:')) ? $src : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function extractPublishedAt(string $html, string $content): ?string
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . SourceDetector::stripNoise($html), LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return $this->detector->detectPublishedAt(new \DOMXPath($doc))
            ?? $this->detector->dateFromText(mb_substr($content, 0, 400));
    }

    /** Verilen desenin ilk yakalamasını normalize ederek döndürür */
    private function firstMatch(string $html, string $pattern): string
    {
        if (! preg_match($pattern, $html, $m)) {
            return '';
        }

        $text = $this->detector->normalize(strip_tags(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return mb_substr($text, 0, 300);
    }

    /**
     * Başlığı seçer.
     *
     * Liste sayfasındaki bağlantı metni güvenilmez: kimi sitede "Detaya Git", kimisinde
     * kurum adı, kimisinde haberin ilk cümlesinin tamamı geliyor. Bu yüzden önce detay
     * sayfasının h1 etiketine, sonra og:title'a bakılır.
     */
    private function pickTitle(string $linkText, array $body, BotSource $source): string
    {
        $adaylar = [$body['h1'] ?? '', $linkText, $body['og_title'] ?? '', $this->stripSiteName($body['page_title'] ?? '', $source)];

        foreach ($adaylar as $aday) {
            $aday = $this->cleanTitle($this->detector->normalize($aday));
            if ($this->isUsableTitle($aday, $source)) {
                return $aday;
            }
        }

        return '';
    }

    /** Gezinme metni, kurum adı ya da gövdeyi yutmuş başlıklar elenir */
    private function isUsableTitle(string $title, BotSource $source): bool
    {
        $uzunluk = mb_strlen($title);
        if ($uzunluk < 15 || $uzunluk > 160) {
            return false;
        }

        $junk = 'detaya git|devam[ıi]n[ıi] oku|devam[ıi]|t[üu]m[üu]n[üu] g[öo]r|daha fazla|read more|haberin devam[ıi]|t[ıi]klay[ıi]n[ıi]z|oku>|>>';
        if (preg_match('/^(' . $junk . ')/iu', $title) || preg_match('/(' . $junk . ')$/iu', $title)) {
            return false;
        }

        // Kurum adının kendisi başlık olamaz
        $sade = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title) ?? $title, 'UTF-8');
        $kurum = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $source->name) ?? $source->name, 'UTF-8');
        if (trim($sade) === trim($kurum) || str_starts_with(trim($sade), 't c ' . trim($kurum))) {
            return false;
        }

        // Başlık en az iki kelime içermeli
        return count(preg_split('/\s+/u', trim($title), -1, PREG_SPLIT_NO_EMPTY) ?: []) >= 2;
    }

    /** "Haber başlığı | Kurum Adı" kalıbından kurum adını atar */
    private function stripSiteName(string $title, BotSource $source): string
    {
        foreach ([' | ', ' - ', ' :: ', ' — '] as $ayrac) {
            if (str_contains($title, $ayrac)) {
                $parcalar = explode($ayrac, $title);
                usort($parcalar, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

                return trim($parcalar[0]);
            }
        }

        return $title;
    }

    /** Liste kartlarındaki tarih önekini başlıktan ayıklar */
    private function cleanTitle(string $title): string
    {
        $months = 'Ocak|Şubat|Mart|Nisan|Mayıs|Haziran|Temmuz|Ağustos|Eylül|Ekim|Kasım|Aralık';
        $title = preg_replace('/^\s*\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4}\s*/u', '', $title) ?? $title;
        $title = preg_replace('/^\s*\d{1,2}\s+(' . $months . ')\s+\d{4}\s*/iu', '', $title) ?? $title;

        return trim($title);
    }

    /**
     * Kurum sayfaları gövdenin başında başlığı tekrarlar (çoğu zaman büyük harfle).
     * Aynı metnin iki kez geçmesi hem kelime sayımını hem yapay zekâ girdisini bozuyor.
     */
    private function stripLeadingTitle(string $content, string $title): string
    {
        if ($content === '' || $title === '') {
            return $content;
        }

        $titleWords = preg_split('/\s+/u', $this->fold($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($titleWords) < 3) {
            return $content;
        }

        $contentWords = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($contentWords) <= count($titleWords)) {
            return $content;
        }

        foreach ($titleWords as $i => $word) {
            if ($this->fold($contentWords[$i]) !== $word) {
                return $content;
            }
        }

        return $this->stripLeadingDate(trim(implode(' ', array_slice($contentWords, count($titleWords)))));
    }

    /** Başlık ayıklandıktan sonra gövdenin başında kalan yayın tarihini atar */
    private function stripLeadingDate(string $text): string
    {
        $months = 'Ocak|Şubat|Mart|Nisan|Mayıs|Haziran|Temmuz|Ağustos|Eylül|Ekim|Kasım|Aralık';
        $text = preg_replace('/^\s*\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4}\s*/u', '', $text) ?? $text;
        $text = preg_replace('/^\s*\d{1,2}\s+(' . $months . ')\s+\d{4}\s*/iu', '', $text) ?? $text;

        return trim($text);
    }

    /** Karşılaştırma için küçük harfe indirir ve noktalamayı atar */
    private function fold(string $s): string
    {
        $s = mb_strtolower(strtr($s, ['İ' => 'i', 'I' => 'ı']), 'UTF-8');

        return trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $s) ?? $s);
    }

    /** Kurum sitelerinde gövde sonuna eklenen liste ve galeri artıklarını atar */
    private function cleanContent(string $text): string
    {
        $cutPatterns = [
            '/DİĞER FOTOĞRAFLAR.*$/su',
            '/Diğer Haberler.*$/su',
            '/Güncel Haberler.*$/su',
            '/İLGİLİ HABER.*$/su',
            '/Tüm haberler için tıklayınız.*$/su',
            '/Bu haberi paylaş.*$/su',
            '/ETİKETLER.*$/su',
            '/Yorum yaz.*$/su',
        ];
        foreach ($cutPatterns as $p) {
            $text = preg_replace($p, '', $text) ?? $text;
        }

        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    // ── Kaydetme ve filtreler ───────────────────────────────────────────────

    private function alreadyInPool(string $hash): bool
    {
        return BotPoolItem::where('content_hash', $hash)->exists();
    }

    /** @var array<string,int> aynı tarama turunda görülen gövde imzaları */
    private array $contentSignatures = [];

    private function store(array $item, array &$stats): bool
    {
        $bump = function (string $key) use (&$stats) {
            $stats['skipped'][$key] = ($stats['skipped'][$key] ?? 0) + 1;
        };

        // Aynı kaynaktan gelen her habere aynı gövde çekiliyorsa selektör yanlış
        // bloğu gösteriyordur; bu kayıtlar havuza girmemeli.
        $imza = md5(mb_substr($item['content'], 0, 600));
        $this->contentSignatures[$imza] = ($this->contentSignatures[$imza] ?? 0) + 1;
        if ($this->contentSignatures[$imza] > 1) {
            $bump('ayni_govde');

            return false;
        }

        if ($item['word_count'] < $this->minWords) {
            $bump('kisa');

            return false;
        }

        if ($item['source_published_at'] !== null && $this->maxAgeDays > 0) {
            if (strtotime($item['source_published_at']) < strtotime("-{$this->maxAgeDays} days")) {
                $bump('eski');

                return false;
            }
        }

        // Kaynak tarihi bulunamadıysa metindeki tam tarihlere bakılır.
        //
        // Kurum siteleri haber listelerinde yıllar önceki duyuruları tutuyor ve
        // çoğunda okunabilir bir yayın tarihi yok (havuzun %78'i tarihsizdi).
        // Tarih olmayınca yaş filtresi çalışmıyor, 2017 tarihli bir duyuru bile
        // yayına giriyordu. Metinde yalnızca geçmiş yıllara ait tam tarih varsa
        // içerik eskidir. Yıl tek başına geçtiğinde elenmez: güncel bir haber
        // geçmiş bir yıla atıf yapabilir.
        if ($item['source_published_at'] === null && $this->maxAgeDays > 0) {
            $yillar = $this->metindekiYillar($item['title'] . ' ' . strip_tags((string) $item['content']));
            if ($yillar !== [] && max($yillar) < (int) date('Y')) {
                $bump('eski');

                return false;
            }
        }

        if (($hit = $this->matchesFilter($item['title'], $item['content'])) !== null) {
            $bump('filtre');
            BotKeywordFilter::where('id', $hit)->increment('hit_count');

            return false;
        }

        if ($this->findSimilarTitle($item['title']) !== null) {
            $bump('benzer');

            return false;
        }

        try {
            BotPoolItem::create($item);
        } catch (Throwable) {
            $bump('mukerrer');

            return false;
        }

        $this->recentTitles[] = [
            'title' => $item['title'],
            'normalized' => $this->normalizeTitle($item['title']),
        ];

        return true;
    }

    /** @return int|null eşleşen filtrenin id'si */
    /**
     * Metindeki tam tarihlerin (gün + ay + yıl) yıllarını döndürür.
     *
     * Yalnızca gün ve ay ile birlikte yazılmış tarihler sayılır; "2024 yılında"
     * gibi tek başına yıl geçişleri güncel haberlerde de olabildiği için alınmaz.
     *
     * @return array<int,int>
     */
    private function metindekiYillar(string $metin): array
    {
        $yillar = [];

        // 03.02.2017, 3/2/2017
        if (preg_match_all('/\b\d{1,2}[.\/]\d{1,2}[.\/](20\d{2})\b/u', $metin, $m)) {
            $yillar = array_merge($yillar, $m[1]);
        }

        // 3 Şubat 2017
        $aylar = 'ocak|şubat|mart|nisan|mayıs|haziran|temmuz|ağustos|eylül|ekim|kasım|aralık';
        if (preg_match_all('/\b\d{1,2}\s+(' . $aylar . ')\s+(20\d{2})\b/iu', $metin, $m)) {
            $yillar = array_merge($yillar, $m[2]);
        }

        return array_map('intval', $yillar);
    }

    private function matchesFilter(string $title, string $content): ?int
    {
        if ($this->filters === null) {
            $this->filters = BotKeywordFilter::where('is_active', true)->get()->all();
        }

        $lowerTitle = mb_strtolower($title, 'UTF-8');
        $lowerContent = mb_strtolower($content, 'UTF-8');

        $requiredHit = null;
        $hasRequired = false;

        foreach ($this->filters as $filter) {
            $needle = mb_strtolower($filter->keyword, 'UTF-8');
            $haystack = match ($filter->scope) {
                'title' => $lowerTitle,
                'content' => $lowerContent,
                default => $lowerTitle . ' ' . $lowerContent,
            };
            $found = str_contains($haystack, $needle);

            if ($filter->mode === 'block' && $found) {
                return $filter->id;
            }
            if ($filter->mode === 'require') {
                $hasRequired = true;
                if ($found) $requiredHit = true;
            }
        }

        // Zorunlu kelime tanımlıysa en az biri geçmeli
        return ($hasRequired && ! $requiredHit) ? 0 : null;
    }

    private function normalizeTitle(string $title): string
    {
        $t = mb_strtolower(trim($title), 'UTF-8');
        $t = preg_replace('/[^\p{L}\p{N}\s]/u', '', $t) ?? $t;

        return trim(preg_replace('/\s+/', ' ', $t) ?? $t);
    }

    /** Son 48 saatteki başlıklarla benzerlik kontrolü — aynı haber farklı kurumdan gelebilir */
    private function findSimilarTitle(string $title): ?string
    {
        if ($title === '') return null;

        if ($this->recentTitles === null) {
            $this->recentTitles = DB::table('bot_pool')
                ->where('created_at', '>=', now()->subHours(48))
                ->pluck('title')
                ->map(fn ($t) => ['title' => $t, 'normalized' => $this->normalizeTitle($t)])
                ->all();
        }

        $new = $this->normalizeTitle($title);
        $newLen = mb_strlen($new);
        if ($newLen < 10) return null;

        foreach ($this->recentTitles as $existing) {
            $len = mb_strlen($existing['normalized']);
            if (abs($newLen - $len) > $newLen * 0.5) continue;   // hızlı ön eleme

            similar_text($new, $existing['normalized'], $percent);
            if ($percent >= $this->similarityThreshold) {
                return $existing['title'];
            }
        }

        return null;
    }
}
