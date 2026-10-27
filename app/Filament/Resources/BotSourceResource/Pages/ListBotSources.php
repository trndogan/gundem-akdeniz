<?php

namespace App\Filament\Resources\BotSourceResource\Pages;

use App\Filament\Resources\BotSourceResource;
use App\Models\BotSource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBotSources extends ListRecords
{
    protected static string $resource = BotSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Kaynak ekle'),

            Actions\Action::make('crawlNow')
                ->label('Tarama turu başlat')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription('Sıradaki kaynaklar taranıp yeni haberler havuza eklenir.')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('bot:crawl');

                    Notification::make()->success()
                        ->title('Tarama tamamlandı')
                        ->body(trim(\Illuminate\Support\Facades\Artisan::output()))
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü')->badge(BotSource::count()),
            'active' => Tab::make('Aktif')
                ->badge(BotSource::where('is_active', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'problem' => Tab::make('Sorunlu')
                ->badge(BotSource::where('consecutive_errors', '>=', 3)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('consecutive_errors', '>=', 3)),
            'nocity' => Tab::make('Şehirsiz')
                ->badge(BotSource::whereNull('city_id')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('city_id')),
        ];
    }
}
