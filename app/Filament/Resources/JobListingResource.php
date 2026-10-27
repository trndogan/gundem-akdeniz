<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobListingResource\Pages;
use App\Models\JobListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobListingResource extends Resource
{
    protected static ?string $model = JobListing::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Modüller';
    protected static ?string $navigationLabel = 'İş İlanları';
    protected static ?string $modelLabel = 'İş İlanı';
    protected static ?string $pluralModelLabel = 'İş İlanları';
    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('İlan Bilgileri')->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Pozisyon Adı')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('company_name')
                        ->label('Şirket Adı')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('city_id')
                        ->label('Şehir')
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\TextInput::make('district')
                        ->label('İlçe')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('sector')
                        ->label('Sektör')
                        ->maxLength(255)
                        ->placeholder('Örn: Turizm, İnşaat, Teknoloji'),
                    Forms\Components\Select::make('employment_type')
                        ->label('Çalışma Türü')
                        ->options([
                            'full-time' => 'Tam Zamanlı',
                            'part-time' => 'Yarı Zamanlı',
                            'contract' => 'Sözleşmeli',
                            'internship' => 'Staj',
                            'freelance' => 'Freelance',
                            'temporary' => 'Geçici',
                        ])
                        ->default('full-time')
                        ->required(),
                ])->columns(2),

                Forms\Components\Section::make('Maaş Bilgisi')->schema([
                    Forms\Components\TextInput::make('salary_min')
                        ->label('Minimum Maaş (TL)')
                        ->numeric()
                        ->placeholder('Örn: 25000'),
                    Forms\Components\TextInput::make('salary_max')
                        ->label('Maksimum Maaş (TL)')
                        ->numeric()
                        ->placeholder('Örn: 40000'),
                ])->columns(2)->collapsible(),

                Forms\Components\Section::make('İlan Detayı')->schema([
                    Forms\Components\RichEditor::make('description')
                        ->label('İş Tanımı')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ])
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('requirements')
                        ->label('Aranan Nitelikler')
                        ->toolbarButtons([
                            'bold', 'italic',
                            'bulletList', 'orderedList',
                        ])
                        ->columnSpanFull(),
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
                    ])->columns(1)->collapsible(),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('İletişim')->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefon')
                        ->tel()
                        ->placeholder('05XX XXX XX XX'),
                    Forms\Components\TextInput::make('whatsapp')
                        ->label('WhatsApp')
                        ->tel()
                        ->placeholder('905XXXXXXXXX')
                        ->helperText('Ülke kodu ile. Örn: 905321234567'),
                    Forms\Components\TextInput::make('email')
                        ->label('E-posta')
                        ->email(),
                ]),

                Forms\Components\Section::make('Yayın Ayarları')->schema([
                    Forms\Components\DatePicker::make('deadline')
                        ->label('Son Başvuru Tarihi')
                        ->nullable(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Öne Çıkan')
                        ->default(false),
                ]),

                Forms\Components\Section::make('Kaynak')->schema([
                    Forms\Components\TextInput::make('source')
                        ->label('Kaynak')
                        ->placeholder('Örn: Kariyer.net'),
                    Forms\Components\TextInput::make('source_url')
                        ->label('Kaynak URL')
                        ->url(),
                ])->collapsible(),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Pozisyon')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Şirket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Şehir')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('employment_type_label')
                    ->label('Tür')
                    ->badge(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Son Başvuru')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($record) => $record->is_expired ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->boolean(),
                Tables\Columns\TextColumn::make('view_count')
                    ->label('Görüntülenme')
                    ->sortable()
                    ->numeric(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Şehir')
                    ->relationship('city', 'name'),
                Tables\Filters\SelectFilter::make('employment_type')
                    ->label('Çalışma Türü')
                    ->options([
                        'full-time' => 'Tam Zamanlı',
                        'part-time' => 'Yarı Zamanlı',
                        'contract' => 'Sözleşmeli',
                        'internship' => 'Staj',
                        'freelance' => 'Freelance',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Öne Çıkan'),
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
            'index' => Pages\ListJobListings::route('/'),
            'create' => Pages\CreateJobListing::route('/create'),
            'edit' => Pages\EditJobListing::route('/{record}/edit'),
        ];
    }
}
