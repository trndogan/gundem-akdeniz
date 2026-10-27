<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pharmacy extends Model
{
    protected $fillable = [
        'city_id',
        'name',
        'address',
        'phone',
        'district',
        'latitude',
        'longitude',
        'duty_date',
    ];

    protected $casts = [
        'duty_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('duty_date', now()->format('Y-m-d'));
    }

    public function scopeTomorrow($query)
    {
        return $query->whereDate('duty_date', now()->addDay()->format('Y-m-d'));
    }
}
