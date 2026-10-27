<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotKeywordFilter extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const MODES = [
        'block' => 'Engelle',
        'require' => 'Zorunlu',
    ];

    public const SCOPES = [
        'title' => 'Başlık',
        'content' => 'İçerik',
        'both' => 'Başlık + İçerik',
    ];
}
