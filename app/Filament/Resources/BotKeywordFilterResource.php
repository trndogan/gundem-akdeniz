<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotKeywordFilterResource\Pages;
use App\Models\BotKeywordFilter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Kelime filtreleri: istenmeyen içerikleri havuza girmeden eler.
 * "Engelle" bir kelime geçtiğinde haberi atar, "Zorunlu" tanımlıysa en az biri geçmelidir.
 */
class BotKeywordFilterResource extends Resource
{
    protected static ?string $model = BotKeywordFilter::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Kelime Filtreleri';
    protected static ?string $modelLabel = 'Filtre';
    protected static ?string $pluralModelLabel = 'Kelime Filtreleri';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('keyword')
                    ->label('Kelime veya ifade')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Büyük/küçük harf ayrımı yapılmaz, metin içinde geçmesi yeterlidir.'),
                Forms\Components\Select::make('mode')
                    ->label('Kural')
                    ->options(BotKeywordFilter::MODES)
                    ->default('block')
                    ->required()
                    ->helperText('Engelle: geçerse haber alınmaz. Zorunlu: hiçbiri geçmezse haber alınmaz.'),
                Forms\Components\Select::make('scope')
                    ->label('Nerede aransın')
                    ->options(BotKeywordFilter::SCOPES)
                    ->default('both')
                    ->required(),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hit_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('keyword')->label('Kelime')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('mode')
                    ->label('Kural')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => BotKeywordFilter::MODES[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'block' ? 'danger' : 'info'),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Kapsam')
                    ->formatStateUsing(fn (string $state) => BotKeywordFilter::SCOPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('hit_count')
                    ->label('Engellenen')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mode')->label('Kural')->options(BotKeywordFilter::MODES),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Düzenle')])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Sil')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotKeywordFilters::route('/'),
            'create' => Pages\CreateBotKeywordFilter::route('/create'),
            'edit' => Pages\EditBotKeywordFilter::route('/{record}/edit'),
        ];
    }
}
