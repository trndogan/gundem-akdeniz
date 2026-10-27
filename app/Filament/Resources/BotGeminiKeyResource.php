<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotGeminiKeyResource\Pages;
use App\Models\BotGeminiKey;
use App\Services\Bot\BotSettings;
use App\Services\Bot\GeminiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Gemini anahtar havuzu. Ücretsiz anahtarların günlük kotası sınırlı olduğundan
 * bot en az kullanılan anahtardan başlar ve kotası dolanı gün sonuna kadar dinlendirir.
 */
class BotGeminiKeyResource extends Resource
{
    protected static ?string $model = BotGeminiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Gemini Anahtarları';
    protected static ?string $modelLabel = 'Anahtar';
    protected static ?string $pluralModelLabel = 'Gemini Anahtarları';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('label')
                    ->label('Etiket')
                    ->placeholder('Hesap 1')
                    ->helperText('Anahtarı tanımanız için, örneğin hangi Google hesabına ait olduğu.'),
                Forms\Components\TextInput::make('api_key')
                    ->label('API anahtarı')
                    ->required()
                    ->password()
                    ->revealable()
                    ->helperText('Veritabanında şifreli saklanır.'),
                Forms\Components\TextInput::make('model')
                    ->label('Model')
                    ->placeholder(BotSettings::get('gemini_free_model'))
                    ->helperText('Boş bırakılırsa ayarlardaki ücretsiz model kullanılır.'),
                Forms\Components\TextInput::make('daily_limit')
                    ->label('Günlük çağrı limiti')
                    ->numeric()
                    ->default(200)
                    ->required(),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                Forms\Components\Toggle::make('is_exhausted')
                    ->label('Kotası doldu')
                    ->helperText('Her gece otomatik sıfırlanır.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('Etiket')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('masked_key')->label('Anahtar')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('model')->label('Model')->placeholder('Varsayılan')->toggleable(),
                Tables\Columns\TextColumn::make('today_usage')
                    ->label('Bugün')
                    ->formatStateUsing(fn ($state, BotGeminiKey $record) => "{$state} / {$record->daily_limit}")
                    ->badge()
                    ->color(fn (BotGeminiKey $record) => $record->remaining > 20 ? 'success' : ($record->remaining > 0 ? 'warning' : 'danger')),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\IconColumn::make('is_exhausted')
                    ->label('Tükendi')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('last_error')->label('Son hata')->limit(40)->placeholder('—')->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test et')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->action(function (BotGeminiKey $record) {
                        $result = (new GeminiService())->testKey(
                            $record->api_key,
                            $record->model ?: (string) BotSettings::get('gemini_free_model'),
                        );

                        $record->update(['last_error' => $result['ok'] ? null : $result['message']]);

                        $result['ok']
                            ? Notification::make()->success()->title($result['message'])->send()
                            : Notification::make()->danger()->title('Anahtar çalışmıyor')->body($result['message'])->send();
                    }),
                Tables\Actions\Action::make('reset')
                    ->label('Kotayı sıfırla')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn (BotGeminiKey $record) => $record->update([
                        'today_usage' => 0, 'is_exhausted' => false, 'is_active' => true, 'last_error' => null,
                    ])),
                Tables\Actions\EditAction::make()->label('Düzenle'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Sil')])
            ->emptyStateHeading('Anahtar eklenmemiş')
            ->emptyStateDescription('Ücretsiz Gemini anahtarları ekleyerek günlük kotayı katlayabilirsiniz.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotGeminiKeys::route('/'),
            'create' => Pages\CreateBotGeminiKey::route('/create'),
            'edit' => Pages\EditBotGeminiKey::route('/{record}/edit'),
        ];
    }
}
