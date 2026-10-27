<?php

namespace App\Models;

use App\Helpers\PathHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Mayor extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'district',
        'party_id',
        'title',
        'photo_url',
        'bio',
        'bio_full',
        'municipality_name',
        'municipality_logo_url',
        'social_twitter',
        'social_instagram',
        'social_facebook',
        'social_website',
        'term_start',
        'term_end',
        'is_active',
        'sort_order',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'term_start' => 'date',
        'term_end' => 'date',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_mayor')
            ->withTimestamps()
            ->latest('published_at');
    }

    public function pressReleases(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_mayor')
            ->withTimestamps()
            ->where('is_press_release', true)
            ->latest('published_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function getUrlAttribute(): string
    {
        return route('mayor.show', $this->slug);
    }

    public function getPhotoDisplayUrlAttribute(): ?string
    {
        return PathHelper::urlForStoredUpload($this->photo_url);
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }
        return mb_strtoupper(mb_substr($this->name, 0, 2));
    }

    public function getHasPhotoAttribute(): bool
    {
        return PathHelper::storedUploadFileExists($this->photo_url);
    }

    public function getSocialUrlAttribute(): array
    {
        return [
            'twitter' => $this->ensureUrl($this->social_twitter),
            'instagram' => $this->ensureUrl($this->social_instagram),
            'facebook' => $this->ensureUrl($this->social_facebook),
            'website' => $this->ensureUrl($this->social_website),
        ];
    }

    private function ensureUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        return 'https://' . $url;
    }

    public function getMunicipalityLogoDisplayUrlAttribute(): ?string
    {
        return PathHelper::urlForStoredUpload($this->municipality_logo_url);
    }

    public function getLocationTextAttribute(): string
    {
        $parts = [];
        if ($this->district) $parts[] = $this->district;
        if ($this->city) $parts[] = $this->city->name;
        return implode(', ', $parts);
    }
}
