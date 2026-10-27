{{-- Haber Botu kontrol paneli.
     Yerleşim Filament'in derlenmiş CSS'ine güvenmez: xl:grid-cols-3 gibi sınıflar o
     derlemede bulunmadığı için kartlar alt alta diziliyordu. Grid burada tanımlanır. --}}
<x-filament-panels::page>

    <style>
        .bot-grid { display: grid; gap: .75rem; }
        .bot-grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .bot-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .bot-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 1280px) { .bot-grid--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 1024px) { .bot-grid--3, .bot-grid--2 { grid-template-columns: minmax(0, 1fr); } }
        @media (max-width: 640px)  { .bot-grid--4 { grid-template-columns: minmax(0, 1fr); } }

        .bot-card {
            background: rgb(255 255 255 / 1); border: 1px solid rgb(228 228 231 / 1);
            border-radius: .75rem; padding: .9rem 1rem;
        }
        .dark .bot-card { background: rgb(24 24 27 / 1); border-color: rgb(39 39 42 / 1); }

        .bot-card__label { font-size: .75rem; color: rgb(113 113 122); letter-spacing: .01em; }
        .bot-card__value { font-size: 1.75rem; font-weight: 700; line-height: 1.15; margin-top: .15rem; }
        .bot-card__hint  { font-size: .7rem; color: rgb(113 113 122); margin-top: .2rem; }

        .bot-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
                   padding: .45rem 0; border-bottom: 1px solid rgb(244 244 245); font-size: .8rem; }
        .dark .bot-row { border-color: rgb(39 39 42); }
        .bot-row:last-child { border-bottom: 0; }
        .bot-row__main { min-width: 0; }
        .bot-row__title { font-weight: 600; }
        .bot-row__sub { color: rgb(113 113 122); font-size: .72rem;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bot-row__meta { flex-shrink: 0; text-align: right; color: rgb(113 113 122); font-size: .72rem; }

        .bot-bar { height: .4rem; border-radius: 999px; background: rgb(228 228 231); overflow: hidden; }
        .dark .bot-bar { background: rgb(39 39 42); }
        .bot-bar__fill { height: 100%; border-radius: 999px; background: rgb(239 68 68); }

        .bot-pill { display: inline-block; padding: .1rem .45rem; border-radius: .35rem;
                    font-size: .68rem; font-weight: 600; }
    </style>

    {{-- Durum kartları --}}
    <div wire:poll.60s class="bot-grid bot-grid--4">
        @foreach ($stats as $stat)
            @php
                $renk = match ($stat['tone']) {
                    'warning' => '#d97706',
                    'danger' => '#dc2626',
                    'success' => '#16a34a',
                    'info' => '#2563eb',
                    default => 'inherit',
                };
            @endphp
            <div class="bot-card">
                <div class="bot-card__label">{{ $stat['label'] }}</div>
                <div class="bot-card__value" style="color: {{ $renk }}">{{ $stat['value'] }}</div>
                <div class="bot-card__hint">{{ $stat['hint'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Yayın planı + son çalışmalar --}}
    <div class="bot-grid bot-grid--2" style="margin-top: 1rem">

        <x-filament::section compact>
            <x-slot name="heading">Bugünün yayın planı</x-slot>

            @php $percent = $quota['target'] > 0 ? min(100, round($quota['published'] / $quota['target'] * 100)) : 0; @endphp

            <div class="bot-grid bot-grid--4" style="gap:.5rem">
                <div>
                    <div class="bot-card__label">Yayınlanan</div>
                    <div style="font-size:1.35rem;font-weight:700">{{ $quota['published'] }}</div>
                </div>
                <div>
                    <div class="bot-card__label">Günlük tavan</div>
                    <div style="font-size:1.35rem;font-weight:700">{{ $quota['target'] }}</div>
                </div>
                <div>
                    <div class="bot-card__label">Eksik</div>
                    <div style="font-size:1.35rem;font-weight:700;color:{{ $quota['deficit'] > 0 ? '#d97706' : '#16a34a' }}">
                        {{ $quota['deficit'] }}
                    </div>
                </div>
                <div>
                    <div class="bot-card__label">Sıradaki yayın</div>
                    <div style="font-size:1.35rem;font-weight:700">{{ $nextSlot ?? '—' }}</div>
                </div>
            </div>

            <div style="margin-top:.7rem">
                <div class="bot-bar"><div class="bot-bar__fill" style="width: {{ $percent }}%"></div></div>
                <div class="bot-card__hint">
                    @if (($quota['mode'] ?? 'interval') === 'slots')
                        {{ $quota['slots_total'] }} yayın saatinden {{ $quota['slots_passed'] }} tanesi geçti.
                        Onaylanan haberler bu saatlere yayılarak çıkar.
                    @else
                        Akış kipi: 15 dakikada bir kontrol edilir, sırada haber varsa
                        turda {{ $quota['per_run'] ?? 1 }} tane yayınlanır.
                        @unless ($quota['active'] ?? true) Şu an yayın penceresi dışında. @endunless
                    @endif
                </div>
            </div>
        </x-filament::section>

        <x-filament::section compact>
            <x-slot name="heading">Son çalışmalar</x-slot>

            @forelse ($runs as $run)
                @php
                    $labels = ['crawl' => 'Tarama', 'curate' => 'Editör', 'process' => 'İşleme',
                               'publish' => 'Yayın', 'cleanup' => 'Temizlik'];
                    $ozet = collect($run->stats ?? [])
                        ->filter(fn ($v) => is_numeric($v) && $v > 0)
                        ->map(fn ($v, $k) => str_replace('_', ' ', $k) . ': ' . $v)
                        ->implode(' · ');
                @endphp
                <div class="bot-row">
                    <div class="bot-row__main">
                        <div class="bot-row__title">{{ $labels[$run->command] ?? $run->command }}</div>
                        <div class="bot-row__sub">{{ $ozet ?: 'değişiklik yok' }}</div>
                    </div>
                    <div class="bot-row__meta">
                        {{ $run->started_at?->diffForHumans(short: true) }}
                        @if ($run->duration !== null)<br>{{ abs($run->duration) }} sn @endif
                    </div>
                </div>
            @empty
                <div class="bot-card__hint">Bot henüz çalışmadı.</div>
            @endforelse
        </x-filament::section>
    </div>

    {{-- Kaynak sağlığı + son yayınlananlar --}}
    <div class="bot-grid bot-grid--2" style="margin-top: 1rem">

        <x-filament::section compact>
            <x-slot name="heading">Kaynak sağlığı</x-slot>

            @if ($brokenSources->isNotEmpty())
                @foreach ($brokenSources as $source)
                    <div class="bot-row">
                        <div class="bot-row__main">
                            <a href="{{ \App\Filament\Resources\BotSourceResource::getUrl('edit', ['record' => $source]) }}"
                               class="bot-row__title" style="color:#dc2626">{{ $source->name }}</a>
                            <div class="bot-row__sub">{{ $source->last_error }}</div>
                        </div>
                        <div class="bot-row__meta">{{ $source->consecutive_errors }} hata</div>
                    </div>
                @endforeach
                <div class="bot-card__hint" style="margin-top:.5rem">
                    Kurum sitesi şablonunu değiştirmiş olabilir. Kaynak satırındaki
                    "Selektörleri yenile" çoğu durumda çözer.
                </div>
            @else
                <div class="bot-row">
                    <div class="bot-row__main">
                        <div class="bot-row__title" style="color:#16a34a">Hata veren kaynak yok</div>
                        <div class="bot-row__sub">{{ $topSources->count() }} kaynak haber üretiyor</div>
                    </div>
                </div>
            @endif

            @foreach ($topSources->take(5) as $source)
                <div class="bot-row">
                    <div class="bot-row__main">
                        <div class="bot-row__title" style="font-weight:500">{{ $source->name }}</div>
                    </div>
                    <div class="bot-row__meta">{{ $source->fetched_total }} haber</div>
                </div>
            @endforeach
        </x-filament::section>

        <x-filament::section compact>
            <x-slot name="heading">Son yayınlananlar</x-slot>

            @forelse ($latest as $draft)
                <div class="bot-row">
                    <div class="bot-row__main">
                        <div class="bot-row__title">{{ \Illuminate\Support\Str::limit($draft->title, 60) }}</div>
                        <div class="bot-row__sub">{{ $draft->source?->name }}</div>
                    </div>
                    <div class="bot-row__meta">
                        @if ($draft->is_headline)
                            <span class="bot-pill" style="background:#dc2626;color:#fff">MANŞET</span>
                        @elseif ($draft->is_featured)
                            <span class="bot-pill" style="background:#d97706;color:#fff">öne çıkan</span>
                        @endif
                        <br>{{ $draft->score ?? '—' }} puan
                    </div>
                </div>
            @empty
                <div class="bot-card__hint">Henüz haber yayınlanmadı.</div>
            @endforelse
        </x-filament::section>
    </div>

</x-filament-panels::page>
