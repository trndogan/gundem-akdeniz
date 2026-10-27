<?php

namespace App\Filament\Resources\BotGeminiKeyResource\Pages;

use App\Filament\Resources\BotGeminiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBotGeminiKeys extends ListRecords
{
    protected static string $resource = BotGeminiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Anahtar ekle')];
    }
}
