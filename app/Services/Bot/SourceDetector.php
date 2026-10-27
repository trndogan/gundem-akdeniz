<?php

namespace App\Services\Bot;

use DOMDocument;
use DOMElement;
use DOMXPath;
use SplObjectStorage;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Resmî kurum sitelerinde haber liste linklerini ve detay gövdesini otomatik bulur.
 *
 * Her kurumun CMS'i farklı olduğu için selektörleri elle yazmak sürdürülebilir değil.
 * Bu sınıf sayfayı analiz edip selektörleri kendisi üretir:
 *   - Liste sayfası: linkleri DOM ata zincirine göre gruplar, haber kartı grubunu seçer
 *   - Detay sayfası: Readability benzeri paragraf skorlamasıyla gövdeyi bulur
 *
 * Laravel konteynerine bağımlı değildir; tools/bot-detect.php aynı sınıfı CLI'dan kullanır.
 */
class SourceDetector
{
    public const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    private const MIN_ANCHOR_LEN = 14;   // Anchor metni bundan kısaysa haber başlığı sayılmaz
    private const MIN_GROUP_SIZE = 3;    // Bir grup bundan az linkse haber listesi değildir
    private const MIN_BODY_WORDS = 45;   // Detay gövdesi bundan kısaysa kaynak reddedilir
    // (çalışma anında havuza girmek için ayrıca 80 kelime şartı var; burada kaynağı tümden elemeyelim)

    private const LIST_BLACKLIST = 'nav|menu|navbar|header|footer|dropdown|breadcrumb|sidebar|widget|social|share|pagination|cookie|modal|offcanvas|login|search';
    private const NEWS_HINT = 'haber|news|duyuru|announce|basin|post|card|item|list|blog|aktuel|etkinlik|manset';
    private const BODY_NEGATIVE = 'menu|nav|header|footer|sidebar|widget|breadcrumb|share|social|comment|yorum|related|ilgili|banner|reklam|advert|cookie|popup|modal|slider|carousel|pagination|tag|etiket|meta|author|date|toolbar|search';
    private const BODY_POSITIVE = 'icerik|content|article|body|detay|detail|entry|post|text|haber|news|yazi|aciklama|description|main';

    public function __construct(private int $timeout = 25) {}

    // ── Genel API ───────────────────────────────────────────────────────────

