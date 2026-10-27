<?php

/**
 * Gündem Akdeniz — Resmî kaynak keşif/doğrulama CLI'ı.
 *
 * Panelde çalışan App\Services\Bot\SourceDetector sınıfının ince sarmalayıcısıdır;
 * katalog üretimi için Laravel önyüklemeden koşar. Algoritma tek yerde durur.
 *
 * Kullanım:
 *   php tools/bot-detect.php --url=https://www.antalya.gov.tr/haberler
 *   php tools/bot-detect.php --site=https://www.osmaniye.bel.tr        (haber sayfasını bul)
 *   php tools/bot-detect.php --file=tools/candidates.json --out=tools/verified.json
 *   php tools/bot-detect.php --retry=tools/verified.json --out=tools/retry.json
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\Bot\SourceDetector;

$detector = new SourceDetector();

/** Adayı doğrular; bilinen URL tutmazsa alan adından keşfe düşer */
function inspect(SourceDetector $detector, array $candidate): array
{
    $result = null;

    if (! empty($candidate['url'])) {
        $result = $detector->verify($candidate['url']);
        if ($result['ok']) {
            return $candidate + ['url' => $candidate['url']] + $result;
        }
    }

    if (! empty($candidate['site'])) {
        $found = $detector->discoverNewsUrl($candidate['site']);
        if ($found !== null && $found['url'] !== ($candidate['url'] ?? null)) {
            $retry = $detector->verify($found['url']);
            if ($retry['ok'] || $result === null) {
                $candidate['url'] = $found['url'];

                return $candidate + $retry;
            }
        }
    }

    // HTML listesi tutmadıysa RSS beslemesi var mı bak
    if (! empty($candidate['site'])) {
        $feed = $detector->discoverFeed($candidate['site']);
        if ($feed !== null) {
            $feedResult = $detector->verifyFeed($feed);
            if ($feedResult['ok']) {
                $candidate['url'] = $feed;

                return $candidate + $feedResult;
            }
        }
    }

    if ($result !== null) {
        return $candidate + $result;
    }

    return $candidate + ['ok' => false, 'reason' => 'Haber sayfası bulunamadı', 'url' => $candidate['site'] ?? null];
}

function reportLine(int $i, int $total, array $r): string
{
    $info = $r['ok']
        ? sprintf('%2d link, ~%3d kelime | %s | %s', $r['link_count'], $r['avg_words'], $r['link_selector'], $r['content_selector'])
        : ($r['reason'] ?? 'bilinmeyen hata');

    return sprintf("[%3d/%3d] %s %-40s %s\n", $i, $total, $r['ok'] ? 'OK  ' : 'FAIL', mb_substr($r['name'] ?? $r['url'] ?? '', 0, 40), $info);
}

// ── CLI ─────────────────────────────────────────────────────────────────────

$opts = getopt('', ['url:', 'site:', 'file:', 'retry:', 'out:', 'quiet']);
$quiet = isset($opts['quiet']);
$encode = fn ($d) => json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (isset($opts['site'])) {
    $found = $detector->discoverNewsUrl($opts['site']);
    echo $encode($found ?? ['error' => 'Haber sayfası bulunamadı']), PHP_EOL;
    exit($found ? 0 : 1);
}

if (isset($opts['url'])) {
    $res = $detector->verify($opts['url']);
    echo $encode($res), PHP_EOL;
    exit($res['ok'] ? 0 : 1);
}

$source = $opts['file'] ?? $opts['retry'] ?? null;
if ($source === null) {
    fwrite(STDERR, "Kullanım:\n");
    fwrite(STDERR, "  php tools/bot-detect.php --url=<liste-url>\n");
    fwrite(STDERR, "  php tools/bot-detect.php --site=<https://kurum.gov.tr>\n");
    fwrite(STDERR, "  php tools/bot-detect.php --file=<aday.json> --out=<sonuc.json>\n");
    fwrite(STDERR, "  php tools/bot-detect.php --retry=<sonuc.json> --out=<yeniden.json>   (sadece başarısızları dener)\n");
    exit(2);
}

$candidates = json_decode((string) file_get_contents($source), true);
if (! is_array($candidates)) {
    fwrite(STDERR, "Aday listesi okunamadı: {$source}\n");
    exit(2);
}

// --retry: önceki çıktıdan yalnız başarısızları al, önceki sonuç alanlarını temizle
if (isset($opts['retry'])) {
    $candidates = array_values(array_map(
        fn ($r) => array_intersect_key($r, array_flip(['name', 'site', 'institution_type', 'city', 'category'])),
        array_filter($candidates, fn ($r) => empty($r['ok']))
    ));
}

$results = [];
$okCount = 0;
$total = count($candidates);

foreach ($candidates as $i => $c) {
    $res = inspect($detector, $c);
    $results[] = $res;
    if (! empty($res['ok'])) $okCount++;
    if (! $quiet) fwrite(STDERR, reportLine($i + 1, $total, $res));
}

if (isset($opts['out'])) {
    file_put_contents($opts['out'], $encode($results));
    fwrite(STDERR, sprintf("\nBitti: %d/%d doğrulandı → %s\n", $okCount, $total, $opts['out']));
} else {
    echo $encode($results), PHP_EOL;
}
