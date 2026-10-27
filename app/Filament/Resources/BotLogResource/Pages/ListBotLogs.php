<?php

namespace App\Filament\Resources\BotLogResource\Pages;

use App\Filament\Resources\BotLogResource;
use App\Services\Bot\BotLogger;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBotLogs extends ListRecords
{
    protected static string $resource = BotLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('prune')
                ->label('30 günden eskileri temizle')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $deleted = BotLogger::prune(30);

                    Notification::make()->success()->title("{$deleted} kayıt silindi")->send();
                }),
        ];
    }
}
