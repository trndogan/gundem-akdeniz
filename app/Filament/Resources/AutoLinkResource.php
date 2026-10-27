<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoLinkResource\Pages;
use App\Models\AutoLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutoLinkResource extends Resource
{
    protected static ?string $model = AutoLink::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $navigationLabel = 'Otomatik Linkler';
    protected static ?string $modelLabel = 'Otomatik Link';
    protected static ?string $pluralModelLabel = 'Otomatik Linkler';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Link Bilgileri')->schema([
                Forms\Components\TextInput::make('keyword')
                    ->label('Anahtar Kelime')
                    ->required()
                    ->maxLength(255)
                    ->helperText('İçerikte bu kelime geçtiğinde otomatik link oluşturulur.'),
                Forms\Components\TextInput::make('url')
                    ->label('Hedef URL')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('/antalya veya https://...')
                    ->helperText('Dahili veya harici URL.'),
                Forms\Components\TextInput::make('priority')
                    ->label('Öncelik')
                    ->numeric()
                    ->default(0)
                    ->helperText('Yüksek öncelikli linkler önce işlenir.'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\Toggle::make('new_tab')
                    ->label('Yeni Sekmede Aç')
                    ->default(false),
                Forms\Components\Toggle::make('nofollow')
                    ->label('Nofollow')
                    ->default(false)
                    ->helperText('Harici linkler için önerilir.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('keyword')
                    ->label('Anahtar Kelime')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('url')
                    ->label('Hedef URL')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('new_tab')
                    ->label('Yeni Sekme')
                    ->boolean(),
                Tables\Columns\IconColumn::make('nofollow')
                    ->label('Nofollow')
                    ->boolean(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Öncelik')
                    ->sortable(),
            ])
            ->defaultSort('priority', 'desc')
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
            'index' => Pages\ListAutoLinks::route('/'),
            'create' => Pages\CreateAutoLink::route('/create'),
            'edit' => Pages\EditAutoLink::route('/{record}/edit'),
        ];
    }
}
