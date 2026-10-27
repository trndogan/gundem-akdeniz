@extends('layouts.app')

@section('title', $city->weather_seo_title ?? $city->name . ' Hava Durumu ' . now()->translatedFormat('d F Y') . ' - Gündem Akdeniz')
@section('meta_description', $city->weather_seo_description ?? 'Bugün ' . $city->name . ' hava durumu: ' . ($today ? ($today->description ?? '') . ', sıcaklık ' . intval($today->temp_current ?? $today->temp_max ?? 0) . '°C' : '') . '. 7 günlük detaylı hava tahmini.')
@section('og_title', $city->weather_seo_title ?? $city->name . ' Hava Durumu - ' . now()->translatedFormat('d F Y'))
@section('canonical', route('weather.show', $city->slug))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="{{ route('weather.index') }}" class="hover:text-primary transition-colors">Hava Durumu</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">{{ $city->name }}</span>
</nav>

{{-- Hero --}}
<div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-sky-500 via-blue-500 to-indigo-600 mb-8">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 400 200" fill="none"><circle cx="320" cy="50" r="100" fill="white"/><circle cx="80" cy="160" r="60" fill="white"/></svg>
    </div>
    <div class="relative px-6 py-8 md:px-10 md:py-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                @if($today)
                <div class="flex items-center gap-4 mb-3">
                    <span class="text-5xl md:text-6xl">{{ $today->icon_emoji }}</span>
                    <div>
                        <div class="text-4xl md:text-5xl font-extrabold text-white tracking-tight">
                            {{ intval($today->temp_current ?? $today->temp_max ?? 0) }}°C
                        </div>
                        <p class="text-white/80 text-sm capitalize">{{ $today->description }}</p>
                    </div>
                </div>
                @endif
                <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">{{ $city->name }} Hava Durumu</h1>
                <div class="flex items-center gap-4 mt-2 text-white/70 text-sm">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">calendar_today</span>
                        {{ now()->translatedFormat('d F Y, l') }}
                    </span>
                    @if($today)
                    <span class="w-1 h-1 rounded-full bg-white/40"></span>
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">water_drop</span>
                        %{{ $today->humidity }}
                    </span>
                    <span class="w-1 h-1 rounded-full bg-white/40"></span>
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">air</span>
                        {{ $today->wind_speed }} km/s
                    </span>
                    @endif
                </div>
            </div>

            {{-- Şehir Seçici --}}
            <div class="flex flex-wrap gap-1.5">
                @foreach($allCities as $c)
                    <a href="{{ route('weather.show', $c->slug) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $c->id === $city->id ? 'bg-white text-blue-700 shadow-md' : 'bg-white/15 text-white hover:bg-white/25' }}">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($forecasts->isEmpty())
<div class="text-center py-20">
    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-slate-400 text-3xl">cloud_off</span>
    </div>
    <p class="text-lg font-bold text-slate-900">Henüz hava durumu verisi bulunmuyor</p>
    <p class="text-sm text-slate-500 mt-1">Veriler saatlik olarak güncellenir.</p>
</div>
@else

{{-- Bugün Detay --}}
@if($today)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
        <span class="material-symbols-outlined text-blue-500 text-2xl mb-1">thermostat</span>
        <p class="text-2xl font-extrabold text-slate-900">{{ intval($today->temp_max ?? 0) }}°</p>
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">En Yüksek</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
        <span class="material-symbols-outlined text-sky-400 text-2xl mb-1">ac_unit</span>
        <p class="text-2xl font-extrabold text-slate-900">{{ intval($today->temp_min ?? 0) }}°</p>
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">En Düşük</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
        <span class="material-symbols-outlined text-cyan-500 text-2xl mb-1">water_drop</span>
        <p class="text-2xl font-extrabold text-slate-900">%{{ $today->humidity ?? '-' }}</p>
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Nem</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
        <span class="material-symbols-outlined text-gray-500 text-2xl mb-1">air</span>
        <p class="text-2xl font-extrabold text-slate-900">{{ $today->wind_speed ?? '-' }}</p>
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Rüzgar (km/s)</p>
    </div>
