<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReservedSlug implements ValidationRule
{
    protected static array $reserved = [
        'admin',
        'api',
        'login',
        'logout',
        'register',
        'password',
        'css',
        'js',
        'images',
        'fonts',
        'storage',
        'vendor',
        'livewire',
        'filament',
        'haber',
        'kategori',
        'sehir',
        'sayfa',
        'sitemap',
        'sitemap.xml',
        'robots.txt',
        'favicon.ico',
        'feed',
        'rss',
        'search',
        'build',
        'public',
        'assets',
        'uploads',
        'dashboard',
        'user',
        'profile',
        'settings',
        'cache',
        'sanctum',
        'nobetci-eczaneler',
        'hava-durumu',
        'is-ilanlari',
        'belediye-baskanlari',
        'haber-gonder',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array(strtolower($value), static::$reserved, true)) {
            $fail("\":attribute\" olarak \"$value\" kullanılamaz. Bu kelime sistem tarafından ayrılmıştır.");
        }
    }
}
