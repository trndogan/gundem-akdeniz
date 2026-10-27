<?php

namespace App\Filament\Pages;

use App\Models\BotDraft;
use App\Models\BotGeminiKey;
use App\Models\BotPoolItem;
use App\Models\BotRun;
use App\Models\BotSource;
use App\Services\Bot\PublisherService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

/**
 * Bot kontrol paneli: durum kartları, yayın kotası, son çalışmalar,
 * sorunlu kaynaklar ve zinciri elle tetikleme düğmeleri.
 *
 * Kartlar ayrı bir Livewire widget'ı değil, doğrudan bu sayfanın görünümünde
 * çizilir. Widget'ı Filament'in keşif dizini dışına koymak Livewire'ın bileşeni
 * bulamamasına ve sayfanın 500 vermesine yol açıyordu.
 */
class BotDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Haber Botu';
    protected static ?string $navigationLabel = 'Bot Kontrol Paneli';
    protected static ?string $title = 'Haber Botu Kontrol Paneli';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.bot-dashboard';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crawl')
                ->label('Kaynakları tara')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Tarama turu başlatılsın mı?')
                ->modalDescription('Sıradaki resmî kaynaklar taranır, yeni haberler havuza eklenir.')
                ->action(fn () => $this->runCommand('bot:crawl', 'Tarama tamamlandı')),

            Action::make('process')
                ->label('Taslak üret')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Havuzdaki haberler işlensin mi?')
                ->modalDescription('Her haber için bir Gemini çağrısı yapılır ve görsel üretilir. Bu işlem birkaç dakika sürebilir.')
                ->action(fn () => $this->runCommand('bot:process', 'İşleme tamamlandı')),

            Action::make('publish')
                ->label('Yayın kotasını çalıştır')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Saat kotasına göre onaylı taslaklar yayınlanır.')
                ->action(fn () => $this->runCommand('bot:publish', 'Yayın turu tamamlandı')),
        ];
    }

    private function runCommand(string $command, string $title): void
    {
        Artisan::call($command);

        Notification::make()
            ->success()
            ->title($title)
            ->body(trim(Artisan::output()) ?: null)
            ->send();
    }

    /** Blade tarafına veri sağlar */
    public function getViewData(): array
    {
        $publisher = new PublisherService();

        $keyLimit = (int) BotGeminiKey::where('is_active', true)->sum('daily_limit');
        $keyUsed = (int) BotGeminiKey::where('is_active', true)->sum('today_usage');
        $brokenCount = BotSource::where('consecutive_errors', '>=', 3)->count();
        $bekleyen = BotDraft::where('status', 'draft')->count();

        return [
            'quota' => $publisher->quota(),
            'nextSlot' => $publisher->nextSlot(),

            'stats' => [
                [
                    'label' => 'Havuzda bekleyen',
                    'value' => BotPoolItem::where('status', 'new')->count(),
                    'hint' => BotPoolItem::where('status', 'new')->whereNull('score')->count() . ' tanesi henüz puanlanmadı',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Bugün yayınlanan',
                    'value' => BotDraft::where('status', 'published')->whereDate('published_at', today())->count()
                        . ' / ' . $publisher->quota()['target'],
                    'hint' => $bekleyen > 0 ? $bekleyen . ' taslak onayınızı bekliyor' : 'Onay kuyruğu boş, akış otomatik',
                    'tone' => $bekleyen > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Aktif kaynak',
                    'value' => BotSource::where('is_active', true)->count() . ' / ' . BotSource::count(),
                    'hint' => $brokenCount > 0 ? $brokenCount . ' kaynak hata veriyor' : 'Tüm kaynaklar sağlıklı',
                    'tone' => $brokenCount > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Gemini kotası',
                    'value' => $keyLimit > 0 ? $keyUsed . ' / ' . $keyLimit : 'Ana anahtar',
                    'hint' => $keyLimit > 0
                        ? BotGeminiKey::where('is_active', true)->where('is_exhausted', false)->count() . ' anahtar kullanılabilir'
                        : 'Ücretsiz anahtar havuzu boş',
                    'tone' => $keyLimit > 0 && $keyUsed >= $keyLimit ? 'danger' : 'muted',
                ],
            ],

            'runs' => BotRun::orderByDesc('started_at')->limit(8)->get(),
            'brokenSources' => BotSource::where('consecutive_errors', '>=', 3)
                ->orderByDesc('consecutive_errors')
                ->limit(8)
                ->get(),
            'topSources' => BotSource::where('fetched_total', '>', 0)
                ->orderByDesc('fetched_total')
                ->limit(8)
                ->get(),
            'latest' => BotDraft::where('status', 'published')
                ->with('source')
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(),
        ];
    }
}
