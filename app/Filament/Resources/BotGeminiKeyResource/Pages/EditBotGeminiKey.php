<?php

namespace App\Filament\Resources\BotGeminiKeyResource\Pages;

use App\Filament\Resources\BotGeminiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBotGeminiKey extends EditRecord
{
    protected static string $resource = BotGeminiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Sil')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
