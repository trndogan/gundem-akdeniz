<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotPoolItem extends Model
{
    protected $table = 'bot_pool';

    protected $guarded = [];

    protected $casts = [
        'source_published_at' => 'datetime',
        'curated_at' => 'datetime',
        'suggest_headline' => 'boolean',
        'suggest_featured' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(BotSource::class, 'source_id');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'new');
    }
}
