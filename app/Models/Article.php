<?php

namespace App\Models;

use App\Helpers\PathHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Article extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia;

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget('homepage.headline');
        Cache::forget('homepage.featured');
        Cache::forget('homepage.latest');
        Cache::forget('most_read_articles');
        Cache::forget('articles.most_read');
        Cache::forget('breaking_news');

        $cities = \App\Models\City::pluck('id');
        foreach ($cities as $cityId) {
            Cache::forget("city.{$cityId}.articles");
            Cache::forget("city_news.{$cityId}");
        }

        $categories = \App\Models\Category::pluck('slug');
        foreach ($categories as $slug) {
            Cache::forget("category.{$slug}.articles");
        }
    }

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'content_html',
        'category_id',
        'city_id',
        'user_id',
        'image_url',
        'is_published',
        'is_featured',
        'is_headline',
        'is_breaking',
        'is_press_release',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'view_count',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_headline' => 'boolean',
        'is_breaking' => 'boolean',
        'is_press_release' => 'boolean',
        'published_at' => 'datetime',
        'view_count' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->format('webp')
            ->quality(80);

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->format('webp')
            ->quality(85);

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(800)
            ->format('webp')
            ->quality(90);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->useFallbackUrl(asset('images/placeholder.jpg'));

        $this->addMediaCollection('gallery');
    }

    public function getMediaFileNameAttribute(): string
    {
        return $this->slug ?: \Illuminate\Support\Str::slug($this->title);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mayors(): BelongsToMany
    {
        return $this->belongsToMany(Mayor::class, 'article_mayor')->withTimestamps();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeHeadline($query)
    {
        return $query->where('is_headline', true);
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function getImageDisplayUrlAttribute(): ?string
    {
        $value = $this->attributes['image_url'] ?? null;

        return PathHelper::urlForStoredUpload($value);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->body));
        return max(1, (int) ceil($wordCount / 200));
    }

    public function getUrlAttribute(): string
    {
        $folder = $this->city ? $this->city->slug : ($this->category ? $this->category->slug : 'haber');
        return url("/{$folder}/{$this->slug}-h{$this->id}");
    }
}
