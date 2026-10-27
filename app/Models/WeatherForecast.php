<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherForecast extends Model
{
    protected $fillable = [
        'city_id',
        'forecast_date',
        'description',
        'icon',
        'temp_min',
        'temp_max',
        'temp_current',
        'humidity',
        'wind_speed',
        'wind_direction',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'temp_min' => 'decimal:1',
        'temp_max' => 'decimal:1',
        'temp_current' => 'decimal:1',
        'wind_speed' => 'decimal:1',
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
        return $query->whereDate('forecast_date', now()->format('Y-m-d'));
    }

    public function scopeWeek($query)
    {
        return $query->whereDate('forecast_date', '>=', now()->format('Y-m-d'))
                     ->whereDate('forecast_date', '<=', now()->addDays(6)->format('Y-m-d'));
    }

    public function getIconEmojiAttribute(): string
    {
        return match(true) {
            str_contains($this->icon ?? '', 'clear') || str_contains($this->description ?? '', 'güneş') || str_contains($this->description ?? '', 'açık') => '☀️',
            str_contains($this->icon ?? '', 'cloud') || str_contains($this->description ?? '', 'bulut') => '☁️',
            str_contains($this->icon ?? '', 'rain') || str_contains($this->description ?? '', 'yağmur') => '🌧️',
            str_contains($this->icon ?? '', 'snow') || str_contains($this->description ?? '', 'kar') => '❄️',
            str_contains($this->icon ?? '', 'thunder') || str_contains($this->description ?? '', 'gök') => '⛈️',
            str_contains($this->icon ?? '', 'fog') || str_contains($this->description ?? '', 'sis') => '🌫️',
            str_contains($this->description ?? '', 'parça') => '⛅',
            default => '🌤️',
        };
    }
}
