<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\User;
use App\Services\Bot\BotSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Bot ayarları: yapay zekâ, toplama, görsel ve yayın davranışı tek ekranda.
 */
class BotSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Bot Ayarları';
    protected static ?string $title = 'Haber Botu Ayarları';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.bot-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(BotSettings::all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Ayarlar')->tabs([

                    Forms\Components\Tabs\Tab::make('Yapay Zekâ')->icon('heroicon-o-sparkles')->schema([
                        Forms\Components\TextInput::make('claude_api_key')
                            ->label('Claude API anahtarı')
                            ->password()
                            ->revealable()
                            ->helperText('Doluysa özgünleştirme ve editör puanlaması önce Claude ile yapılır. Claude yanıt veremezse (kredi bitti, hata) Gemini otomatik devreye girer. Boş bırakılırsa yalnız Gemini çalışır.'),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('claude_model')
                                ->label('Claude modeli')
                                ->required(),
                            Forms\Components\Select::make('claude_effort')
                                ->label('Claude efor seviyesi')
                                ->options(['low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek'])
                                ->required()
                                ->helperText('Yükseldikçe metin kalitesi ve maliyet artar. Editör puanlaması her zaman düşük eforla çalışır.'),
                        ]),
                        Forms\Components\TextInput::make('gemini_api_key')
                            ->label('Ana Gemini API anahtarı')
                            ->password()
                            ->revealable()
                            ->helperText('Ücretsiz anahtar havuzu tükendiğinde bu anahtar devreye girer. Görsel üretimi de bu anahtarı kullanır.'),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('gemini_model')
                                ->label('Ana model')
                                ->required(),
                            Forms\Components\TextInput::make('gemini_free_model')
                                ->label('Ücretsiz havuz modeli')
                                ->required(),
                            Forms\Components\TextInput::make('ai_temperature')
                                ->label('Sıcaklık')
                                ->numeric()
                                ->step(0.1)
                                ->minValue(0)
                                ->maxValue(2)
                                ->helperText('Yalnız Gemini için geçerlidir. Düşük değer daha sadık, yüksek değer daha yaratıcı metin üretir.'),
                        ]),
                        Forms\Components\Textarea::make('ai_prompt')
                            ->label('Özgünleştirme talimatı')
                            ->rows(18)
                            ->required()
                            ->helperText('Modelden istenen JSON alanlarını değiştirmeyin: title, content, excerpt, tags, seo_title, seo_description, city, category.'),
                    ]),

                    Forms\Components\Tabs\Tab::make('Toplama')->icon('heroicon-o-arrow-down-tray')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('crawl_batch')
                                ->label('Tur başına azami kaynak')
                                ->numeric()->minValue(0)->maxValue(500)->required()
                                ->helperText('0 yazarsanız sınır yoktur, tur tüm kaynakları dolaşır ve aşağıdaki süre dolunca durur. Önerilen budur: haber gecikmesi tarama aralığı kadar olur.'),
                            Forms\Components\TextInput::make('crawl_concurrency')
                                ->label('Eşzamanlı istek')
                                ->numeric()->minValue(1)->maxValue(40)->required()
                                ->helperText('Aynı anda kaç sayfa çekilsin. Yükseltmek taramayı hızlandırır ama kurum sunucularını ve kendi sunucunuzu yorar. 25 dengeli bir değerdir.'),
                            Forms\Components\TextInput::make('crawl_time_budget')
                                ->label('Tur süre bütçesi (dakika)')
                                ->numeric()->minValue(0)->maxValue(120)->required()
                                ->helperText('Tur bu süreyi aşmadan biter, yetişemediği kaynaklar sonraki turda alınır. Tarama 30 dakikada bir çalıştığı için 20 uygundur. Ölçüm: kaynak başına ~5 saniye.'),
                            Forms\Components\TextInput::make('max_links_per_source')
                                ->label('Kaynak başına azami haber')
                                ->numeric()->minValue(1)->maxValue(50)->required(),
                            Forms\Components\TextInput::make('min_words')
                                ->label('Asgari kelime sayısı')
                                ->numeric()->minValue(20)->required()
                                ->helperText('Bu sayının altındaki duyurular havuza alınmaz.'),
                            Forms\Components\TextInput::make('max_age_days')
                                ->label('Azami haber yaşı (gün)')
                                ->numeric()->minValue(0)->required()
                                ->helperText('0 yazarsanız tarih filtresi uygulanmaz.'),
                            Forms\Components\TextInput::make('similarity_threshold')
                                ->label('Benzerlik eşiği (%)')
                                ->numeric()->minValue(50)->maxValue(100)->required()
                                ->helperText('Son 48 saatteki başlıklarla bu oranın üzerinde benzeyen haber alınmaz. Aynı olayı birden çok kurum duyurduğunda tekrarı önler.'),
                        ]),
                    ]),

                    Forms\Components\Tabs\Tab::make('Editör')->icon('heroicon-o-scale')->schema([
                        Forms\Components\Toggle::make('curate_enabled')
                            ->label('Yapay zekâ editör katmanı')
                            ->helperText('Açıkken havuzdaki her haber önce haber değerine göre puanlanır. Düşük puanlılar elenir, yüksek puanlılar işlemede öne geçer ve manşet olarak işaretlenir. Puanlama toplu yapılır, tur başına tek yapay zeka çağrısı harcar.')
                            ->live(),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('curate_batch_size')
                                ->label('Tek çağrıda haber')
                                ->numeric()->minValue(5)->maxValue(120)
                                ->helperText('Puanlama toplu yapılır. Parti büyüdükçe haber başına maliyet düşer.'),
                            Forms\Components\TextInput::make('curate_max_batches')
                                ->label('Tur başına çağrı')
                                ->numeric()->minValue(1)->maxValue(20)
                                ->helperText('Havuz birikmişse tek turda kaç parti işlensin. 50 x 6 = turda 300 haber.'),
                            Forms\Components\TextInput::make('curate_min_score')
                                ->label('Eleme eşiği')
                                ->numeric()->minValue(0)->maxValue(100)
                                ->helperText('Bu puanın altındaki haberler elenir, yapay zekâya hiç gitmez.'),
                            Forms\Components\Toggle::make('curate_delete_rejected')
                                ->label('Elenen haberi sil')
                                ->helperText('Açıkken eşik altı kayıt havuzdan tamamen silinir. Kapalıyken "Elendi" olarak listede kalır.'),
                            Forms\Components\TextInput::make('curate_autopublish_score')
                                ->label('Onaysız yayın eşiği')
                                ->numeric()->minValue(0)->maxValue(100)
                                ->helperText('0 = kapalı. Üstündeki puanlar onay kuyruğunu atlar ve yayın sırasına girer.'),
                        ])->visible(fn ($get) => (bool) $get('curate_enabled')),

                        Forms\Components\Select::make('promotion_mode')
                            ->label('Manşet kararı')
                            ->options([
                                'relative' => 'Göreceli: elde ne varsa en iyisi manşet olur',
                                'absolute' => 'Mutlak: sabit puan eşiğini geçen manşet olur',
                            ])
                            ->required()
                            ->live()
                            ->visible(fn ($get) => (bool) $get('curate_enabled'))
                            ->helperText('Resmî kurum haberleri nadiren 85 puan alır. Mutlak kipte günlerce manşet çıkmayabilir; göreceli kipte son X saatteki en iyi haberler kota kadar manşete alınır.'),

                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make('promotion_window_hours')
                                ->label('Değerlendirme penceresi (saat)')
                                ->numeric()->minValue(6)->maxValue(168)
                                ->helperText('Sıralama bu süredeki haberler arasında yapılır.'),
                            Forms\Components\TextInput::make('headline_per_day')
                                ->label('Günlük manşet sayısı')
                                ->numeric()->minValue(0)->maxValue(10),
                            Forms\Components\TextInput::make('featured_per_day')
                                ->label('Günlük öne çıkan sayısı')
                                ->numeric()->minValue(0)->maxValue(30),
                            Forms\Components\TextInput::make('promotion_floor_score')
                                ->label('Taban puan')
                                ->numeric()->minValue(0)->maxValue(100)
                                ->helperText('Bu puanın altı hiçbir koşulda manşet olmaz.'),
                        ])->visible(fn ($get) => (bool) $get('curate_enabled') && $get('promotion_mode') === 'relative'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('curate_headline_score')
                                ->label('Manşet eşiği')
                                ->numeric()->minValue(0)->maxValue(100),
                            Forms\Components\TextInput::make('curate_featured_score')
                                ->label('Öne çıkan eşiği')
                                ->numeric()->minValue(0)->maxValue(100),
                        ])->visible(fn ($get) => (bool) $get('curate_enabled') && $get('promotion_mode') === 'absolute'),
                        Forms\Components\Textarea::make('curator_prompt')
                            ->label('Editör talimatı')
                            ->rows(16)
                            ->visible(fn ($get) => (bool) $get('curate_enabled'))
                            ->helperText('Çıktı JSON şemasını değiştirmeyin: {"haberler":[{"id":..,"puan":..,"neden":".."}]}'),
                    ]),

                    Forms\Components\Tabs\Tab::make('Görsel')->icon('heroicon-o-photo')->schema([
                        Forms\Components\Select::make('image_mode')
                            ->label('Görsel üretim biçimi')
                            ->options([
                                'hybrid' => 'Hibrit: kaynak görseli varsa işle, yoksa yapay zekâ üretsin',
                                'source' => 'Yalnız kaynak görseli, yoksa marka kartı',
                                'ai' => 'Her haberde yapay zekâ görseli',
                            ])
                            ->required(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('image_width')->label('Genişlik')->numeric()->required(),
                            Forms\Components\TextInput::make('image_height')->label('Yükseklik')->numeric()->required(),
                            Forms\Components\TextInput::make('ai_image_model')->label('Görsel modeli'),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('image_crop_percent')
                                ->label('Kenar kırpma (%)')
                                ->numeric()->minValue(0)->maxValue(20)
                                ->helperText('Kaynak görselden görsel olarak ayrışmayı sağlar.'),
                            Forms\Components\TextInput::make('image_darken')
                                ->label('Karartma (%)')
                                ->numeric()->minValue(0)->maxValue(80),
                            Forms\Components\TextInput::make('watermark_text')->label('Alt köşe marka yazısı'),
                        ]),
                        Forms\Components\Select::make('cover_engine')
                            ->label('Kapağı kim tasarlasın')
                            ->options([
                                'ai' => 'Yapay zekâ tasarlasın (tutmazsa kod devreye girer)',
                                'code' => 'Kod çizsin (anında, ücretsiz, her seferinde aynı)',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Yapay zekâ seçilirse yırtık kenar, fırça dokusu ve konuya uygun ikonlarla manşet tasarlar. Fotoğrafı koruyup korumadığı otomatik denetlenir, uydurursa çıktı reddedilir ve kod çizer.'),
                        Forms\Components\TextInput::make('ai_cover_similarity')
                            ->label('Fotoğraf koruma eşiği (%)')
                            ->numeric()->minValue(50)->maxValue(95)
                            ->visible(fn ($get) => $get('cover_engine') === 'ai')
                            ->helperText('AI çıktısındaki fotoğraf, orijinaline bu oranın altında benziyorsa kapak reddedilir. 75 önerilir; yükseltirseniz daha katı olur ama daha sık koda düşer.'),
                        Forms\Components\Select::make('image_template')
                            ->label('Kod şablonu')
                            ->options(\App\Services\Bot\ImageComposer::TEMPLATES)
                            ->required()
                            ->helperText('Yapay zekâ devre dışıyken ya da çıktısı reddedildiğinde kullanılır. Otomatik seçilirse şablon haberin başlığına göre belirlenir: aynı haber hep aynı kapağı alır ama haberden habere değişir.'),
                        Forms\Components\Toggle::make('image_title_band')
                            ->label('Kapağa başlık yaz')
                            ->helperText('Kapatırsanız görsel yalnızca kırpılıp karartılır, üzerine yazı basılmaz.'),
                    ]),

                    Forms\Components\Tabs\Tab::make('Yayın')->icon('heroicon-o-paper-airplane')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('process_batch')
                                ->label('Tur başına üretilecek taslak')
                                ->numeric()->minValue(1)->maxValue(50)->required()
                                ->helperText('Her taslak bir Gemini çağrısı harcar.'),
                            Forms\Components\TextInput::make('daily_limit')
                                ->label('Günlük haber limiti')
                                ->numeric()->minValue(0)->required(),
                            Forms\Components\Select::make('default_category_id')
                                ->label('Varsayılan kategori')
                                ->options(fn () => Category::orderBy('sort_order')->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Select::make('publish_user_id')
                                ->label('Yayıncı kullanıcı')
                                ->options(fn () => User::orderBy('id')->pluck('name', 'id'))
                                ->required()
                                ->helperText('Bot haberleri bu kullanıcı adına yayınlar.'),
                        ]),
                        Forms\Components\Select::make('publish_mode')
                            ->label('Yayın kipi')
                            ->options([
                                'interval' => 'Akış: her 15 dakikada bir, sırada haber varsa yayınla',
                                'slots' => 'Saat dilimi: yalnızca belirlenen saatlerde yayınla',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Akış kipinde hazır haber bekletilmez, doğal bir yayın temposu oluşur. Günlük limit her iki kipte de geçerlidir.'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('publish_per_run')
                                ->label('Tur başına yayınlanacak haber')
                                ->numeric()->minValue(1)->maxValue(10)
                                ->helperText('15 dakikada bir çalışır. 1 yazarsanız saatte 4 haber çıkar.'),
                            Forms\Components\TextInput::make('publish_active_hours')
                                ->label('Yayın penceresi')
                                ->placeholder('07:00-23:59')
                                ->helperText('Boş bırakırsanız gün boyu yayın yapılır. Gece yayın istemiyorsanız aralık yazın.'),
                        ])->visible(fn ($get) => $get('publish_mode') !== 'slots'),

                        Forms\Components\TextInput::make('publish_schedule')
                            ->label('Yayın saatleri')
                            ->required()
                            ->visible(fn ($get) => $get('publish_mode') === 'slots')
                            ->helperText('Virgülle ayırın: 08:00,09:30,11:00 … Onaylı haberler bu saatlere yayılarak yayınlanır.'),
                        Forms\Components\Toggle::make('auto_approve')
                            ->label('Onay kuyruğunu atla')
                            ->helperText('Açarsanız üretilen taslaklar doğrudan onaylı sayılır ve yayın saatinde otomatik yayınlanır. Kapalı tutmanız önerilir.'),
                    ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Toggle alanları bool döner; ayar tablosu metin tuttuğu için 1/0'a çevir
        foreach (['auto_approve', 'image_title_band', 'curate_enabled', 'curate_delete_rejected'] as $key) {
            $data[$key] = ! empty($data[$key]) ? '1' : '0';
        }

        BotSettings::setMany($data);

        Notification::make()->success()->title('Bot ayarları kaydedildi')->send();
    }

    /** Talimatı fabrika ayarına döndürmek için */
    public function resetPrompt(): void
    {
        $this->form->fill(array_merge($this->form->getState(), ['ai_prompt' => BotSettings::DEFAULT_PROMPT]));

        Notification::make()->success()->title('Talimat varsayılana döndürüldü')->body('Kaydetmeyi unutmayın.')->send();
    }
}
