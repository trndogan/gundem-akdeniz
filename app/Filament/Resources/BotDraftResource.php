<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotDraftResource\Pages;
use App\Models\BotDraft;
use App\Services\Bot\ProcessorService;
use App\Services\Bot\PublisherService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Onay kuyruğu: botun ürettiği taslaklar burada incelenir, düzeltilir ve yayınlanır.
 * Editörün asıl çalıştığı ekran budur.
 */
class BotDraftResource extends Resource
{
    protected static ?string $model = BotDraft::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Onay Kuyruğu';
    protected static ?string $modelLabel = 'Taslak';
    protected static ?string $pluralModelLabel = 'Taslaklar';
    protected static ?int $navigationSort = 2;

    /** Bekleyen taslak sayısı menüde rozet olarak görünür */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'draft')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'draft')->count() > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Group::make()->columnSpan(2)->schema([
                    Forms\Components\Section::make('Haber')->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('Başlıkta virgül ve uzun tire kullanmayın.'),
                        Forms\Components\Textarea::make('excerpt')
                            ->label('Özet')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('body')
                            ->label('İçerik')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo']),
                    ]),

                    Forms\Components\Section::make('SEO')->collapsed()->schema([
                        Forms\Components\TextInput::make('seo_title')->label('SEO Başlığı')->maxLength(255),
                        Forms\Components\Textarea::make('seo_description')->label('Meta Açıklama')->rows(2),
                        Forms\Components\TextInput::make('tags')->label('Etiketler')->helperText('Virgülle ayırın.'),
                    ])->columns(1),
                ]),

                Forms\Components\Group::make()->columnSpan(1)->schema([
                    Forms\Components\Section::make('Görsel')->schema([
                        Forms\Components\Placeholder::make('image_preview')
                            ->hiddenLabel()
                            ->content(fn (?BotDraft $record) => $record?->image_url
                                ? new HtmlString('<img src="' . e($record->image_url) . '" style="width:100%;border-radius:8px" alt="">')
                                : 'Görsel üretilmedi.'),
                        Forms\Components\TextInput::make('image_path')
                            ->label('Görsel yolu')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Placeholder::make('image_mode')
                            ->label('Üretim biçimi')
                            ->content(fn (?BotDraft $record) => match ($record?->image_mode) {
                                'ai_cover' => 'Kapağı yapay zekâ tasarladı, fotoğraf doğrulandı',
                                'processed' => 'Kaynak görseli işlendi, kapağı kod çizdi',
                                'ai' => 'Fotoğrafı yapay zekâ üretti',
                                'placeholder' => 'Marka kartı çizildi',
                                default => 'Yok',
                            }),
                    ]),

                    Forms\Components\Section::make('Sınıflandırma')->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('city_id')
                            ->label('Şehir')
                            ->relationship('city', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options(BotDraft::STATUSES)
                            ->required(),
                        Forms\Components\Toggle::make('is_headline')
                            ->label('Manşet')
                            ->helperText('Yayınlandığında ana sayfada manşet olur.'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Öne çıkan'),
                    ]),

                    Forms\Components\Section::make('Kaynak')->schema([
                        Forms\Components\Placeholder::make('source_name')
                            ->label('Kurum')
                            ->content(fn (?BotDraft $record) => $record?->source?->name ?? '—'),
                        Forms\Components\Placeholder::make('original')
                            ->label('Orijinal haber')
                            ->content(fn (?BotDraft $record) => $record?->original_url
                                ? new HtmlString('<a href="' . e($record->original_url) . '" target="_blank" rel="noopener" class="text-primary-600 underline">Kaynakta aç</a>')
                                : '—'),
                        Forms\Components\Placeholder::make('ai_model')
                            ->label('Kullanılan model')
                            ->content(fn (?BotDraft $record) => $record?->ai_model ?? '—'),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')   // cron arka planda taslak ürettiğinde liste kendi tazelensin
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Görsel')
                    ->height(56)
                    ->width(96),
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->wrap()
                    ->limit(140)
                    ->weight('bold')
                    ->description(fn (BotDraft $record) => $record->source?->name)
                    ->grow(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Değer')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state, BotDraft $record) => match (true) {
                        $record->is_headline => 'MANŞET ' . $state,
                        $record->is_featured => 'Öne çıkan ' . $state,
                        $state === null => '—',
                        default => (string) $state,
                    })
                    ->color(fn (BotDraft $record) => match (true) {
                        $record->is_headline => 'danger',
                        $record->is_featured => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Şehir')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('image_mode')
                    ->label('Görsel')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'ai_cover' => 'AI kapak',
                        'processed' => 'Kaynak',
                        'ai' => 'AI foto',
                        'placeholder' => 'Kart',
                        default => 'Yok',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'ai_cover' => 'primary',
                        'processed' => 'success',
                        'ai' => 'info',
                        'placeholder' => 'gray',
                        default => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (string $state) => BotDraft::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'warning',
                        'approved' => 'info',
                        'published' => 'success',
                        'rejected' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Üretildi')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(BotDraft::STATUSES)
                    ->default('draft'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Şehir')
                    ->relationship('city', 'name'),
                Tables\Filters\SelectFilter::make('source_id')
                    ->label('Kaynak')
                    ->relationship('source', 'name')
                    ->searchable(),
            ])
            ->actions([
                // Beş eylem yan yana dizilince tablo yatayda taşıyor ve başlık sütunu
                // dikey şeride sıkışıyordu; hepsi tek açılır menüde toplanır.
                Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('publish')
                    ->label('Yayınla')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Haber yayınlansın mı?')
                    ->modalDescription(fn (BotDraft $record) => $record->title)
                    ->visible(fn (BotDraft $record) => in_array($record->status, ['draft', 'approved'], true))
                    ->action(function (BotDraft $record) {
                        $result = (new PublisherService())->publish($record, force: true);

                        $result['ok']
                            ? Notification::make()->success()->title('Haber yayınlandı')->send()
                            : Notification::make()->danger()->title('Yayınlanamadı')->body($result['error'])->send();
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (BotDraft $record) => $record->status === 'draft')
                    ->action(function (BotDraft $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->success()
                            ->title('Onaylandı')
                            ->body('Haber yayın saatinde otomatik yayınlanacak.')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BotDraft $record) => ! in_array($record->status, ['published', 'rejected'], true))
                    ->action(function (BotDraft $record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->success()->title('Reddedildi')->send();
                    }),

                Tables\Actions\Action::make('regenerateImage')
                    ->label('Görseli yenile')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->visible(fn (BotDraft $record) => $record->status !== 'published')
                    ->action(function (BotDraft $record) {
                        (new ProcessorService())->regenerateImage($record)
                            ? Notification::make()->success()->title('Görsel yenilendi')->send()
                            : Notification::make()->danger()->title('Görsel üretilemedi')->send();
                    }),

                Tables\Actions\EditAction::make()->label('Düzenle'),
                ])
                    ->label('İşlemler')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip('İşlemler'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approveMany')
                    ->label('Seçilenleri onayla')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status !== 'draft') continue;
                            $record->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
                            $count++;
                        }

                        Notification::make()->success()->title("{$count} taslak onaylandı")->send();
                    }),

                Tables\Actions\BulkAction::make('publishMany')
                    ->label('Seçilenleri yayınla')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        $publisher = new PublisherService();
                        $ok = $fail = 0;
                        foreach ($records as $record) {
                            $publisher->publish($record, force: true)['ok'] ? $ok++ : $fail++;
                        }

                        Notification::make()->success()->title("{$ok} haber yayınlandı")
                            ->body($fail > 0 ? "{$fail} haber yayınlanamadı." : null)->send();
                    }),

                Tables\Actions\BulkAction::make('rejectMany')
                    ->label('Seçilenleri reddet')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => $records->each->update([
                        'status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
                    ])),

                Tables\Actions\DeleteBulkAction::make()->label('Seçilenleri sil'),
            ])
            ->emptyStateHeading('Onay bekleyen taslak yok')
            ->emptyStateDescription('Bot kaynakları tarayıp haber ürettiğinde taslaklar burada listelenir.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['source', 'category', 'city']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotDrafts::route('/'),
            'edit' => Pages\EditBotDraft::route('/{record}/edit'),
        ];
    }
}
