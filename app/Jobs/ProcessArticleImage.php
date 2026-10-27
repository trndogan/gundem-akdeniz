<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessArticleImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Article $article,
        public string $imageUrl
    ) {}

    public function handle(): void
    {
        if (!$this->article->hasMedia('featured_image')) {
            $this->article
                ->addMediaFromUrl($this->imageUrl)
                ->toMediaCollection('featured_image');
        }
    }
}
