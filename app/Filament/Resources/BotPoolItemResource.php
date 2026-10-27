<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotPoolItemResource\Pages;
use App\Models\BotPoolItem;
use App\Services\Bot\ProcessorService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ham havuz: kaynaklardan çekilmiş, henüz yapay zekâdan geçmemiş haberler.
 * Buradan tek tek "taslak üret" denerek sıradan bağımsız işleme yapılabilir.
 */
class BotPoolItemResource extends Resource
{
    protected static ?string $model = BotPoolItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Haber Havuzu';
    protected static ?string $modelLabel = 'Havuz Kaydı';
    protected static ?string $pluralModelLabel = 'Haber Havuzu';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->columns([
                // Sütun sayısı bilinçli olarak az: gerekçe ayrı sütunda dururken
                // başlığı dikey şeride eziyor ve eylem düğmelerini ekran dışına itiyordu
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->wrap()
                    ->limit(140)
                    ->weight('bold')
                    ->description(fn (BotPoolItem $record) => trim(
                        ($record->source?->name ?? '') . ($record->curation_reason ? ' · ' . $record->curation_reason : '')
                    ))
                    ->grow(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Değer')
                    ->badge()
                    ->alignCenter()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state, BotPoolItem $record) => match (true) {
                        $record->suggest_headline => 'MANŞET ' . $state,
                        $record->suggest_featured => 'Öne çıkan ' . $state,
                        $state === null => '—',
                        default => (string) $state,
                    })
                    ->color(fn (?int $state, BotPoolItem $record) => match (true) {
                        $record->suggest_headline => 'danger',
                        $record->suggest_featured => 'warning',
                        $state === null => 'gray',
                        $state >= 45 => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'new' => 'Bekliyor',
                        'processing' => 'İşleniyor',
                        'used' => 'Taslak oldu',
                        'rejected' => 'Elendi',
                        'failed' => 'Hata',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'used' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Çekildi')
                    ->since()
                    ->alignEnd()
                    ->sortable(),

                // Teknik ayrıntılar: gerektiğinde sütun menüsünden açılır
                Tables\Columns\TextColumn::make('word_count')
                    ->label('Kelime')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('image_url')
                    ->label('Görsel')
                    ->boolean()
                    ->getStateUsing(fn (BotPoolItem $record) => filled($record->image_url))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source_published_at')
                    ->label('Kaynak tarihi')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('mansetlik')
                    ->label('Manşetlik (85+)')
                    ->query(fn ($query) => $query->where('score', '>=', 85)),
                Tables\Filters\Filter::make('puanlanmamis')
                    ->label('Henüz puanlanmamış')
                    ->query(fn ($query) => $query->whereNull('score')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'new' => 'Bekliyor',
                        'used' => 'Taslağa dönüştü',
                        'failed' => 'Hata',
                        'rejected' => 'Elendi',
                    ])
                    ->default('new'),
                Tables\Filters\SelectFilter::make('source_id')
                    ->label('Kaynak')
                    ->relationship('source', 'name')
                    ->searchable(),
            ])
            ->actions([
                // Havuzda ne çekildiğini görmeden karar verilemiyordu
                Tables\Actions\Action::make('preview')
                    ->label('Önizle')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Önizle')
                    ->color('gray')
                    ->modalHeading(fn (BotPoolItem $record) => mb_substr($record->title, 0, 80))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(fn (BotPoolItem $record) => view('filament.bot.pool-preview', ['item' => $record->fresh()])),

                Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('open')
                    ->label('Kaynakta aç')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (BotPoolItem $record) => $record->url, shouldOpenInNewTab: true),

                Tables\Actions\Action::make('process')
                    ->label('Taslak üret')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (BotPoolItem $record) => in_array($record->status, ['new', 'failed'], true))
                    ->action(function (BotPoolItem $record) {
                        // Liste sorgusu içerik gövdesini yüklemiyor; işleme için tam kaydı çek
                        $result = (new ProcessorService())->process($record->fresh());

                        $result['ok']
                            ? Notification::make()->success()->title('Taslak üretildi')->body($result['draft']->title)->send()
                            : Notification::make()->danger()->title('Üretilemedi')->body($result['error'])->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Ele')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (BotPoolItem $record) => $record->status === 'new')
                    ->action(fn (BotPoolItem $record) => $record->update(['status' => 'rejected', 'reject_reason' => 'Elle elendi'])),
                ])
                    ->label('İşlemler')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip('İşlemler'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('processMany')
                    ->label('Seçilenlerden taslak üret')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalDescription('Her haber için bir Gemini çağrısı yapılır, işlem uzun sürebilir.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        $processor = new ProcessorService();
                        $ok = $fail = 0;
                        foreach ($records as $record) {
                            if (! in_array($record->status, ['new', 'failed'], true)) continue;
                            $processor->process($record->fresh())['ok'] ? $ok++ : $fail++;
                        }

                        Notification::make()->success()->title("{$ok} taslak üretildi")
                            ->body($fail > 0 ? "{$fail} haber işlenemedi." : null)->send();
                    }),
                Tables\Actions\DeleteBulkAction::make()->label('Sil'),
            ])
            ->emptyStateHeading('Havuz boş')
            ->emptyStateDescription('Kaynaklar tarandığında yeni haberler burada birikir.');
    }

    /** Gövde metni listede gösterilmiyor; longText sütununu çekmeyerek liste hafif kalır */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('source')->select([
            'id', 'source_id', 'url', 'title', 'image_url', 'word_count',
            'score', 'curation_reason', 'suggest_headline', 'suggest_featured',
            'source_published_at', 'status', 'reject_reason', 'created_at', 'updated_at',
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBotPoolItems::route('/')];
    }
}
