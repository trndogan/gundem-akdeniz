<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotDraft extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'cover_facts' => 'array',
        'is_headline' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public const STATUSES = [
        'draft' => 'Onay bekliyor',
        'approved' => 'Onaylandı',
        'published' => 'Yayınlandı',
        'rejected' => 'Reddedildi',
        'failed' => 'Hata',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(BotSource::class, 'source_id');
    }

    public function poolItem(): BelongsTo
    {
        return $this->belongsTo(BotPoolItem::class, 'pool_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Üretilen görselin tarayıcıdan erişilebilir adresi (site kuralı: /images/...) */
    public function getImageUrlAttribute(): ?string
    {
        return \App\Helpers\PathHelper::urlForStoredUpload($this->image_path);
    }
}
