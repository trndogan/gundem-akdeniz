<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(BotSource::class, 'source_id');
    }
}
