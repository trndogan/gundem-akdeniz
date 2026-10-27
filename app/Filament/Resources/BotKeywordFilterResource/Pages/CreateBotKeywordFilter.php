<?php

namespace App\Filament\Resources\BotKeywordFilterResource\Pages;

use App\Filament\Resources\BotKeywordFilterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBotKeywordFilter extends CreateRecord
{
    protected static string $resource = BotKeywordFilterResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
