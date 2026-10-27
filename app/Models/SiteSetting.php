<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['group', 'key', 'label', 'value', 'type', 'sort_order'];

    public static function get(string $key, $default = null): ?string
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function getJson(string $key, $default = []): array
    {
        $val = static::get($key);
        if (!$val) return $default;
        return json_decode($val, true) ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::where('key', $key)->update(['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    public static function getByGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)->orderBy('sort_order')->get();
        });
    }
}
