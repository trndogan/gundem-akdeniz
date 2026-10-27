<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\AutoLinkerService;

class ArticleObserver
{
    public function saving(Article $article): void
    {
        // Görsel SEO: body içindeki boş alt tag'larını otomatik doldur
        if ($article->isDirty('body') && $article->body) {
            $article->body = $this->fillEmptyAltTags($article);
        }

        // Otomatik iç linkleme: body'den content_html üret
        if ($article->isDirty('body') || !$article->content_html) {
            $linker = app(AutoLinkerService::class);
            $article->content_html = $linker->process($article->body ?? '');
        }
    }

    protected function fillEmptyAltTags(Article $article): string
    {
        $altText = $article->title;
        if ($article->city_id && $article->relationLoaded('city') && $article->city) {
            $altText .= ' - ' . $article->city->name;
        } elseif ($article->city_id) {
            $city = \App\Models\City::find($article->city_id);
            if ($city) {
                $altText .= ' - ' . $city->name;
            }
        }

        // Boş alt="" veya alt attribute'u olmayan img tag'larını doldur
        $body = $article->body;

        // alt="" (boş alt) olanları doldur
        $body = preg_replace(
            '/<img([^>]*)\salt=["\'][\s]*["\']([^>]*)>/i',
            '<img$1 alt="' . e($altText) . '"$2>',
            $body
        );

        // alt attribute'u hiç olmayan img tag'larını doldur
        $body = preg_replace_callback(
            '/<img(?![^>]*\balt\b)([^>]*)>/i',
            function ($matches) use ($altText) {
                return '<img alt="' . e($altText) . '"' . $matches[1] . '>';
            },
            $body
        );

        return $body;
    }
}
