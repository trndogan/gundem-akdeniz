<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class FooterSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $navigationLabel = 'Footer Ayarları';
    protected static ?string $title = 'Footer Ayarları';
    protected static ?int $navigationSort = 33;
    protected static string $view = 'filament.pages.footer-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'footer_description' => SiteSetting::get('footer_description', 'Akdeniz Bölgesi\'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.'),
            'footer_copyright' => SiteSetting::get('footer_copyright', '© ' . date('Y') . ' GundemAkdeniz.com - Tüm hakları saklıdır.'),
            'footer_tagline' => SiteSetting::get('footer_tagline', 'Akdeniz Bölgesi\'nin Güvenilir Haber Kaynağı'),
            'social_facebook' => SiteSetting::get('social_facebook', '#'),
            'social_twitter' => SiteSetting::get('social_twitter', '#'),
            'social_instagram' => SiteSetting::get('social_instagram', '#'),
            'social_tiktok' => SiteSetting::get('social_tiktok', '#'),
            'service_cards' => SiteSetting::getJson('footer_service_cards', [
                ['title' => 'Hava Durumu', 'subtitle' => 'İlçe ilçe detaylı', 'icon' => 'wb_sunny', 'url' => '/hava-durumu', 'is_active' => true],
                ['title' => 'Nöbetçi Eczane', 'subtitle' => 'Canlı listeler', 'icon' => 'local_pharmacy', 'url' => '/nobetci-eczaneler', 'is_active' => true],
                ['title' => 'Belediye Başkanları', 'subtitle' => 'Başkanlar rehberi', 'icon' => 'account_balance', 'url' => '/belediye-baskanlari', 'is_active' => true],
                ['title' => 'Vefat Haberleri', 'subtitle' => 'Taziye ilanları', 'icon' => 'sentiment_dissatisfied', 'url' => '#', 'is_active' => true],
                ['title' => 'İş İlanları', 'subtitle' => 'Bölgesel fırsatlar', 'icon' => 'work', 'url' => '/is-ilanlari', 'is_active' => true],
                ['title' => 'Haber Gönder', 'subtitle' => 'İhbar ve bilgi', 'icon' => 'tips_and_updates', 'url' => '/haber-gonder', 'is_active' => true],
            ]),
            'extra_links' => SiteSetting::getJson('footer_extra_links', [
                ['title' => 'Haber Gönder', 'url' => '/haber-gonder', 'is_active' => true],
                ['title' => 'RSS', 'url' => '/sitemap.xml', 'is_active' => true],
            ]),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Footer')->tabs([
                Forms\Components\Tabs\Tab::make('Genel')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Textarea::make('footer_description')
                            ->label('Site Açıklaması')
                            ->rows(2)
                            ->helperText('Footer\'daki logo altı açıklama metni'),
                        Forms\Components\TextInput::make('footer_copyright')
                            ->label('Telif Hakkı Metni'),
                        Forms\Components\TextInput::make('footer_tagline')
                            ->label('Alt Slogan'),
                    ]),
                Forms\Components\Tabs\Tab::make('Sosyal Medya')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Forms\Components\TextInput::make('social_facebook')->label('Facebook URL')->nullable()->placeholder('https://facebook.com/...'),
                        Forms\Components\TextInput::make('social_twitter')->label('Twitter / X URL')->nullable()->placeholder('https://x.com/...'),
                        Forms\Components\TextInput::make('social_instagram')->label('Instagram URL')->nullable()->placeholder('https://instagram.com/...'),
                        Forms\Components\TextInput::make('social_tiktok')->label('TikTok URL')->nullable()->placeholder('https://tiktok.com/...'),
                    ])->columns(2),
                Forms\Components\Tabs\Tab::make('Servis Kartları')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Forms\Components\Repeater::make('service_cards')
                            ->label('Servis Kartları')
                            ->schema([
                                Forms\Components\TextInput::make('title')->label('Başlık')->required(),
                                Forms\Components\TextInput::make('subtitle')->label('Alt Yazı'),
                                Forms\Components\TextInput::make('icon')->label('İkon (Material)')->placeholder('wb_sunny, work, vb.')->required(),
                                Forms\Components\TextInput::make('url')->label('URL')->required(),
                                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                    ]),
                Forms\Components\Tabs\Tab::make('Ekstra Linkler')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Forms\Components\Repeater::make('extra_links')
                            ->label('Kurumsal Bölümü Ekstra Linkler')
                            ->helperText('Sayfalar (admin panelden) otomatik gelir. Buraya ek linkler ekleyebilirsiniz.')
                            ->schema([
                                Forms\Components\TextInput::make('title')->label('Başlık')->required(),
                                Forms\Components\TextInput::make('url')->label('URL')->required(),
                                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                            ])
                            ->columns(3)
                            ->reorderable(),
                    ]),
            ])->columnSpanFull(),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = [
            'footer_description' => $data['footer_description'],
            'footer_copyright' => $data['footer_copyright'],
            'footer_tagline' => $data['footer_tagline'],
            'social_facebook' => $data['social_facebook'],
            'social_twitter' => $data['social_twitter'],
            'social_instagram' => $data['social_instagram'],
            'social_tiktok' => $data['social_tiktok'],
            'footer_service_cards' => json_encode($data['service_cards']),
            'footer_extra_links' => json_encode($data['extra_links']),
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $key,
                    'value' => $value,
                    'group' => 'footer',
                ]
            );
        }

        // Clear all footer caches
        foreach (array_keys($settings) as $key) {
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.footer');

        Notification::make()->title('Footer ayarları kaydedildi!')->success()->send();
    }
}
