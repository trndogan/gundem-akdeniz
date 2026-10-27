<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $modelLabel = 'Haber';
    protected static ?string $pluralModelLabel = 'Haberler';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Haber İçeriği')->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Başlık')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Boş bırakılırsa başlıktan otomatik oluşturulur')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('excerpt')
                        ->label('Özet')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('body')
                        ->label('İçerik')
                        ->required()
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'attachFiles',
                            'blockquote',
                            'bold',
                            'bulletList',
                            'codeBlock',
                            'h2',
                            'h3',
                            'italic',
                            'link',
                            'orderedList',
                            'redo',
                            'strike',
                            'underline',
                            'undo',
                        ]),
                ]),
                Forms\Components\Section::make('SEO Ayarları')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('SEO Başlık')
                            ->maxLength(70),
                        Forms\Components\Textarea::make('seo_description')
                            ->label('SEO Açıklama')
                            ->rows(2)
                            ->maxLength(160),
                        Forms\Components\TextInput::make('seo_keywords')
                            ->label('Anahtar Kelimeler')
                            ->maxLength(255),
                    ])->columns(1)->collapsible(),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Yayın Ayarları')->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('city_id')
                        ->label('Şehir')
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Yayın Tarihi')
                        ->default(now()),
                    Forms\Components\Toggle::make('is_published')
                        ->label('Yayında')
                        ->default(true),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Öne Çıkan')
                        ->default(false),
                    Forms\Components\Toggle::make('is_headline')
                        ->label('Manşet')
                        ->default(false),
                    Forms\Components\Toggle::make('is_breaking')
                        ->label('Son Dakika')
                        ->default(false),
                    Forms\Components\Toggle::make('is_press_release')
                        ->label('Resmi Açıklama')
                        ->default(false)
                        ->helperText('Basın bülteni / resmi açıklama'),
                ]),
                Forms\Components\Section::make('İlişkili Başkanlar')->schema([
                    Forms\Components\Select::make('mayors')
                        ->label('Başkanlar')
                        ->relationship('mayors', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->placeholder('Başkan adı yazın...'),
                ])->collapsible(),
                Forms\Components\Section::make('Görsel')->schema([
                    Forms\Components\FileUpload::make('_deferred_image')
                        ->label('Kapak görseli (dosya)')
                        ->image()
                        ->maxSize(10240)
                        ->storeFiles(false)
                        ->disk('local')
                        ->visibility('private')
                        ->directory('filament-deferred')
                        ->helperText('Önizleme hemen görünür; işleme ve yayın yolu ancak Kaydet / Güncelle ile yapılır.')
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('image_existing_preview')
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
                        ->placeholder('https://... veya boş bırakın')
                        ->helperText('Dosya yüklerseniz bu alan kayıtta otomatik güncellenir.')
                        ->columnSpanFull(),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_display_url')
                    ->label('Görsel')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Şehir')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Yayında')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_headline')
                    ->label('Manşet')
                    ->boolean(),
                Tables\Columns\TextColumn::make('view_count')
                    ->label('Görüntülenme')
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Yayın Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Şehir')
                    ->relationship('city', 'name'),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Yayın Durumu'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Öne Çıkan'),
                Tables\Filters\TernaryFilter::make('is_headline')
                    ->label('Manşet'),
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
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
