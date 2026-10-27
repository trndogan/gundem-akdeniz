<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $modelLabel = 'Sayfa';
    protected static ?string $pluralModelLabel = 'Sayfalar';
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Section::make('Sayfa İçeriği')->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Sayfa Başlığı')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true),
                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules([new \App\Rules\ReservedSlug]),
                    Forms\Components\RichEditor::make('body')
                        ->label('İçerik')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'link', 'h2', 'h3',
                            'bulletList', 'orderedList',
                            'blockquote', 'codeBlock',
                            'undo', 'redo',
                        ])
                        ->columnSpanFull(),
                ])->columnSpan(['lg' => 2]),

                Forms\Components\Section::make('Ayarlar')->schema([
                    Forms\Components\Select::make('template')
                        ->label('Şablon')
                        ->options([
                            'default' => 'Varsayılan',
                            'contact' => 'İletişim',
                            'full_width' => 'Tam Genişlik',
                        ])
                        ->default('default'),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sıralama')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Forms\Components\Toggle::make('show_in_footer')
                        ->label('Footer\'da Göster')
                        ->default(true),
                ]),

                Forms\Components\Section::make('SEO Ayarları')->schema([
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
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('URL')
                    ->searchable()
                    ->color('gray')
                    ->prefix('/'),
                Tables\Columns\TextColumn::make('template')
                    ->label('Şablon')
                    ->badge(),
                Tables\Columns\IconColumn::make('show_in_footer')
                    ->label('Footer')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Güncelleme')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
                Tables\Filters\TernaryFilter::make('show_in_footer')->label('Footer\'da'),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
