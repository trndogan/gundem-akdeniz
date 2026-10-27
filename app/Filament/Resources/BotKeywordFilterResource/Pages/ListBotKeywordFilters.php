<?php

namespace App\Filament\Resources\BotKeywordFilterResource\Pages;

use App\Filament\Resources\BotKeywordFilterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBotKeywordFilters extends ListRecords
{
    protected static string $resource = BotKeywordFilterResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Filtre ekle')];
    }
}
