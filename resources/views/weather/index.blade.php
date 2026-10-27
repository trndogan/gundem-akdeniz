@extends('layouts.app')

@section('title', $seoTitle ?: 'Hava Durumu ' . now()->translatedFormat('d F Y') . ' - Gündem Akdeniz')
@section('meta_description', $seoDescription ?: 'Akdeniz Bölgesi hava durumu. Antalya, Mersin, Adana, Hatay, Isparta, Burdur, Osmaniye ve Kahramanmaraş güncel hava durumu ve 7 günlük tahmin.')
@section('og_title', $seoTitle ?: 'Hava Durumu - Gündem Akdeniz')
@section('canonical', route('weather.index'))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">Hava Durumu</span>
</nav>

{{-- Hero --}}
<div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-sky-500 via-blue-500 to-indigo-600 mb-10">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 400 200" fill="none"><circle cx="320" cy="50" r="100" fill="white"/><circle cx="80" cy="160" r="60" fill="white"/></svg>
    </div>
    <div class="relative px-6 py-10 md:px-10 md:py-12 text-center">
        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
            <span class="text-3xl">🌤️</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-2">Hava Durumu</h1>
        <p class="text-white/80 text-base md:text-lg max-w-xl mx-auto">Akdeniz Bölgesi'ndeki tüm illerin güncel hava durumu ve 7 günlük tahminleri.</p>
        <div class="flex items-center justify-center gap-2 mt-4 text-white/70 text-sm">
            <span class="material-symbols-outlined text-sm">calendar_today</span>
            <span>{{ now()->translatedFormat('d F Y, l') }}</span>
        </div>
    </div>
</div>

{{-- Şehir Kartları --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
    @foreach($cities as $city)
    @php $w = $todayWeather[$city->id] ?? null; @endphp
    <a href="{{ route('weather.show', $city->slug) }}" class="group bg-white border border-gray-200 rounded-xl p-5 hover:border-blue-200 hover:shadow-lg transition-all duration-300 overflow-hidden">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $city->name }}</h2>
            @if($w)
            <span class="text-3xl">{{ $w->icon_emoji }}</span>
            @endif
        </div>
        @if($w)
        <div class="flex items-end justify-between">
            <div>
                <span class="text-3xl font-extrabold text-slate-900">{{ intval($w->temp_current ?? $w->temp_max ?? 0) }}°</span>
                <p class="text-xs text-slate-500 capitalize mt-1">{{ $w->description }}</p>
            </div>
            <div class="text-right text-xs text-slate-400">
                <p>↑ {{ intval($w->temp_max ?? 0) }}° ↓ {{ intval($w->temp_min ?? 0) }}°</p>
                <p class="mt-0.5">💧 %{{ $w->humidity ?? '-' }}</p>
            </div>
        </div>
        @else
        <p class="text-sm text-slate-400">Veri bekleniyor...</p>
        @endif
    </a>
    @endforeach
</div>

@if($weatherContent)
<div class="bg-slate-50 border border-gray-200 rounded-xl p-6 mb-10 prose prose-slate max-w-none prose-headings:font-extrabold prose-headings:text-slate-900 prose-a:text-primary">
    {!! $weatherContent !!}
</div>
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
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Hava Durumu'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

@endsection
