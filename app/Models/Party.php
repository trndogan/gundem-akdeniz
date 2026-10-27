<?php

namespace App\Models;

use App\Helpers\PathHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Party extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'logo_url',
        'primary_color',
        'secondary_color',
        'sort_order',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function mayors(): HasMany
    {
        return $this->hasMany(Mayor::class);
    }

    public function getCssClassAttribute(): string
    {
        return 'theme-' . $this->slug;
    }

    public function getLogoDisplayUrlAttribute(): ?string
    {
        return PathHelper::urlForStoredUpload($this->logo_url);
    }
}
