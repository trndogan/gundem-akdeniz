<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotSource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_publish' => 'boolean',
        'last_crawled_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    /** Kurum türleri — panelde etiket ve filtre olarak kullanılır */
    public const TYPES = [
        'valilik' => 'Valilik',
        'kaymakamlik' => 'Kaymakamlık',
        'belediye' => 'Belediye',
        'il_mudurlugu' => 'İl Müdürlüğü',
        'universite' => 'Üniversite',
        'kalkinma_ajansi' => 'Kalkınma Ajansı',
        'oda_borsa' => 'Oda / Borsa',
        'diger' => 'Diğer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function poolItems(): HasMany
    {
        return $this->hasMany(BotPoolItem::class, 'source_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->institution_type] ?? $this->institution_type;
    }

    /** Üst üste 5 hata alan kaynak sıraya girmez; panelde uyarı olarak gösterilir */
    public function isHealthy(): bool
    {
        return $this->consecutive_errors < 5;
    }
}
