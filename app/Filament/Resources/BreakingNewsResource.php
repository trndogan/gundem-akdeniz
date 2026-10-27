<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreakingNewsResource\Pages;
use App\Models\BreakingNews;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BreakingNewsResource extends Resource
{
    protected static ?string $model = BreakingNews::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $modelLabel = 'Son Dakika';
    protected static ?string $pluralModelLabel = 'Son Dakika Haberleri';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Son Dakika Bilgileri')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Başlık')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->label('Bağlantı URL')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Bitiş Tarihi')
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->limit(60),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('expires_at')->label('Bitiş')->dateTime('d.m.Y H:i')->placeholder('Süresiz'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreakingNews::route('/'),
            'create' => Pages\CreateBreakingNews::route('/create'),
            'edit' => Pages\EditBreakingNews::route('/{record}/edit'),
        ];
    }
}
