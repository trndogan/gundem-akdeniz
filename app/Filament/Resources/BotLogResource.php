<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotLogResource\Pages;
use App\Models\BotLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Bot günlüğü: tarama, yapay zekâ, görsel ve yayın adımlarının kayıtları. */
class BotLogResource extends Resource
{
    protected static ?string $model = BotLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Bot Günlüğü';
    protected static ?string $modelLabel = 'Günlük Kaydı';
    protected static ?string $pluralModelLabel = 'Bot Günlüğü';
    protected static ?int $navigationSort = 8;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Seviye')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'info' => 'Bilgi', 'warning' => 'Uyarı', 'error' => 'Hata', default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'error' => 'danger', 'warning' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Adım')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'crawl' => 'Tarama', 'ai' => 'Yapay zekâ', 'image' => 'Görsel',
                        'publish' => 'Yayın', default => 'Sistem',
                    }),
                Tables\Columns\TextColumn::make('message')->label('Mesaj')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('source.name')->label('Kaynak')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('context')
                    ->label('Ayrıntı')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '—')
                    ->wrap()
                    ->limit(120)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Seviye')
                    ->options(['info' => 'Bilgi', 'warning' => 'Uyarı', 'error' => 'Hata']),
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Adım')
                    ->options([
                        'crawl' => 'Tarama', 'ai' => 'Yapay zekâ', 'image' => 'Görsel',
                        'publish' => 'Yayın', 'system' => 'Sistem',
                    ]),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Sil')])
            ->emptyStateHeading('Günlük boş');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBotLogs::route('/')];
    }
}
