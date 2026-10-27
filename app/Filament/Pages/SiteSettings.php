<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Sayfa Yönetimi';
    protected static ?string $navigationLabel = 'Site Ayarları';
    protected static ?string $title = 'Site Ayarları';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::get('site_name', 'Gündem Akdeniz'),
            'site_description' => Setting::get('site_description', ''),
            'site_keywords' => Setting::get('site_keywords', ''),
            'logo_url' => Setting::get('logo_url', ''),
            'favicon_url' => Setting::get('favicon_url', ''),
            'contact_email' => Setting::get('contact_email', ''),
            'contact_phone' => Setting::get('contact_phone', ''),
            'contact_address' => Setting::get('contact_address', ''),
            'social_facebook' => Setting::get('social_facebook', ''),
            'social_twitter' => Setting::get('social_twitter', ''),
            'social_instagram' => Setting::get('social_instagram', ''),
            'social_youtube' => Setting::get('social_youtube', ''),
            'google_analytics_id' => Setting::get('google_analytics_id', ''),
            'header_scripts' => Setting::get('header_scripts', ''),
            'footer_scripts' => Setting::get('footer_scripts', ''),
            'weather_seo_title' => Setting::get('weather_seo_title', ''),
            'weather_seo_description' => Setting::get('weather_seo_description', ''),
            'weather_content' => Setting::get('weather_content', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Ayarlar')->tabs([
                    Forms\Components\Tabs\Tab::make('Genel')
                        ->icon('heroicon-o-globe-alt')
                        ->schema([
                            Forms\Components\TextInput::make('site_name')
                                ->label('Site Adı')
                                ->required(),
                            Forms\Components\Textarea::make('site_description')
                                ->label('Site Açıklaması')
                                ->rows(3),
                            Forms\Components\TextInput::make('site_keywords')
                                ->label('Anahtar Kelimeler'),
                            Forms\Components\TextInput::make('logo_url')
                                ->label('Logo URL')
                                ->url(),
                            Forms\Components\TextInput::make('favicon_url')
                                ->label('Favicon URL')
                                ->url(),
                        ]),
                    Forms\Components\Tabs\Tab::make('İletişim')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Forms\Components\TextInput::make('contact_email')
                                ->label('E-posta')
                                ->email(),
                            Forms\Components\TextInput::make('contact_phone')
                                ->label('Telefon'),
                            Forms\Components\Textarea::make('contact_address')
                                ->label('Adres')
                                ->rows(3),
                        ]),
                    Forms\Components\Tabs\Tab::make('Sosyal Medya')
                        ->icon('heroicon-o-share')
                        ->schema([
                            Forms\Components\TextInput::make('social_facebook')
                                ->label('Facebook')
                                ->url()
                                ->prefix('https://'),
                            Forms\Components\TextInput::make('social_twitter')
                                ->label('Twitter / X')
                                ->url()
                                ->prefix('https://'),
                            Forms\Components\TextInput::make('social_instagram')
                                ->label('Instagram')
                                ->url()
                                ->prefix('https://'),
                            Forms\Components\TextInput::make('social_youtube')
                                ->label('YouTube')
                                ->url()
                                ->prefix('https://'),
                        ]),
                    Forms\Components\Tabs\Tab::make('Hava Durumu SEO')
                        ->icon('heroicon-o-cloud')
                        ->schema([
                            Forms\Components\TextInput::make('weather_seo_title')
                                ->label('Hava Durumu Genel Sayfa SEO Başlık')
                                ->placeholder('Örn: Hava Durumu - Akdeniz Bölgesi Güncel Tahminler')
                                ->maxLength(70)
                                ->helperText('/hava-durumu sayfası için. Maksimum 70 karakter.'),
                            Forms\Components\Textarea::make('weather_seo_description')
                                ->label('Hava Durumu Genel Sayfa SEO Açıklama')
                                ->placeholder('Örn: Akdeniz Bölgesi hava durumu. Antalya, Mersin, Adana...')
                                ->rows(2)
                                ->maxLength(160)
                                ->helperText('/hava-durumu sayfası için. Maksimum 160 karakter.'),
                            Forms\Components\RichEditor::make('weather_content')
                                ->label('Hava Durumu Genel Sayfa İçeriği')
                                ->helperText('/hava-durumu sayfasının altında görünecek SEO açıklama metni.')
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike',
                                    'h2', 'h3',
                                    'bulletList', 'orderedList',
                                    'link', 'blockquote',
                                ]),
                        ]),
                    Forms\Components\Tabs\Tab::make('Kod & Analitik')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Forms\Components\TextInput::make('google_analytics_id')
                                ->label('Google Analytics ID')
                                ->placeholder('G-XXXXXXXXXX'),
                            Forms\Components\Textarea::make('header_scripts')
                                ->label('Header Kodları (head içi)')
                                ->rows(5)
                                ->placeholder('<script>...</script>'),
                            Forms\Components\Textarea::make('footer_scripts')
                                ->label('Footer Kodları (body sonu)')
                                ->rows(5)
                                ->placeholder('<script>...</script>'),
                        ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        Notification::make()
            ->title('Ayarlar kaydedildi')
            ->success()
            ->send();
    }
}
