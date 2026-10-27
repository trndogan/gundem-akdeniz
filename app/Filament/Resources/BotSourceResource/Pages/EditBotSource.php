<?php

namespace App\Filament\Resources\BotSourceResource\Pages;

use App\Filament\Resources\BotSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBotSource extends EditRecord
{
    protected static string $resource = BotSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Sil')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
