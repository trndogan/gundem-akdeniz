<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotSourceResource\Pages;
use App\Models\BotSource;
use App\Services\Bot\CrawlerService;
use App\Services\Bot\SourceDetector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Resmî kaynak kataloğu.
 *
 * Selektörler elle yazılmak zorunda değil: "Otomatik algıla" düğmesi kurumun
 * haber sayfasını analiz edip link ve gövde selektörlerini kendisi doldurur,
 * "Canlı test" ise kaydetmeden önce gerçekten haber çekilebildiğini gösterir.
 */
class BotSourceResource extends Resource
{
    protected static ?string $model = BotSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Resmî Kaynaklar';
    protected static ?string $modelLabel = 'Kaynak';
    protected static ?string $pluralModelLabel = 'Resmî Kaynaklar';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kurum')->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Kurum adı')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Antalya Valiliği'),
                Forms\Components\Select::make('institution_type')
                    ->label('Kurum türü')
                    ->options(BotSource::TYPES)
                    ->default('diger')
                    ->required(),
                Forms\Components\TextInput::make('site_url')
                    ->label('Kurum ana sayfası')
                    ->url()
                    ->placeholder('https://www.antalya.gov.tr')
                    ->helperText('Haber sayfası adresini bilmiyorsanız buraya ana sayfayı yazıp "Haber sayfasını bul" düğmesini kullanın.'),
                Forms\Components\TextInput::make('list_url')
                    ->label('Haber liste sayfası')
                    ->required()
                    ->url()
                    ->maxLength(500)
                    ->unique(ignoreRecord: true)
                    ->placeholder('https://www.antalya.gov.tr/haberler'),
                Forms\Components\Select::make('city_id')
                    ->label('Şehir')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->helperText('Bu kaynaktan gelen haberler varsayılan olarak bu şehre atanır.'),
                Forms\Components\Select::make('category_id')
                    ->label('Varsayılan kategori')
                    ->relationship('category', 'name')
                    ->searchable(),
            ]),

            Forms\Components\Section::make('Çekim ayarları')
                ->description('Selektörleri boş bırakırsanız bot her taramada sayfayı kendisi analiz eder. Bu daha esnektir ama biraz daha yavaştır.')
                ->columns(2)
                ->schema([
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('discover')
                            ->label('Haber sayfasını bul')
                            ->icon('heroicon-o-magnifying-glass')
                            ->color('gray')
                            ->action(function (Get $get, Set $set) {
                                $site = $get('site_url') ?: $get('list_url');
                                if (! $site) {
                                    Notification::make()->warning()->title('Önce kurum adresi girin')->send();

                                    return;
                                }

                                $found = (new SourceDetector())->discoverNewsUrl($site);
                                if ($found === null) {
                                    Notification::make()->danger()
                                        ->title('Haber sayfası bulunamadı')
                                        ->body('Site haberleri JavaScript ile yüklüyor olabilir. Adresi elle girmeyi deneyin.')
                                        ->send();

                                    return;
                                }

                                $set('list_url', $found['url']);
                                Notification::make()->success()
                                    ->title('Haber sayfası bulundu')
                                    ->body($found['url'] . ' — ' . $found['links'] . ' haber linki')
                                    ->send();
                            }),

                        Forms\Components\Actions\Action::make('detect')
                            ->label('Selektörleri otomatik algıla')
                            ->icon('heroicon-o-sparkles')
                            ->color('primary')
                            ->action(function (Get $get, Set $set) {
                                $url = $get('list_url');
                                if (! $url) {
                                    Notification::make()->warning()->title('Önce haber liste sayfası girin')->send();

                                    return;
                                }

                                $result = (new SourceDetector())->verify($url);
                                if (! $result['ok']) {
                                    Notification::make()->danger()
                                        ->title('Algılama başarısız')
                                        ->body($result['reason'])
                                        ->send();

                                    return;
                                }

                                $set('link_selector', $result['link_selector']);
                                $set('content_selector', $result['content_selector']);
                                $set('image_selector', $result['image_selector']);
                                $set('avg_words', $result['avg_words']);

                                Notification::make()->success()
                                    ->title('Selektörler algılandı')
                                    ->body(sprintf(
                                        '%d haber linki bulundu, ortalama %d kelime. Örnek: %s',
                                        $result['link_count'],
                                        $result['avg_words'],
                                        mb_substr($result['samples'][0]['title'] ?? '', 0, 60),
                                    ))
                                    ->persistent()
                                    ->send();
                            }),

                        Forms\Components\Actions\Action::make('test')
                            ->label('Canlı test')
                            ->icon('heroicon-o-play')
                            ->color('info')
                            ->action(function (Get $get) {
                                $url = $get('list_url');
                                if (! $url) return;

                                $result = (new SourceDetector())->verify($url);
                                $lines = collect($result['samples'] ?? [])
                                    ->map(fn ($s) => '• ' . mb_substr($s['title'], 0, 70) . ' (' . $s['words'] . ' kelime)')
                                    ->implode("\n");

                                $result['ok']
                                    ? Notification::make()->success()->title('Kaynak çalışıyor')->body($lines)->persistent()->send()
                                    : Notification::make()->danger()->title('Kaynak çalışmıyor')->body($result['reason'])->persistent()->send();
                            }),
                    ])->columnSpanFull(),

                    Forms\Components\Select::make('type')
                        ->label('Kaynak tipi')
                        ->options(['html' => 'HTML sayfası', 'rss' => 'RSS beslemesi'])
                        ->default('html')
                        ->required(),
                    Forms\Components\TextInput::make('priority')
                        ->label('Öncelik')
                        ->numeric()
                        ->default(50)
                        ->helperText('Yüksek olan önce taranır ve önce işlenir.'),
                    Forms\Components\TextInput::make('link_selector')
                        ->label('Haber linki selektörü')
                        ->placeholder('a.news-card-horizontal')
                        ->helperText('Liste sayfasındaki haber bağlantıları.'),
                    Forms\Components\TextInput::make('content_selector')
                        ->label('İçerik selektörü')
                        ->placeholder('.icerik')
                        ->helperText('Detay sayfasındaki haber gövdesi. Boşsa otomatik tespit edilir.'),
                    Forms\Components\TextInput::make('image_selector')
                        ->label('Görsel selektörü')
                        ->placeholder('.icerik img')
                        ->helperText('Boşsa gövdedeki ilk uygun görsel ya da og:image kullanılır.'),
                    Forms\Components\TextInput::make('title_selector')
                        ->label('Başlık selektörü')
                        ->helperText('Genellikle gerekmez; liste sayfasındaki bağlantı metni kullanılır.'),
                ]),

            Forms\Components\Section::make('Durum')->columns(3)->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\Toggle::make('auto_publish')
                    ->label('Onaysız yayınla')
                    ->helperText('Bu kaynağın haberleri onay kuyruğunu atlar.'),
                Forms\Components\TextInput::make('avg_words')
                    ->label('Ortalama kelime')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\Textarea::make('notes')
                    ->label('Not')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('last_error')
                    ->label('Son hata')
                    ->columnSpanFull()
                    ->visible(fn (?BotSource $record) => filled($record?->last_error))
                    ->content(fn (?BotSource $record) => $record?->last_error),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kurum')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (BotSource $record) => $record->list_url)
                    ->wrap()
                    ->grow(),
                Tables\Columns\TextColumn::make('institution_type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => BotSource::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'valilik' => 'danger',
                        'belediye' => 'success',
                        'kaymakamlik' => 'warning',
                        'il_mudurlugu' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Şehir')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Atanmadı')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fetched_total')
                    ->label('Toplanan')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('avg_words')
                    ->label('Ort. kelime')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_crawled_at')
                    ->label('Son tarama')
                    ->since()
                    ->placeholder('Hiç')
                    ->sortable(),
                Tables\Columns\TextColumn::make('consecutive_errors')
                    ->label('Hata')
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'success' : ($state < 5 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn (int $state) => $state === 0 ? 'Sağlıklı' : $state . ' hata')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_type')
                    ->label('Kurum türü')
                    ->options(BotSource::TYPES),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Şehir')
                    ->relationship('city', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
                Tables\Filters\Filter::make('problemli')
                    ->label('Sorunlu kaynaklar')
                    ->query(fn (Builder $query) => $query->where('consecutive_errors', '>=', 3)),
                Tables\Filters\Filter::make('sehirsiz')
                    ->label('Şehri atanmamış')
                    ->query(fn (Builder $query) => $query->whereNull('city_id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('crawlNow')
                    ->label('Şimdi tara')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (BotSource $record) {
                        $stats = (new CrawlerService())->crawl($record);

                        if ($stats['error']) {
                            Notification::make()->danger()->title('Tarama başarısız')->body($stats['error'])->send();

                            return;
                        }

                        $skipped = collect($stats['skipped'])->map(fn ($n, $k) => "{$k}: {$n}")->implode(', ');
                        Notification::make()->success()
                            ->title("{$stats['saved']} yeni haber havuza eklendi")
                            ->body($skipped !== '' ? "Elenen → {$skipped}" : null)
                            ->send();
                    }),

                Tables\Actions\Action::make('redetect')
                    ->label('Selektörleri yenile')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Kurum sitesi şablonunu değiştirdiyse selektörler yeniden algılanır.')
                    ->action(function (BotSource $record) {
                        $result = (new SourceDetector())->verify($record->list_url);

                        if (! $result['ok']) {
                            Notification::make()->danger()->title('Algılanamadı')->body($result['reason'])->send();

                            return;
                        }

                        $record->update([
                            'link_selector' => $result['link_selector'],
                            'content_selector' => $result['content_selector'],
                            'image_selector' => $result['image_selector'],
                            'avg_words' => $result['avg_words'],
                            'consecutive_errors' => 0,
                            'last_error' => null,
                        ]);

                        Notification::make()->success()->title('Selektörler güncellendi')->send();
                    }),

                Tables\Actions\EditAction::make()->label('Düzenle'),
                ])
                    ->label('İşlemler')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip('İşlemler'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Aktifleştir')
                    ->icon('heroicon-o-check')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => $records->each->update(['is_active' => true, 'consecutive_errors' => 0])),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Pasifleştir')
                    ->icon('heroicon-o-pause')
                    ->color('gray')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => $records->each->update(['is_active' => false])),
                Tables\Actions\BulkAction::make('assignCity')
                    ->label('Şehir ata')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->deselectRecordsAfterCompletion()
                    ->form([
                        Forms\Components\Select::make('city_id')
                            ->label('Şehir')
                            ->options(fn () => \App\Models\City::orderBy('sort_order')->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(fn ($records, array $data) => $records->each->update(['city_id' => $data['city_id']])),
                Tables\Actions\DeleteBulkAction::make()->label('Sil'),
            ])
            ->emptyStateHeading('Kaynak yok')
            ->emptyStateDescription('Akdeniz kaynak kataloğunu yüklemek için: php artisan db:seed --class=AkdenizBotSeeder');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['city', 'category']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotSources::route('/'),
            'create' => Pages\CreateBotSource::route('/create'),
            'edit' => Pages\EditBotSource::route('/{record}/edit'),
        ];
    }
}
