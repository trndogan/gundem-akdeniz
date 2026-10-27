<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $modelLabel = 'Kategori';
    protected static ?string $pluralModelLabel = 'Kategoriler';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kategori Bilgileri')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Kategori Adı')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('slug')
                    ->label('URL Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules([new \App\Rules\ReservedSlug]),
                Forms\Components\Textarea::make('description')
                    ->label('Kısa Açıklama')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\ColorPicker::make('color')
                    ->label('Renk')
                    ->default('#fe0000'),
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
                ->description('Arama motorları için özel başlık ve açıklama. Boş bırakılırsa otomatik oluşturulur.')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->label('SEO Başlık')
                        ->placeholder('Örn: Ekonomi Haberleri - Gündem Akdeniz')
                        ->maxLength(70)
                        ->helperText('Maksimum 70 karakter önerilir.'),
                    Forms\Components\Textarea::make('seo_description')
                        ->label('SEO Açıklama')
                        ->placeholder('Örn: En güncel ekonomi haberleri ve gelişmeleri...')
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Maksimum 160 karakter önerilir.'),
                ])->columns(1)->collapsible(),

            Forms\Components\Section::make('Sayfa İçeriği')
                ->description('Kategori sayfasının altında görünecek açıklama metni. SEO için faydalıdır.')
                ->schema([
                    Forms\Components\RichEditor::make('content')
                        ->label('Sayfa Altı İçerik')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ])
                        ->columnSpanFull(),
                ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Renk'),
                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Haber Sayısı')
                    ->counts('articles')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('show_in_menu')
                    ->label('Menüde')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
