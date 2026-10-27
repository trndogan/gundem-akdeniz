<?php

namespace App\Filament\Resources\BotDraftResource\Pages;

use App\Filament\Resources\BotDraftResource;
use App\Models\BotDraft;
use App\Services\Bot\ProcessorService;
use App\Services\Bot\PublisherService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBotDraft extends EditRecord
{
    protected static string $resource = BotDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publish')
                ->label('Kaydet ve yayınla')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (BotDraft $record) => $record->status !== 'published')
                ->action(function (BotDraft $record) {
                    $this->save(shouldRedirect: false);

                    $result = (new PublisherService())->publish($record->refresh(), force: true);

                    if ($result['ok']) {
                        Notification::make()->success()->title('Haber yayınlandı')->send();
                        $this->redirect(BotDraftResource::getUrl('index'));

                        return;
                    }

                    Notification::make()->danger()->title('Yayınlanamadı')->body($result['error'])->send();
                }),

            Actions\Action::make('regenerateImage')
                ->label('Görseli yenile')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->action(function (BotDraft $record) {
                    (new ProcessorService())->regenerateImage($record)
                        ? Notification::make()->success()->title('Görsel yenilendi')->send()
                        : Notification::make()->danger()->title('Görsel üretilemedi')->send();

                    $this->fillForm();
                }),

            Actions\Action::make('viewArticle')
                ->label('Yayındaki haberi aç')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn (BotDraft $record) => $record->article !== null)
                ->url(fn (BotDraft $record) => $record->article?->url, shouldOpenInNewTab: true),

            Actions\DeleteAction::make()->label('Sil'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
