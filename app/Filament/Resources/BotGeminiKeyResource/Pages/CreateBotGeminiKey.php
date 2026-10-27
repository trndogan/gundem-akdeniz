<?php

namespace App\Filament\Resources\BotGeminiKeyResource\Pages;

use App\Filament\Resources\BotGeminiKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBotGeminiKey extends CreateRecord
{
    protected static string $resource = BotGeminiKeyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
