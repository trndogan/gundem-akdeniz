<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'stats' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }
}
