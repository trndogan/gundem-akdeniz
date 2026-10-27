<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartyResource\Pages;
use App\Models\Party;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartyResource extends Resource
{
    protected static ?string $model = Party::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Modüller';
    protected static ?string $navigationLabel = 'Partiler';
    protected static ?string $modelLabel = 'Parti';
    protected static ?string $pluralModelLabel = 'Partiler';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Parti Bilgileri')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Parti Adı')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('URL Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('short_name')
                    ->label('Kısa Ad')
                    ->maxLength(20)
                    ->placeholder('Örn: AKP, CHP, MHP'),
                Forms\Components\FileUpload::make('_deferred_logo')
                    ->label('Logo dosyası')
                    ->image()
                    ->maxSize(10240)
                    ->storeFiles(false)
                    ->disk('local')
                    ->visibility('private')
                    ->directory('filament-deferred')
                    ->helperText('Kaydet / Güncelle ile işlenir.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('logo_url')
                    ->label('Logo URL veya mevcut yol')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('logo_existing_preview')
                    ->label('Mevcut logo')
                    ->content(function ($get) {
                        $url = \App\Helpers\PathHelper::urlForStoredUpload($get('logo_url'));
                        if (! $url) {
                            return null;
                        }

                        return new \Illuminate\Support\HtmlString(
                            '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="Mevcut logo" class="rounded-lg max-h-40 w-full object-contain border border-gray-200" />'
                        );
                    })
                    ->visible(fn ($get) => filled($get('logo_url')))
                    ->columnSpanFull(),
                Forms\Components\ColorPicker::make('primary_color')
                    ->label('Ana Renk')
                    ->default('#333333'),
                Forms\Components\ColorPicker::make('secondary_color')
                    ->label('Yardımcı Renk')
                    ->default('#666666'),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Parti Adı')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('short_name')->label('Kısa Ad')->badge(),
                Tables\Columns\ColorColumn::make('primary_color')->label('Ana Renk'),
                Tables\Columns\ColorColumn::make('secondary_color')->label('Yardımcı Renk'),
                Tables\Columns\TextColumn::make('mayors_count')->label('Başkan Sayısı')->counts('mayors'),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParties::route('/'),
            'create' => Pages\CreateParty::route('/create'),
            'edit' => Pages\EditParty::route('/{record}/edit'),
        ];
    }
}