</div>
@endif

{{-- 7 Günlük Tahmin --}}
<h2 class="text-xl font-extrabold text-slate-900 mb-4">7 Günlük Hava Tahmini</h2>
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-10">
    @foreach($forecasts as $index => $forecast)
    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center {{ $index === 0 ? 'ring-2 ring-blue-500/30 border-blue-200' : '' }} hover:shadow-md transition-shadow">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
            {{ $index === 0 ? 'Bugün' : $forecast->forecast_date->translatedFormat('D') }}
        </p>
        <span class="text-3xl block mb-2">{{ $forecast->icon_emoji }}</span>
        <div class="flex items-center justify-center gap-2 mb-1">
            <span class="text-lg font-extrabold text-slate-900">{{ intval($forecast->temp_max ?? 0) }}°</span>
            <span class="text-sm text-slate-400">{{ intval($forecast->temp_min ?? 0) }}°</span>
        </div>
        <p class="text-[11px] text-slate-500 capitalize leading-tight">{{ $forecast->description }}</p>
    </div>
    @endforeach
</div>

{{-- Admin'den girilen SEO içeriği veya otomatik dinamik metin --}}
@if($city->weather_content)
<div class="bg-slate-50 border border-gray-200 rounded-xl p-6 mb-10 prose prose-slate max-w-none prose-headings:font-extrabold prose-headings:text-slate-900 prose-a:text-primary">
    {!! $city->weather_content !!}
</div>
@else
<div class="bg-slate-50 border border-gray-200 rounded-xl p-6 mb-10">
    <h2 class="text-lg font-bold text-slate-900 mb-3">{{ $city->name }} Hava Durumu Özeti</h2>
    <p class="text-slate-600 leading-relaxed">
        Bugün {{ $city->name }}'da hava {{ strtolower($today->description ?? 'değişken') }} ve sıcaklık {{ intval($today->temp_current ?? $today->temp_max ?? 0) }} derece.
        @if($forecasts->count() > 1)
        Yarın için beklenen en yüksek sıcaklık {{ intval($forecasts[1]->temp_max ?? 0) }} derece, en düşük sıcaklık ise {{ intval($forecasts[1]->temp_min ?? 0) }} derece olacak.
        @endif
        @php
            $avgMax = $forecasts->avg('temp_max');
        @endphp
        Haftalık ortalama en yüksek sıcaklık {{ intval($avgMax) }} derece civarında seyredecek.
        {{ $city->name }} ve Akdeniz bölgesi genelinde güncel hava durumu bilgilerine Gündem Akdeniz üzerinden ulaşabilirsiniz.
    </p>
</div>
@endif

@endif

@include('partials.ad', ['position' => 'content_between'])

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Hava Durumu', 'item' => route('weather.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $city->name . ' Hava Durumu'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

{{-- WebPage + WeatherForecast JSON-LD --}}
@if($today)
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $city->name . ' Hava Durumu - ' . now()->translatedFormat('d F Y'),
    'description' => 'Bugün ' . $city->name . ' hava durumu: ' . ($today->description ?? '') . ', sıcaklık ' . intval($today->temp_current ?? $today->temp_max ?? 0) . '°C.',
    'mainEntity' => [
        '@type' => 'City',
        'name' => $city->name,
        'containedInPlace' => [
            '@type' => 'Country',
            'name' => 'Türkiye',
        ],
    ],
    'about' => $forecasts->map(function ($f) use ($city) {
        return [
            '@type' => 'Observation',
            'name' => $city->name . ' ' . $f->forecast_date->translatedFormat('d F Y') . ' Hava Durumu',
            'observationDate' => $f->forecast_date->format('Y-m-d'),
            'measuredProperty' => 'temperature',
            'measuredValue' => intval($f->temp_max ?? 0),
            'unitCode' => 'CEL',
        ];
    })->values()->toArray(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>
@endif

@endsection
