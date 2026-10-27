<?php

namespace App\Filament\Resources\BotKeywordFilterResource\Pages;

use App\Filament\Resources\BotKeywordFilterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBotKeywordFilter extends EditRecord
{
    protected static string $resource = BotKeywordFilterResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Sil')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
