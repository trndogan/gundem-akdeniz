<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $modelLabel = 'Şehir';
    protected static ?string $pluralModelLabel = 'Şehirler';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Şehir Bilgileri')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Şehir Adı')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('URL Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules([new \App\Rules\ReservedSlug]),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\Toggle::make('show_in_menu')
                    ->label('Menüde Göster')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('SEO Ayarları')
                ->description('Şehir sayfası için özel başlık ve açıklama. Boş bırakılırsa otomatik oluşturulur.')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->label('SEO Başlık')
                        ->placeholder('Örn: Antalya Haberleri - Gündem Akdeniz')
                        ->maxLength(70)
                        ->helperText('Maksimum 70 karakter önerilir.'),
                    Forms\Components\Textarea::make('seo_description')
                        ->label('SEO Açıklama')
                        ->placeholder('Örn: Antalya\'dan en güncel haberler ve gelişmeler...')
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Maksimum 160 karakter önerilir.'),
                ])->columns(1)->collapsible(),

            Forms\Components\Section::make('Hava Durumu SEO Ayarları')
                ->description('Hava durumu sayfası için özel başlık, açıklama ve sayfa içeriği.')
                ->schema([
                    Forms\Components\TextInput::make('weather_seo_title')
                        ->label('Hava Durumu SEO Başlık')
                        ->placeholder('Örn: Antalya Hava Durumu - 7 Günlük Tahmin')
                        ->maxLength(70)
                        ->helperText('Maksimum 70 karakter önerilir.'),
                    Forms\Components\Textarea::make('weather_seo_description')
                        ->label('Hava Durumu SEO Açıklama')
                        ->placeholder('Örn: Antalya güncel hava durumu ve 7 günlük hava tahmini...')
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Maksimum 160 karakter önerilir.'),
                    Forms\Components\RichEditor::make('weather_content')
                        ->label('Hava Durumu Sayfa İçeriği')
                        ->helperText('Hava durumu sayfasının altında görünecek SEO açıklama metni.')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ])
                        ->columnSpanFull(),
                ])->columns(1)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
                Tables\Columns\TextColumn::make('articles_count')->label('Haber Sayısı')->counts('articles')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\IconColumn::make('show_in_menu')->label('Menüde')->boolean(),
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
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
