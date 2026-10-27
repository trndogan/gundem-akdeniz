<?php

namespace App\Filament\Resources\BotDraftResource\Pages;

use App\Filament\Resources\BotDraftResource;
use App\Models\BotDraft;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBotDrafts extends ListRecords
{
    protected static string $resource = BotDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('processNow')
                ->label('Havuzdan taslak üret')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Havuzdaki haberler işlensin mi?')
                ->modalDescription('Bekleyen haberler Gemini ile özgünleştirilip görselleriyle kuyruğa eklenir. Bu işlem birkaç dakika sürebilir.')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('bot:process');

                    Notification::make()->success()
                        ->title('İşleme tamamlandı')
                        ->body(trim(\Illuminate\Support\Facades\Artisan::output()))
                        ->send();
                }),
        ];
    }

    /** Sekmeler durum bazlı hızlı geçiş sağlar; editör önce bekleyenlere bakar */
    public function getTabs(): array
    {
        $count = fn (string $status) => BotDraft::where('status', $status)->count();

        return [
            'draft' => Tab::make('Onay bekleyen')
                ->badge($count('draft'))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'approved' => Tab::make('Onaylı')
                ->badge($count('approved'))
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'published' => Tab::make('Yayınlanan')
                ->badge($count('published'))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'rejected' => Tab::make('Reddedilen')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['rejected', 'failed'])),
            'all' => Tab::make('Tümü'),
        ];
    }
}
