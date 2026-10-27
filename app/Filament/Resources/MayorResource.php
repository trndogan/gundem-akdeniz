<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MayorResource\Pages;
use App\Models\Mayor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MayorResource extends Resource
{
    protected static ?string $model = Mayor::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Modüller';
    protected static ?string $navigationLabel = 'Başkanlar';
    protected static ?string $modelLabel = 'Başkan';
    protected static ?string $pluralModelLabel = 'Başkanlar';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Kişisel Bilgiler')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Ad Soyad')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true),
                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('title')
                        ->label('Unvan')
                        ->default('Belediye Başkanı')
                        ->maxLength(255),
                    Forms\Components\Select::make('city_id')
                        ->label('İl')
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\TextInput::make('district')
                        ->label('İlçe')
                        ->maxLength(255)
                        ->placeholder('Boş bırakılırsa Büyükşehir kabul edilir'),
                    Forms\Components\Select::make('party_id')
                        ->label('Parti')
                        ->relationship('party', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ])->columns(2),

                Forms\Components\Section::make('Biyografi')->schema([
                    Forms\Components\Textarea::make('bio')
                        ->label('Kısa Biyografi')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('bio_full')
                        ->label('Detaylı Biyografi')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ])
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Belediye Bilgileri')->schema([
                    Forms\Components\TextInput::make('municipality_name')
                        ->label('Belediye Adı')
                        ->placeholder('Örn: Antalya Büyükşehir Belediyesi'),
                    Forms\Components\TextInput::make('municipality_logo_url')
                        ->label('Belediye Logo URL')
                        ->placeholder('/images/municipalities/antalya.png'),
                    Forms\Components\DatePicker::make('term_start')
                        ->label('Görev Başlangıcı'),
                    Forms\Components\DatePicker::make('term_end')
                        ->label('Görev Bitişi'),
                ])->columns(2)->collapsible(),

                Forms\Components\Section::make('SEO Ayarları')->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->label('SEO Başlık')
                        ->maxLength(70),
                    Forms\Components\Textarea::make('seo_description')
                        ->label('SEO Açıklama')
                        ->rows(2)
                        ->maxLength(160),
                ])->columns(1)->collapsible(),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Fotoğraf')->schema([
                    Forms\Components\FileUpload::make('_deferred_photo')
                        ->label('Yeni fotoğraf')
                        ->image()
                        ->maxSize(10240)
                        ->storeFiles(false)
                        ->disk('local')
                        ->visibility('private')
                        ->directory('filament-deferred')
                        ->helperText('Önizleme hemen görünür; dosya Kaydet veya Güncelle ile sunucuya işlenir.')
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('photo_existing_preview')
                        ->label('Mevcut fotoğraf')
                        ->content(function ($get) {
                            $url = \App\Helpers\PathHelper::urlForStoredUpload($get('photo_url'));
                            if (! $url) {
                                return null;
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="Mevcut fotoğraf" class="rounded-lg max-h-40 w-full object-cover border border-gray-200" />'
                            );
                        })
                        ->visible(fn ($get) => filled($get('photo_url')))
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('photo_url')
                        ->label('Harici URL veya mevcut yol')
                        ->maxLength(500)
                        ->placeholder('https://... veya boş bırakın')
                        ->helperText('Yerel dosya seçerseniz kayıtta bu alan otomatik dolar. Sadece dış adres kullanacaksanız buraya yazın.')
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Sosyal Medya')->schema([
                    Forms\Components\TextInput::make('social_twitter')
                        ->label('X (Twitter)')
                        ->prefix('https://')
                        ->placeholder('x.com/kullaniciadi'),
                    Forms\Components\TextInput::make('social_instagram')
                        ->label('Instagram')
                        ->prefix('https://')
                        ->placeholder('instagram.com/kullaniciadi'),
                    Forms\Components\TextInput::make('social_facebook')
                        ->label('Facebook')
                        ->prefix('https://')
                        ->placeholder('facebook.com/kullaniciadi'),
                    Forms\Components\TextInput::make('social_website')
                        ->label('Resmi Web Sitesi')
                        ->url()
                        ->placeholder('https://...'),
                ])->collapsible(),

                Forms\Components\Section::make('Durum')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sıralama')
                        ->numeric()
                        ->default(0),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_display_url')
                    ->label('Fotoğraf')
                    ->circular()
                    ->defaultImageUrl(asset('images/placeholder-person.png')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('party.name')
                    ->label('Parti')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('İl')
                    ->sortable(),
                Tables\Columns\TextColumn::make('district')
                    ->label('İlçe')
                    ->placeholder('Büyükşehir'),
                Tables\Columns\TextColumn::make('municipality_name')
                    ->label('Belediye')
                    ->limit(30),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Haber')
                    ->counts('articles'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('party_id')
                    ->label('Parti')
                    ->relationship('party', 'name'),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('İl')
                    ->relationship('city', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
            'index' => Pages\ListMayors::route('/'),
            'create' => Pages\CreateMayor::route('/create'),
            'edit' => Pages\EditMayor::route('/{record}/edit'),
        ];
    }
}
