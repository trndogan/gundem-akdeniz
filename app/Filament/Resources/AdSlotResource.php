<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdSlotResource\Pages;
use App\Models\AdSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdSlotResource extends Resource
{
    protected static ?string $model = AdSlot::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $modelLabel = 'Reklam Alanı';
    protected static ?string $pluralModelLabel = 'Reklam Alanları';
    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Reklam Bilgileri')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Reklam Adı')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('position')
                    ->label('Konum')
                    ->required()
                    ->options([
                        'header_top' => 'Header Üstü',
                        'header_bottom' => 'Header Altı',
                        'sidebar_top' => 'Sidebar Üst',
                        'sidebar_bottom' => 'Sidebar Alt',
                        'content_between' => 'İçerik Arası',
                        'footer_top' => 'Footer Üstü',
                        'article_inline' => 'Haber İçi',
                    ]),
                Forms\Components\Textarea::make('code')
                    ->label('Reklam Kodu (HTML/JS)')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('_deferred_ad_image')
                    ->label('Görsel dosyası')
                    ->image()
                    ->maxSize(10240)
                    ->storeFiles(false)
                    ->disk('local')
                    ->visibility('private')
                    ->directory('filament-deferred')
                    ->helperText('Kaydet / Güncelle ile işlenir.')
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('ad_existing_preview')
                    ->label('Mevcut görsel')
                    ->content(function ($get) {
                        $url = \App\Helpers\PathHelper::urlForStoredUpload($get('image_url'));
                        if (! $url) {
                            return null;
                        }

                        return new \Illuminate\Support\HtmlString(
                            '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="Mevcut görsel" class="rounded-lg max-h-40 w-full object-cover border border-gray-200" />'
                        );
                    })
                    ->visible(fn ($get) => filled($get('image_url')))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('image_url')
                    ->label('Harici URL veya mevcut yol')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('link_url')
                    ->label('Bağlantı URL')
                    ->url(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Başlangıç Tarihi'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Bitiş Tarihi'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('position')->label('Konum')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('starts_at')->label('Başlangıç')->dateTime('d.m.Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y')->placeholder('—'),
            ])
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
            'index' => Pages\ListAdSlots::route('/'),
            'create' => Pages\CreateAdSlot::route('/create'),
            'edit' => Pages\EditAdSlot::route('/{record}/edit'),
        ];
    }
}
