<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class JobListing extends Model
{
    use HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'city_id',
        'district',
        'company_name',
        'sector',
        'employment_type',
        'salary_min',
        'salary_max',
        'phone',
        'whatsapp',
        'email',
        'description',
        'requirements',
        'deadline',
        'is_active',
        'is_featured',
        'view_count',
        'source',
        'source_url',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'deadline' => 'date',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('deadline')
                  ->orWhere('deadline', '>=', now()->format('Y-m-d'));
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('deadline')
            ->where('deadline', '<', now()->format('Y-m-d'));
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeBySector($query, string $sector)
    {
        return $query->where('sector', $sector);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return match($this->employment_type) {
            'full-time' => 'Tam Zamanlı',
            'part-time' => 'Yarı Zamanlı',
            'contract' => 'Sözleşmeli',
            'internship' => 'Staj',
            'freelance' => 'Freelance',
            'temporary' => 'Geçici',
            default => $this->employment_type,
        };
    }

    public function getSalaryTextAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) return null;
        if ($this->salary_min && $this->salary_max) {
            return number_format((float)$this->salary_min, 0, ',', '.') . ' - ' . number_format((float)$this->salary_max, 0, ',', '.') . ' TL';
        }
        if ($this->salary_min) return number_format((float)$this->salary_min, 0, ',', '.') . ' TL+';
        return number_format((float)$this->salary_max, 0, ',', '.') . ' TL\'ye kadar';
    }

    public function getUrlAttribute(): string
    {
        return route('job.show', $this->slug);
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        if (!$this->whatsapp) return null;
        $phone = preg_replace('/[^0-9]/', '', $this->whatsapp);
        $text = urlencode($this->title . ' ilanı hakkında bilgi almak istiyorum.');
        return "https://wa.me/{$phone}?text={$text}";
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