    /**
     * Bir haber liste sayfasını doğrular ve selektörlerini üretir.
     *
     * @return array{ok:bool,reason:?string,link_selector:?string,content_selector:?string,
     *               image_selector:?string,image_url:?string,link_count:int,avg_words:int,
     *               has_date:bool,site_title:?string,final_url:?string,samples:array}
     */
    public function verify(string $listUrl): array
    {
        $out = [
            'ok' => false, 'reason' => null,
            'link_selector' => null, 'content_selector' => null, 'image_selector' => null,
            'image_url' => null, 'link_count' => 0, 'avg_words' => 0, 'has_date' => false,
            'site_title' => null, 'final_url' => null, 'auto_extract' => false, 'samples' => [],
        ];

        $list = $this->fetch($listUrl);
        if ($list === null) {
            $out['reason'] = 'Liste sayfası açılamadı';

            return $out;
        }

        $out['final_url'] = $list['url'];
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $list['html'], $tm)) {
            $out['site_title'] = mb_substr($this->normalize(html_entity_decode($tm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 160);
        }

        $groups = $this->linkGroups($list['html'], $list['url'], 3);
        if ($groups === []) {
            $out['reason'] = 'Haber linki grubu bulunamadı (sayfa JS ile yükleniyor olabilir)';

            return $out;
        }

        // Aday gruplar arasında nihai seçim: her birinden bir detay sayfası örnekle,
        // gerçek haber grubu kurumsal menü grubundan daha uzun gövde ve tarih üretir.
        $winner = null;
        foreach ($groups as $group) {
            foreach (array_slice($group['links'], 0, 2) as $link) {
                $detail = $this->fetch($link['href']);
                if ($detail === null) continue;

                $body = $this->extractBody($detail['html']);
                if ($body['content_selector'] === null) continue;

                $quality = $body['words'] + ($body['published_at'] ? 150 : 0) + (int) ($group['score'] / 3);
                if ($winner === null || $quality > $winner['quality']) {
                    // HTML saklanır: kalıp metin elenince gövde aynı sayfada yeniden aranır
                    $winner = ['group' => $group, 'body' => $body, 'link' => $link,
                        'quality' => $quality, 'html' => $detail['html']];
                }
                break;
            }
        }

        if ($winner === null) {
            $out['reason'] = 'Detay sayfasında haber gövdesi bulunamadı';

            return $out;
        }

        $group = $winner['group'];
        $body = $winner['body'];

        $out['link_selector'] = $group['selector'];
        $out['link_count'] = count($group['links']);
        $out['content_selector'] = $body['content_selector'];
        $out['image_selector'] = $body['image_selector'];
        $out['image_url'] = $body['image_url'];
        $out['has_date'] = $body['published_at'] !== null;
        $out['samples'][] = [
            'url' => $winner['link']['href'],
            'title' => $winner['link']['text'],
            'words' => $body['words'],
            'published_at' => $body['published_at'],
            'excerpt' => mb_substr($body['text'], 0, 200),
        ];

        // Kararlılık testi: aynı selektör ikinci haberde de çalışmalı.
        // Çalışmazsa kaynağı elemeyiz — selektörü boşaltıp çalışma anında otomatik
        // tespite bırakırız, çünkü gövde bulunabiliyor, sadece şablon sayfadan sayfaya değişiyor.
        //
        // Ayrıca kalıp metin denetimi: seçici iki farklı haberde AYNI metni veriyorsa
        // haber gövdesini değil menü/altbilgi gibi sabit bir bloğu gösteriyordur.
        // Kelime sayısına bakmak bunu yakalamıyordu; erişilebilirlik menüsü de 108
        // kelime döndürüp testi geçiyor, sonra her habere aynı gövde yazılıyordu.
        $wordCounts = [$body['words']];
        foreach (array_slice($group['links'], 1, 3) as $link) {
            if ($link['href'] === $winner['link']['href']) continue;
            $detail = $this->fetch($link['href']);
            if ($detail === null) continue;

            $second = $this->extractWith($detail['html'], $detail['url'], (string) $body['content_selector']);
            $mode = 'selektör';

            // Kalıp metin: aynı seçici, aynı metin. Bloğu dışlayıp yeniden ara.
            $haric = [];
            $tur = 0;
            while ($body['content_selector'] !== null
                && $second['words'] > 0
                && $this->sameText($second['text'] ?? '', $body['text'] ?? '')
                && $tur < 3) {
                $haric[] = (string) $body['content_selector'];
                $tur++;

                $body = $this->extractBody($winner['html'], $haric);
                if ($body['content_selector'] === null || $body['words'] < 40) {
                    $body['content_selector'] = null;
                    break;
                }

                $out['content_selector'] = $body['content_selector'];
                $out['image_selector'] = $body['image_selector'];
                $second = $this->extractWith($detail['html'], $detail['url'], (string) $body['content_selector']);
                $mode = 'selektör (kalıp elendi)';
            }

            if ($body['content_selector'] === null) {
                $out['content_selector'] = null;
                $out['reason'] = 'Her haberde aynı gövde çıkıyor, haber metni ayrıştırılamadı';

                return $out;
            }

            $wordCounts[0] = $body['words'];

            if ($second['words'] < 40) {
                $auto = $this->extractBody($detail['html'], $haric);
                if ($auto['words'] >= 40) {
                    $second = ['words' => $auto['words'], 'text' => $auto['text']];
                    $mode = 'otomatik';
                    $out['content_selector'] = null;   // sayfadan sayfaya değişiyor → otomatik mod
                    $out['auto_extract'] = true;
                }
            }

            $out['samples'][] = [
                'url' => $link['href'],
                'title' => $link['text'],
                'words' => $second['words'],
                'mode' => $mode,
                'excerpt' => mb_substr($second['text'], 0, 200),
            ];
            if ($second['words'] > 0) $wordCounts[] = $second['words'];
            break;
        }

        $out['avg_words'] = (int) round(array_sum($wordCounts) / count($wordCounts));
        if ($out['avg_words'] < self::MIN_BODY_WORDS) {
            $out['reason'] = "Haber gövdesi çok kısa ({$out['avg_words']} kelime)";

            return $out;
        }

        $out['ok'] = true;

        return $out;
    }

    /**
     * Kurumun alan adından haber liste sayfasını bulur.
     *
     * @return array{url:string,score:int,links:int}|null
     */
    public function discoverNewsUrl(string $siteUrl): ?array
    {
        $siteUrl = rtrim($siteUrl, '/');

        // Ana sayfa başka alan adına yönlenebilir (osmaniye.bel.tr → osmaniye-bld.gov.tr);
        // host karşılaştırmasında nihai adresi baz al.
        $home = $this->fetch($siteUrl);
        $base = $home['url'] ?? $siteUrl;
        $baseRoot = rtrim(($this->part($base, PHP_URL_SCHEME) ?: 'https') . '://' . $this->part($base, PHP_URL_HOST), '/');

        $ranked = [];
        if ($home !== null) {
            [, $xpath] = $this->dom($home['html'], 'none');
            foreach ($xpath->query('//a[@href]') as $a) {
                /** @var DOMElement $a */
                $href = $this->absolutize($a->getAttribute('href'), $base);
                if ($href === '' || ! $this->sameHost($href, $base)) continue;

                $path = strtolower($this->part($href, PHP_URL_PATH));
                $text = mb_strtolower($this->normalize($a->textContent ?? ''), 'UTF-8');
                if (! preg_match('/haber|duyuru|news|bas[ıi]n/u', $path . ' ' . $text)) continue;

                // Liste sayfası kısa yollu olur; uzun slug'lar haber detayıdır
                $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
                $last = end($segments) ?: '';
                $isListing = (bool) preg_match('/haberler|duyurular|arsiv|ar[şs]iv|liste/iu', $last);
                if (! $isListing && (mb_strlen($last) > 20 || substr_count($last, '-') >= 3)) continue;

                $score = 0;
                if (preg_match('/^(t[üu]m\s+)?(haberler|haber|duyurular|bas[ıi]n a[çc][ıi]klamalar[ıi]|haber ar[şs]ivi)$/u', $text)) $score += 100;
                if (str_contains($path, 'haber')) $score += 50;
                if (preg_match('/duyuru|bas[ıi]n/u', $path)) $score += 30;
                $score -= count($segments) * 8;

                $clean = strtok($href, '#');
                $ranked[$clean] = max($ranked[$clean] ?? 0, $score);
            }
        }
        arsort($ranked);

        $candidates = array_keys($ranked);
        foreach (['/haberler', '/haber', '/tr/haberler', '/duyurular', '/haberler.html', '/haber-arsivi',
            '/kategori/haberler', '/www/haberler/kategori/1', '/Haber', '/tr/haber'] as $p) {
            $candidates[] = $baseRoot . $p;
        }
        // Son çare: kurum ana sayfası. Pek çok kamu sitesinde haber listesi ana sayfadadır.
        $candidates[] = $baseRoot;

        $best = null;
        foreach (array_slice(array_values(array_unique($candidates)), 0, 6) as $candidate) {
            $page = $this->fetch($candidate, 18);
            if ($page === null) continue;
            $groups = $this->linkGroups($page['html'], $page['url'], 1);
            if ($groups === []) continue;

            $score = $groups[0]['score'];
            if (rtrim($page['url'], '/') === $baseRoot) $score -= 30; // ana sayfa son çare
            if ($best === null || $score > $best['score']) {
                $best = ['url' => $page['url'], 'score' => $score, 'links' => count($groups[0]['links'])];
            }
            if ($best['score'] > 220) break; // yeterince iyi, fazladan istek atma
        }

        return $best;
    }

    /**
     * Sitenin RSS/Atom beslemesini bulur. Bazı kurumlar (örn. MEB il müdürlükleri)
     * haber listesini JS ile çiziyor ama besleme yayınlıyor; besleme daha sağlam kaynaktır.
     */
    public function discoverFeed(string $siteUrl): ?string
    {
        $home = $this->fetch($siteUrl);
        if ($home === null) return null;

        // <link rel="alternate" type="application/rss+xml" href="...">
        if (preg_match_all('#<link[^>]+application/(?:rss|atom)\+xml[^>]*>#i', $home['html'], $m)) {
            foreach ($m[0] as $tag) {
                if (preg_match('/href=["\']([^"\']+)["\']/i', $tag, $h)) {
                    $url = $this->absolutize($h[1], $home['url']);
                    if ($url !== '' && $this->looksLikeFeed($url)) return $url;
                }
            }
        }

        // Sayfadaki .xml / rss bağlantıları
        if (preg_match_all('#href=["\']([^"\']*(?:rss|feed)[^"\']*)["\']#i', $home['html'], $m)) {
            foreach ($m[1] as $href) {
                $url = $this->absolutize($href, $home['url']);
                if ($url !== '' && $this->sameHost($url, $home['url']) && $this->looksLikeFeed($url)) return $url;
            }
        }

        $root = rtrim(($this->part($home['url'], PHP_URL_SCHEME) ?: 'https') . '://' . $this->part($home['url'], PHP_URL_HOST), '/');
        foreach (['/rss', '/rss.xml', '/feed', '/feed.xml', '/rss/haberler'] as $p) {
            if ($this->looksLikeFeed($root . $p)) return $root . $p;
        }

        return null;
    }

    private function looksLikeFeed(string $url): bool
    {
        $res = $this->fetch($url, 15);

        return $res !== null && preg_match('/<(rss|feed|rdf:RDF)\b/i', substr($res['html'], 0, 1500)) === 1;
    }

    /**
     * RSS beslemesini doğrular ve öğe detay sayfası için gövde selektörü üretir.
     *
     * @return array aynı şema: verify() ile birebir uyumlu
     */
    public function verifyFeed(string $feedUrl): array
    {
        $out = [
            'ok' => false, 'reason' => null, 'type' => 'rss',
            'link_selector' => null, 'content_selector' => null, 'image_selector' => null,
            'image_url' => null, 'link_count' => 0, 'avg_words' => 0, 'has_date' => false,
            'site_title' => null, 'final_url' => $feedUrl, 'samples' => [],
        ];

        $res = $this->fetch($feedUrl);
        if ($res === null) {
            $out['reason'] = 'Besleme açılamadı';

            return $out;
        }

        $xml = @simplexml_load_string($res['html'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $out['reason'] = 'Besleme çözümlenemedi';

            return $out;
        }

        $items = $xml->channel->item ?? $xml->entry ?? [];
        $links = [];
        foreach ($items as $item) {
            $title = $this->normalize((string) ($item->title ?? ''));
            $link = (string) ($item->link['href'] ?? $item->link ?? '');
            if ($title !== '' && $link !== '') $links[] = ['title' => $title, 'url' => $link];
        }

        $out['site_title'] = $this->normalize((string) ($xml->channel->title ?? $xml->title ?? ''));
        $out['link_count'] = count($links);
        if ($links === []) {
            $out['reason'] = 'Beslemede öğe yok';

            return $out;
        }

        $words = [];
        foreach (array_slice($links, 0, 2) as $l) {
            $detail = $this->fetch($l['url']);
            if ($detail === null) continue;

            $body = $this->extractBody($detail['html']);
            if ($body['content_selector'] === null) continue;

            $out['content_selector'] ??= $body['content_selector'];
            $out['image_selector'] ??= $body['image_selector'];
            $out['image_url'] ??= $body['image_url'];
            $out['has_date'] = $out['has_date'] || $body['published_at'] !== null;
            $words[] = $body['words'];
            $out['samples'][] = [
                'url' => $l['url'],
                'title' => $l['title'],
                'words' => $body['words'],
                'published_at' => $body['published_at'],
                'excerpt' => mb_substr($body['text'], 0, 200),
            ];
        }

        if ($words === []) {
            $out['reason'] = 'Besleme öğelerinde gövde bulunamadı';

            return $out;
        }

        $out['avg_words'] = (int) round(array_sum($words) / count($words));
        if ($out['avg_words'] < self::MIN_BODY_WORDS) {
            $out['reason'] = "Haber gövdesi çok kısa ({$out['avg_words']} kelime)";

            return $out;
        }

        $out['ok'] = true;

        return $out;
    }

    // ── HTTP ────────────────────────────────────────────────────────────────

    /** @return array{html:string,url:string,code:int}|null */
    public function fetch(string $url, ?int $timeout = null): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout ?? $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => self::UA,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        if ($body === false || $code >= 400 || $body === '') {
            return null;
        }

        return ['html' => $this->toUtf8($body), 'url' => $final ?: $url, 'code' => $code];
    }

    /**
     * Birden çok adresi eşzamanlı çeker.
     *
     * Seri çekimde kaynak başına ~5 saniye harcanıyor ve 228 kaynaklık tam tur 19
     * dakika sürüyordu. Sunucuda proc_open kapalı olduğu için ayrı işlem açılamaz,
     * bu yüzden eşzamanlılık curl_multi ile tek süreç içinde sağlanır.
     *
     * @param  array<int|string,string>  $urls  anahtar korunur, değer adres
     * @return array<int|string,array{html:string,url:string,code:int}>  açılamayanlar dizide yer almaz
     */
    public function fetchMany(array $urls, int $concurrency = 20, ?int $timeout = null): array
    {
        if ($urls === []) {
            return [];
        }

        $concurrency = max(1, min(40, $concurrency));
        $multi = curl_multi_init();
        $kuyruk = $urls;
        $calisan = [];      // curl handle id => ['key' => ..., 'handle' => ...]
        $sonuc = [];

        $baslat = function () use (&$kuyruk, &$calisan, $multi, $timeout) {
            $key = array_key_first($kuyruk);
            if ($key === null) return;

            $url = $kuyruk[$key];
            unset($kuyruk[$key]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => $timeout ?? $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => self::UA,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8',
                ],
            ]);

            curl_multi_add_handle($multi, $ch);
            $calisan[(int) $ch] = ['key' => $key, 'handle' => $ch];
        };

        for ($i = 0; $i < $concurrency; $i++) {
            $baslat();
        }

        do {
            curl_multi_exec($multi, $aktif);
            curl_multi_select($multi, 0.5);

            while (($bilgi = curl_multi_info_read($multi)) !== false) {
                $ch = $bilgi['handle'];
                $id = (int) $ch;
                $key = $calisan[$id]['key'] ?? null;

                if ($key !== null) {
                    $govde = curl_multi_getcontent($ch);
                    $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $son = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                    if ($govde !== null && $govde !== '' && $kod < 400) {
                        $sonuc[$key] = ['html' => $this->toUtf8($govde), 'url' => $son ?: $urls[$key], 'code' => $kod];
                    }
                }

                curl_multi_remove_handle($multi, $ch);
                unset($calisan[$id]);
                $baslat();
            }
        } while ($aktif || $calisan !== []);

        curl_multi_close($multi);

        return $sonuc;
    }

    /** Sunucu windows-1254 / iso-8859-9 gönderirse Türkçe karakterleri kurtar */
    private function toUtf8(string $html): string
    {
        $head = substr($html, 0, 2000);
        if (preg_match('/charset=["\']?\s*utf-?8/i', $head)) {
            return $html;
        }
        if (preg_match('/charset=["\']?\s*(windows-1254|iso-8859-9)/i', $head, $m)) {
            return mb_convert_encoding($html, 'UTF-8', $m[1]);
        }

        return mb_check_encoding($html, 'UTF-8') ? $html : mb_convert_encoding($html, 'UTF-8', 'Windows-1254');
    }

    // ── DOM hazırlığı ───────────────────────────────────────────────────────

    /** Script/style gövdelerini DOM'a girmeden temizle — metin sayımını bozuyorlar */
    public static function stripNoise(string $html): string
    {
        foreach (['script', 'style', 'noscript', 'template'] as $tag) {
            $html = preg_replace("#<{$tag}\b[^>]*>.*?</{$tag}>#is", ' ', $html) ?? $html;
        }

        return preg_replace('#<!--.*?-->#s', ' ', $html) ?? $html;
    }

    /**
     * @param  string  $mode  none | list | body
     * @return array{0:DOMDocument,1:DOMXPath}
     */
    private function dom(string $html, string $mode = 'body'): array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . self::stripNoise($html), LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        if ($mode !== 'none') $this->pruneChrome($xpath, $mode === 'body');

        return [$doc, $xpath];
    }

    /**
     * Menü, footer, çerez modali gibi sayfa kabuğunu siler.
     *
     * $withHeader yalnızca gövde analizinde açılır: kurum siteleri haber slider'ını
     * sıklıkla <header> içine koyuyor, liste analizinde header silinirse haberler kaybolur.
     */
    private function pruneChrome(DOMXPath $xpath, bool $withHeader = true): void
    {
        foreach ($xpath->query('//nav|//aside') as $n) {
            $n->parentNode?->removeChild($n);
        }
        // ASP.NET / SharePoint devlet siteleri tüm sayfayı tek <form runat="server"> içine
        // sarar; formu koşulsuz silmek sayfayı yok ediyordu. Yalnız küçük arama formlarını sil.
        foreach ($xpath->query('//form') as $n) {
            if (mb_strlen($this->normalize($n->textContent ?? '')) < 500) {
                $n->parentNode?->removeChild($n);
            }
        }
        foreach ($xpath->query($withHeader ? '//header|//footer' : '//footer') as $n) {
            $depth = 0;
            for ($p = $n; $p !== null; $p = $p->parentNode) $depth++;
            if ($depth <= 5) $n->parentNode?->removeChild($n);
        }

        $chrome = 'modal|dialog|popup|cookie|cerez|offcanvas|overlay|lightbox|newsletter|subscribe|share|social|breadcrumb|pagination';
        foreach ($xpath->query('//*[@class or @id or @role]') as $n) {
            /** @var DOMElement $n */
            if (! $n->parentNode) continue;
            $sig = str_replace(['-', '_'], ' ', strtolower($n->getAttribute('class') . ' ' . $n->getAttribute('id')));
            if (strtolower($n->getAttribute('role')) === 'dialog' || preg_match('/\b(' . $chrome . ')\b/', $sig)) {
                $n->parentNode->removeChild($n);
            }
        }
    }

    // ── Liste analizi ───────────────────────────────────────────────────────

    /**
     * Linkleri DOM ata zincirine göre gruplar; aynı karttan üretilenler tek grupta toplanır.
     *
     * @return array<int,array{selector:string,links:array,score:int,signature:string}>
     */
    public function linkGroups(string $html, string $listUrl, int $limit = 3): array
    {
        [, $xpath] = $this->dom($html, 'list');
        $groups = [];

        foreach ($xpath->query('//a[@href]') as $a) {
            /** @var DOMElement $a */
            $href = $this->absolutize($a->getAttribute('href'), $listUrl);
            if ($href === '' || ! $this->sameHost($href, $listUrl)) continue;
            if (rtrim(strtok($href, '#'), '/') === rtrim($listUrl, '/')) continue;

            $path = $this->part($href, PHP_URL_PATH);
            if ($path === '' || $path === '/' || $this->isJunkPath($path)) continue;

            $text = $this->normalize($a->textContent ?? '');
            if ($text === '') {
                $img = $xpath->query('.//img[@alt]', $a);
                if ($img->length > 0) $text = $this->normalize($img->item(0)->getAttribute('alt'));
            }

            $chain = [$this->nodeToken($a)];
            $cur = $a->parentNode;
            for ($i = 0; $i < 4 && $cur instanceof DOMElement; $i++) {
                array_unshift($chain, $this->nodeToken($cur));
                $cur = $cur->parentNode;
            }

            $groups[implode(' > ', $chain)][] = [
                'href' => strtok($href, '#'),
                'text' => $text,
                'len' => mb_strlen($text),
                'chain' => $chain,
            ];
        }

        $scored = [];
        foreach ($groups as $signature => $links) {
            // Aynı haber hem görsel hem başlık linkiyle geçebilir → tekilleştir
            $uniq = [];
            foreach ($links as $l) {
                if (! isset($uniq[$l['href']]) || $l['len'] > $uniq[$l['href']]['len']) {
                    $uniq[$l['href']] = $l;
                }
            }
            $titled = array_values(array_filter($uniq, fn ($l) => $l['len'] >= self::MIN_ANCHOR_LEN));
            if (count($titled) < self::MIN_GROUP_SIZE) continue;

            $flat = str_replace(['.', '#', '-', '_', ' > '], ' ', mb_strtolower($signature));
            if (preg_match('/\b(' . self::LIST_BLACKLIST . ')\b/', $flat)) continue;

            $avgLen = array_sum(array_column($titled, 'len')) / count($titled);
            $score = count($titled) * 5 + (int) min($avgLen * 1.5, 120);
            if (preg_match('/(' . self::NEWS_HINT . ')/', mb_strtolower($signature))) $score += 60;

            $pathHits = $dateHits = 0;
            foreach ($titled as $l) {
                if (preg_match('/(haber|duyuru|news|basin|aciklama|detay|etkinlik)/i', $l['href'])) $pathHits++;
                if (preg_match('/\b\d{1,2}[.\/-]\d{1,2}[.\/-]\d{4}\b|\b\d{1,2}\s+(Ocak|Şubat|Mart|Nisan|Mayıs|Haziran|Temmuz|Ağustos|Eylül|Ekim|Kasım|Aralık)\b/iu', $l['text'])) $dateHits++;
            }
            $score += (int) (40 * $pathHits / count($titled)) + (int) (50 * $dateHits / count($titled));
            if ($avgLen < 20) $score -= 40;

            $group = ['signature' => $signature, 'links' => $titled, 'score' => $score];
            $group['selector'] = $this->buildLinkSelector($group);

            // Üretilen selektör grubun en az yarısını yakalamalı
            try {
                $hit = (new Crawler($html, $listUrl))->filter($group['selector'])->count();
                if ($hit < max(2, (int) (count($titled) * 0.5))) {
                    $group['selector'] = 'a[href*="' . $this->dominantSegment($titled) . '"]';
                }
            } catch (\Throwable) {
                $group['selector'] = 'a[href*="' . $this->dominantSegment($titled) . '"]';
            }

            foreach ($group['links'] as &$l) unset($l['chain']);
            unset($l);

            $scored[] = $group;
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    private function isJunkPath(string $path): bool
    {
        $junk = 'iletisim|contact|kvkk|gizlilik|sitemap|site-haritasi|giris|login|arama|search|rss|feed|ihale|personel|nobetci-eczane|hava-durumu|e-belediye|ebelediye|vefat|meclis-karar|ozgecmis|hakkimizda|privacy';

        return (bool) preg_match('/(' . $junk . ')/i', $path);
    }

    private function buildLinkSelector(array $group): string
    {
        $chain = $group['links'][0]['chain'];
        $anchor = end($chain);

        if (str_contains($anchor, '.')) {
            return $anchor;
        }
        for ($i = count($chain) - 2; $i >= 0; $i--) {
            if (str_contains($chain[$i], '.') || str_contains($chain[$i], '#')) {
                return $chain[$i] . ' a';
            }
        }

        return 'a[href*="' . $this->dominantSegment($group['links']) . '"]';
    }

    private function dominantSegment(array $links): string
    {
        $counts = [];
        foreach ($links as $l) {
            foreach (array_filter(explode('/', $this->part($l['href'], PHP_URL_PATH)), fn ($s) => $s !== '') as $s) {
                if (mb_strlen($s) > 16 || is_numeric($s)) continue;
                $counts[$s] = ($counts[$s] ?? 0) + 1;
            }
        }
        arsort($counts);
        foreach ($counts as $seg => $n) {
            if ($n >= count($links) * 0.6 && preg_match('/haber|duyuru|news|basin|detay|aciklama/i', $seg)) {
                return '/' . $seg;
            }
        }

        return array_key_first($counts) !== null ? '/' . array_key_first($counts) : '/';
    }

    // ── Gövde analizi ───────────────────────────────────────────────────────

    /**
     * Readability benzeri skorlamayla haber gövdesini bulur.
     *
     * @return array{content_selector:?string,image_selector:?string,image_url:?string,
     *               words:int,text:string,published_at:?string}
     */
    /**
     * @param  array<int,string>  $haric  Bu CSS seçicilere uyan bloklar puanlamaya girmez.
     *                                    Kalıp metin (menü, altbilgi) elendiğinde bir
     *                                    sonraki en iyi aday gövde olarak seçilir.
     */
    public function extractBody(string $html, array $haric = []): array
    {
        [, $xpath] = $this->dom($html);

        foreach ($haric as $secici) {
            foreach ($this->queryCss($xpath, $secici) as $n) {
                $n->parentNode?->removeChild($n);
            }
        }

        foreach ($xpath->query('//iframe|//svg') as $n) {
            $n->parentNode?->removeChild($n);
        }

        $publishedAt = $this->detectPublishedAt($xpath);
        $scores = new SplObjectStorage();

        // Paragraf skorlarını ebeveyn ve büyük ebeveyne dağıt
        foreach ($xpath->query('//p|//li[string-length(normalize-space(.)) > 120]') as $p) {
            $text = $this->normalize($p->textContent ?? '');
            $len = mb_strlen($text);
            if ($len < 30) continue;

            $base = 1 + substr_count($text, ',') + substr_count($text, '.') * 0.5 + min($len / 100, 4);
            $parent = $p->parentNode;
            if ($parent instanceof DOMElement) {
                $scores[$parent] = ($scores[$parent] ?? 0) + $base;
                $grand = $parent->parentNode;
                if ($grand instanceof DOMElement) {
                    $scores[$grand] = ($scores[$grand] ?? 0) + $base / 2;
                }
            }
        }

        // Paragraf kullanmayan CMS'ler için düz metin blokları.
        //
        // Bu geçiş eskiden yalnızca hiç paragraf skoru yokken çalışıyordu. Devlet
        // sitelerinde haber gövdesi <div> içinde duruyor ama sayfadaki menüler <p>
        // ve <li> ürettiği için skor listesi boş kalmıyordu; sonuçta gövde hiç aday
        // olamıyor, menü kazanıyordu. Artık her zaman çalışır: aşağıdaki sınıf adı
        // puanı ve link yoğunluğu cezası gerçek gövdeyi menüden ayırır.
        foreach ($xpath->query('//div|//td|//article|//section') as $node) {
            $len = mb_strlen($this->normalize($node->textContent ?? ''));
            if ($len >= 300) $scores[$node] = ($scores[$node] ?? 0) + min($len / 100, 12);
        }

        $winner = null;
        $winnerScore = 0.0;
        foreach ($scores as $node) {
            /** @var DOMElement $node */
            $score = (float) $scores[$node];
            // Sınıf adı puanı ata zincirinden okunur. Yalnız düğümün kendi sınıfına
            // bakmak yetmiyordu: menülerin içinde sınıfsız kutular oluyor, ceza
            // uygulanmıyor ve menü haber gövdesi sanılıyordu.
            $idc = '';
            $ata = $node;
            for ($k = 0; $k < 4 && $ata instanceof DOMElement; $k++) {
                $idc .= ' ' . $ata->getAttribute('id') . ' ' . $ata->getAttribute('class');
                $ata = $ata->parentNode;
            }
            $idc = str_replace(['-', '_'], ' ', strtolower($idc));

            if (preg_match('/\b(' . self::BODY_NEGATIVE . ')\b/', $idc)) $score -= 30;
            if (preg_match('/\b(' . self::BODY_POSITIVE . ')\b/', $idc)) $score += 30;

            $text = $this->normalize($node->textContent ?? '');
            $len = max(1, mb_strlen($text));
            if ($len < 200) continue;

            $linkLen = 0;
            foreach ($xpath->query('.//a', $node) as $a) {
                $linkLen += mb_strlen($this->normalize($a->textContent ?? ''));
            }
            $score *= (1 - min(0.95, $linkLen / $len));

            if ($score > $winnerScore) {
                $winnerScore = $score;
                $winner = $node;
            }
        }

        if ($winner === null) {
            return ['content_selector' => null, 'image_selector' => null, 'image_url' => null,
                'words' => 0, 'text' => '', 'published_at' => $publishedAt];
        }

        // Tek çocuklu sarmalayıcıları içeri doğru kırp
        while (true) {
            $children = [];
            foreach ($winner->childNodes as $c) {
                if ($c instanceof DOMElement) $children[] = $c;
            }
            if (count($children) !== 1) break;
            $only = $children[0];
            if (in_array(strtolower($only->tagName), ['p', 'a', 'span', 'img'], true)) break;
            if (mb_strlen($this->normalize($only->textContent ?? '')) < mb_strlen($this->normalize($winner->textContent ?? '')) * 0.95) break;
            $winner = $only;
        }

        $selector = $this->buildNodeSelector($winner, $xpath);
        $image = $this->detectImage($winner, $xpath, $selector);
        $text = $this->normalize($winner->textContent ?? '');

        return [
            'content_selector' => $selector,
            'image_selector' => $image['selector'],
            'image_url' => $image['url'],
            'words' => $this->wordCount($text),
            'text' => mb_substr($text, 0, 400),
            'published_at' => $publishedAt ?? $this->dateFromText($text),
        ];
    }

    /** Hazır selektörle gövdeyi çıkarır — selektörün başka sayfada da çalıştığını doğrular */
    /**
     * İki gövde metni pratikte aynı mı.
     *
     * Kalıp bloklar sayfadan sayfaya birebir aynı gelmeyebilir (tarih, sayaç gibi
     * küçük farklar), o yüzden birebir eşitlik yerine yüksek örtüşme aranır.
     */
    private function sameText(string $a, string $b): bool
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        if ($a === '' || $b === '') return false;
        if ($a === $b) return true;

        $kisa = min(mb_strlen($a), mb_strlen($b));
        if ($kisa < 60) return false;

        similar_text(mb_substr($a, 0, 400), mb_substr($b, 0, 400), $yuzde);

        return $yuzde >= 92.0;
    }

    /**
     * CSS seçicisini XPath'e çevirip düğümleri döndürür; hatalı seçicide boş döner.
     *
     * @return array<int,\DOMElement>
     */
    private function queryCss(DOMXPath $xpath, string $secici): array
    {
        try {
            $ifade = (new \Symfony\Component\CssSelector\CssSelectorConverter())->toXPath($secici);
        } catch (\Throwable) {
            return [];
        }

        $bulunan = [];
        foreach ($xpath->query($ifade) ?: [] as $n) {
            if ($n instanceof DOMElement) $bulunan[] = $n;
        }

        return $bulunan;
    }

    public function extractWith(string $html, string $url, string $selector): array
    {
        try {
            $node = (new Crawler(self::stripNoise($html), $url))->filter($selector);
            if ($node->count() === 0) return ['words' => 0, 'text' => ''];
            $text = $this->normalize($node->first()->text(''));

            return ['words' => $this->wordCount($text), 'text' => $text];
        } catch (\Throwable) {
            return ['words' => 0, 'text' => ''];
        }
    }

    private function buildNodeSelector(DOMElement $node, DOMXPath $xpath): ?string
    {
        $tag = strtolower($node->tagName);

        $id = trim($node->getAttribute('id'));
        if ($id !== '' && ! preg_match('/\d{3,}|^(ctl|ember|react|vue)/i', $id)) {
            return '#' . $id;
        }

        foreach (preg_split('/\s+/', trim($node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $c) {
            if (! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $c)) continue;
            if (preg_match('/^(col|row|d|mb|mt|px|py|pt|pb|w|h|text|bg|border|container|wrapper|clearfix|active)([-_]|$)/i', $c)) continue;
            $hits = $xpath->query("//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')]");
            if ($hits !== false && $hits->length >= 1 && $hits->length <= 3) {
                return $tag . '.' . $c;
            }
        }

        $chain = [];
        $cur = $node;
        for ($i = 0; $i < 3 && $cur instanceof DOMElement; $i++) {
            $cid = trim($cur->getAttribute('id'));
            if ($cid !== '' && ! preg_match('/\d{3,}/', $cid)) {
                array_unshift($chain, '#' . $cid);

                return implode(' ', $chain);
            }
            array_unshift($chain, $this->nodeToken($cur));
            $cur = $cur->parentNode instanceof DOMElement ? $cur->parentNode : null;
        }

        return $chain !== [] ? implode(' ', $chain) : null;
    }

    /**
     * Görsel bulur: gövde içi → og:image → sayfadaki haber klasörü desenli görsel.
     *
     * @return array{selector:?string,url:?string}
     */
    public function detectImage(DOMElement $body, DOMXPath $xpath, ?string $bodySelector): array
    {
        foreach ($xpath->query('.//img', $body) as $img) {
            if (($src = $this->usableImageSrc($img)) !== null) {
                return ['selector' => $bodySelector ? $bodySelector . ' img' : 'img', 'url' => $src];
            }
        }

        $og = $xpath->query('//meta[@property="og:image"]/@content');
        if ($og !== false && $og->length > 0 && trim($og->item(0)->nodeValue ?? '') !== '') {
            return ['selector' => 'meta[property="og:image"]', 'url' => trim($og->item(0)->nodeValue)];
        }

        // Gövde dışında kalan galeri görselleri (ISAY valilik şablonu böyle çalışır)
        foreach ($xpath->query('//img[contains(@src, "aber") or contains(@data-src, "aber") or contains(@src, "upload") or contains(@src, "media")]') as $img) {
            if (($src = $this->usableImageSrc($img)) !== null) {
                return ['selector' => null, 'url' => $src];
            }
        }

        return ['selector' => null, 'url' => null];
    }

    /** Logo/ikon/placeholder olmayan, yeterince büyük görselin kaynağını döndürür */
    public function usableImageSrc(DOMElement $img): ?string
    {
        $src = $img->getAttribute('data-src') ?: $img->getAttribute('data-original') ?: $img->getAttribute('src');
        if ($src === '' || str_starts_with($src, 'data:')) return null;
        if (preg_match('/logo|icon|avatar|placeholder|spacer|blank|pixel|banner|footer|ataturk|bayrak/i', $src)) return null;

        // width="100%" gibi oransal değerler sayısal filtreye takılmamalı
        foreach (['width' => 200, 'height' => 150] as $attr => $min) {
            $raw = trim($img->getAttribute($attr));
            if ($raw !== '' && preg_match('/^\d+$/', $raw) && (int) $raw < $min) return null;
        }

        return $src;
    }

    /** Yayın tarihi: meta etiketleri, <time>, JSON-LD */
    public function detectPublishedAt(DOMXPath $xpath): ?string
    {
        $paths = [
            '//meta[@property="article:published_time"]/@content',
            '//meta[@property="og:published_time"]/@content',
            '//meta[@itemprop="datePublished"]/@content',
            '//meta[@name="date"]/@content',
            '//time/@datetime',
        ];
        foreach ($paths as $q) {
            $r = $xpath->query($q);
            if ($r !== false && $r->length > 0) {
                $v = trim($r->item(0)->nodeValue ?? '');
                if ($v !== '' && ($ts = strtotime($v)) !== false) {
                    return date('Y-m-d H:i:s', $ts);
                }
            }
        }

        foreach ($xpath->query('//*[@type="application/ld+json"]') as $n) {
            if (preg_match('/"datePublished"\s*:\s*"([^"]+)"/', $n->textContent ?? '', $m) && ($ts = strtotime($m[1])) !== false) {
                return date('Y-m-d H:i:s', $ts);
            }
        }

        return null;
    }

    /** Metin içinde 28.08.2026 / 28 Ağustos 2026 biçimli tarih arar */
    public function dateFromText(string $text): ?string
    {
        if (preg_match('/\b(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})\b/', $text, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1]);
        }
        $months = ['ocak' => 1, 'şubat' => 2, 'mart' => 3, 'nisan' => 4, 'mayıs' => 5, 'haziran' => 6,
            'temmuz' => 7, 'ağustos' => 8, 'eylül' => 9, 'ekim' => 10, 'kasım' => 11, 'aralık' => 12];
        if (preg_match('/\b(\d{1,2})\s+(' . implode('|', array_keys($months)) . ')\s+(\d{4})\b/iu', $text, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', $m[3], $months[mb_strtolower($m[2], 'UTF-8')], $m[1]);
        }

        return null;
    }

    // ── Yardımcılar ─────────────────────────────────────────────────────────

    public function absolutize(string $link, string $base): string
    {
        $link = trim($link);
        if ($link === '' || preg_match('/^(javascript|mailto|tel|#)/i', $link)) return '';
        if (str_starts_with($link, 'http://') || str_starts_with($link, 'https://')) return $link;

        $scheme = $this->part($base, PHP_URL_SCHEME) ?: 'https';
        $host = $this->part($base, PHP_URL_HOST);
        if ($host === '') return '';

        if (str_starts_with($link, '//')) return $scheme . ':' . $link;
        if (str_starts_with($link, '/')) return $scheme . '://' . $host . $link;

        $dir = rtrim(dirname($this->part($base, PHP_URL_PATH) ?: '/'), '/');

        return $scheme . '://' . $host . $dir . '/' . ltrim($link, './');
    }

    public function sameHost(string $a, string $b): bool
    {
        $ha = $this->hostOf($a);

        return $ha !== '' && $ha === $this->hostOf($b);
    }

    public function hostOf(string $url): string
    {
        return preg_replace('/^www\./', '', strtolower($this->part($url, PHP_URL_HOST))) ?? '';
    }

    private function part(string $url, int $component): string
    {
        return (string) (parse_url($url, $component) ?? '');
    }

    public function normalize(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\x{00a0}/u', ' ', $s) ?? $s;
        $s = preg_replace('/[ \t\r\f\v]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s*\n\s*/u', "\n", $s) ?? $s;

        return trim($s);
    }

    public function wordCount(string $s): int
    {
        $s = $this->normalize($s);

        return $s === '' ? 0 : count(preg_split('/\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function meaningfulClass(DOMElement $el): ?string
    {
        foreach (preg_split('/\s+/', trim($el->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $c) {
            if (! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $c)) continue;
            if (preg_match('/^(col|row|d|mb|mt|ms|me|ps|pe|px|py|pt|pb|pl|pr|w|h|m|p|g|gap|order|offset|text|bg|border|rounded|shadow|float|align|justify|position|fs|fw|lh|z|opacity|overflow)([-_]|$)/i', $c)) continue;
            if (preg_match('/^(container|wrapper|inner|outer|clearfix|active|show|fade|collapse|hidden|visible|first|last|odd|even)$/i', $c)) continue;
            if (preg_match('/^(css|sc|jsx|ember|elementor)-[a-z0-9]{5,}$/i', $c)) continue;

            return $c;
        }

        return null;
    }

    private function nodeToken(DOMElement $el): string
    {
        $tag = strtolower($el->tagName);
        $id = trim($el->getAttribute('id'));
        if ($id !== '' && ! preg_match('/\d{3,}/', $id)) {
            return $tag . '#' . $id;
        }
        $cls = $this->meaningfulClass($el);

        return $cls ? $tag . '.' . $cls : $tag;
    }
}
