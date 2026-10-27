@extends('layouts.app')

@section('title', $city->name . ' Nöbetçi Eczaneler ' . now()->format('d F Y') . ' - Gündem Akdeniz')
@section('meta_description', 'Bugün ' . $city->name . ' nöbetçi eczane listesi (' . now()->translatedFormat('d F Y, l') . '). ' . $pharmacies->count() . ' nöbetçi eczanenin adresi, telefonu ve harita konumu.')
@section('og_title', $city->name . ' Nöbetçi Eczaneler - ' . now()->translatedFormat('d F Y'))
@section('canonical', route('pharmacy.show', $city->slug))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="{{ route('pharmacy.index') }}" class="hover:text-primary transition-colors">Nöbetçi Eczaneler</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">{{ $city->name }}</span>
</nav>

{{-- Hero Banner --}}
<div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-primary via-red-600 to-red-700 mb-8">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 400 200" fill="none"><circle cx="350" cy="30" r="120" fill="white"/><circle cx="50" cy="180" r="80" fill="white"/></svg>
    </div>
    <div class="relative px-6 py-8 md:px-10 md:py-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-11 h-11 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-2xl">local_pharmacy</span>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">{{ $city->name }} Nöbetçi Eczaneler</h1>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-white/80 text-sm">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">calendar_today</span>
                        {{ now()->translatedFormat('d F Y, l') }}
                    </span>
                    <span class="w-1 h-1 rounded-full bg-white/40"></span>
                    <span class="font-bold text-white">{{ $pharmacies->count() }} eczane</span>
                </div>
            </div>

            {{-- Şehir Seçici --}}
            <div class="flex flex-wrap gap-1.5">
                @foreach($allCities as $c)
                    <a href="{{ route('pharmacy.show', $c->slug) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $c->id === $city->id ? 'bg-white text-primary shadow-md' : 'bg-white/15 text-white hover:bg-white/25' }}">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($pharmacies->isEmpty())
<div class="text-center py-20">
    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-slate-400 text-3xl">local_pharmacy</span>
    </div>
    <p class="text-lg font-bold text-slate-900">Henüz nöbetçi eczane verisi bulunmuyor</p>
    <p class="text-sm text-slate-500 mt-1">Veriler her gece otomatik olarak güncellenir.</p>
</div>
@else

{{-- İlçe Filtreleme --}}
@php $districts = $pharmacies->pluck('district')->filter()->unique()->sort()->values(); @endphp
@if($districts->count() > 1)
<div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2" x-data="{ active: 'all' }">
    <button @click="active = 'all'" :class="active === 'all' ? 'bg-primary text-white' : 'bg-white text-slate-600 border border-gray-200 hover:border-slate-300'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0" onclick="filterDistrict('all')">
        Tümü <span class="ml-1 opacity-60">{{ $pharmacies->count() }}</span>
    </button>
    @foreach($districts as $district)
    <button @click="active = '{{ $district }}'" :class="active === '{{ $district }}' ? 'bg-primary text-white' : 'bg-white text-slate-600 border border-gray-200 hover:border-slate-300'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0" onclick="filterDistrict('{{ $district }}')">
        {{ $district }} <span class="ml-1 opacity-60">{{ $pharmacies->where('district', $district)->count() }}</span>
    </button>
    @endforeach
</div>
@endif

{{-- Eczane Listesi --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-10" id="pharmacy-grid">
    @foreach($pharmacies as $pharmacy)
    <div class="pharmacy-card group bg-white border border-gray-200 rounded-xl overflow-hidden hover:border-red-200 hover:shadow-lg transition-all duration-300" data-district="{{ $pharmacy->district }}">
        <div class="p-5">
            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <h2 class="font-bold text-slate-900 text-[15px] leading-tight group-hover:text-primary transition-colors">{{ $pharmacy->name }}</h2>
                    @if($pharmacy->district)
                    <span class="inline-block mt-1 text-[10px] font-bold text-primary bg-red-50 px-2 py-0.5 rounded uppercase tracking-wider">{{ $pharmacy->district }}</span>
                    @endif
                </div>
                <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0 group-hover:bg-red-100 transition-colors">
                    <span class="material-symbols-outlined text-primary text-lg">local_pharmacy</span>
                </div>
            </div>

            {{-- Adres --}}
            @if($pharmacy->address)
            <div class="flex items-start gap-2 text-[13px] text-slate-500 leading-relaxed">
                <span class="material-symbols-outlined text-sm text-slate-300 mt-0.5 shrink-0">location_on</span>
                <p>{{ $pharmacy->address }}</p>
            </div>
            @endif
        </div>

        {{-- Aksiyonlar --}}
        <div class="flex border-t border-gray-100">
            @if($pharmacy->phone)
            <a href="tel:{{ $pharmacy->phone }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold text-primary hover:bg-red-50 transition-colors border-r border-gray-100">
                <span class="material-symbols-outlined text-[18px]">call</span>
                <span>{{ $pharmacy->phone }}</span>
            </a>
            @endif
            @if($pharmacy->latitude && $pharmacy->longitude)
            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $pharmacy->latitude }},{{ $pharmacy->longitude }}" target="_blank" rel="noopener noreferrer"
               class="{{ $pharmacy->phone ? 'w-[100px]' : 'flex-1' }} flex items-center justify-center gap-1.5 py-3 text-sm font-bold text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                <span class="material-symbols-outlined text-[18px]">directions</span>
                <span>Yol Tarifi</span>
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endif

@include('partials.ad', ['position' => 'content_between'])

{{-- İlçe Filtreleme JS --}}
<script>
function filterDistrict(district) {
    document.querySelectorAll('.pharmacy-card').forEach(function(card) {
        if (district === 'all' || card.dataset.district === district) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Nöbetçi Eczaneler', 'item' => route('pharmacy.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $city->name . ' Nöbetçi Eczaneler'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

{{-- Pharmacy JSON-LD --}}
@if($pharmacies->isNotEmpty())
<script type="application/ld+json">
@php
$pharmacyItems = $pharmacies->map(function ($p) use ($city) {
    $item = [
        '@type' => 'Pharmacy',
        'name' => $p->name,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $p->address,
            'addressLocality' => $p->district ?? $city->name,
            'addressRegion' => $city->name,
            'addressCountry' => 'TR',
        ],
    ];
    if ($p->phone) {
        $item['telephone'] = $p->phone;
    }
    if ($p->latitude && $p->longitude) {
        $item['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $p->latitude,
            'longitude' => (float) $p->longitude,
        ];
    }
    return $item;
});

echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $city->name . ' Nöbetçi Eczaneler - ' . now()->translatedFormat('d F Y'),
    'numberOfItems' => $pharmacies->count(),
    'itemListElement' => $pharmacyItems->values()->map(function ($item, $index) {
        return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => $item,
        ];
    }),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>
@endif

@endsection
