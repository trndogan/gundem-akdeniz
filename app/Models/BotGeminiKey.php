<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotGeminiKey extends Model
{
    protected $guarded = [];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
        'is_exhausted' => 'boolean',
        'last_reset_date' => 'date',
    ];

    /** Anahtarın son 4 hanesi — panelde tam anahtar gösterilmez */
    public function getMaskedKeyAttribute(): string
    {
        $key = (string) $this->api_key;

        return $key === '' ? '—' : str_repeat('•', 8) . substr($key, -4);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->daily_limit - $this->today_usage);
    }
}
