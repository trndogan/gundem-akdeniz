<?php

namespace App\Services;

use App\Models\AutoLink;

class AutoLinkerService
{
    public function process(string $html): string
    {
        $links = AutoLink::active()->orderByDesc('priority')->orderByDesc('id')->get();

        if ($links->isEmpty()) {
            return $html;
        }

        $linkedKeywords = [];

        foreach ($links as $link) {
            if (in_array(mb_strtolower($link->keyword), $linkedKeywords)) {
                continue;
            }

            $keyword = preg_quote($link->keyword, '/');

            // Regex: kelimeyi bul AMA şu tag'ların içindeyse dokunma:
            // <a>...</a>, <h1>-<h6>...</h>, <img ... alt="...">
            // Strateji: HTML'i parçalara ayır, sadece "güvenli" metin kısımlarında değiştir
            $result = $this->replaceInSafeZones($html, $keyword, $link);

            if ($result !== $html) {
                $html = $result;
                $linkedKeywords[] = mb_strtolower($link->keyword);
            }
        }

        return $html;
    }

    protected function replaceInSafeZones(string $html, string $keyword, AutoLink $link): string
    {
        // HTML'i tag ve metin parçalarına ayır
        // Korunan bölgeler: <a>...</a>, <h1>-<h6>...</h>, <img ...>, <script>...</script>, <style>...</style>
        $protectedPattern = '/(<a\b[^>]*>.*?<\/a>|<h[1-6]\b[^>]*>.*?<\/h[1-6]>|<img\b[^>]*>|<script\b[^>]*>.*?<\/script>|<style\b[^>]*>.*?<\/style>|<[^>]+>)/si';

        $parts = preg_split($protectedPattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        $replaced = false;

        foreach ($parts as &$part) {
            if ($replaced) {
                break;
            }

            // Eğer bu parça bir HTML tag'ı veya korunan bölge ise atla
            if (preg_match('/^</', $part)) {
                continue;
            }

            // Sadece düz metin kısımlarında kelimeyi ara (case-insensitive, kelime sınırı)
            $pattern = '/\b(' . $keyword . ')\b/ui';

            if (preg_match($pattern, $part)) {
                $target = $link->new_tab ? ' target="_blank"' : '';
                $rel = $link->nofollow ? ' rel="nofollow"' : '';
                $replacement = '<a href="' . e($link->url) . '"' . $target . $rel . '>$1</a>';

                // Sadece ilk eşleşmeyi değiştir
                $part = preg_replace($pattern, $replacement, $part, 1);
                $replaced = true;
            }
        }
        unset($part);

        return implode('', $parts);
    }
}
