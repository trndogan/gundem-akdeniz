<?php

namespace App\Filament\Resources\BotSourceResource\Pages;

use App\Filament\Resources\BotSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBotSource extends CreateRecord
{
    protected static string $resource = BotSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
