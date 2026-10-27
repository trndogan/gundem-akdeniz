<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoLink extends Model
{
    protected $fillable = [
        'keyword',
        'url',
        'is_active',
        'new_tab',
        'nofollow',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'new_tab' => 'boolean',
        'nofollow' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
