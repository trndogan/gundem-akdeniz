@extends('layouts.app')

@section('title', $city->seo_title ?? $city->name . ' Haberleri - Gündem Akdeniz')
@section('meta_description', $city->seo_description ?? $city->name . ' ilinden en güncel haberler. Gündem Akdeniz - Akdeniz Bölgesi\'nin haber portalı.')
@section('og_title', $city->seo_title ?? $city->name . ' Haberleri - Gündem Akdeniz')
@section('canonical', url('/' . $city->slug))
@if($articles->currentPage() > 1)
@section('noindex', true)
@endif

@section('content')

<div class="mb-8">
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-4">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-slate-900 font-semibold">{{ $city->name }}</span>
    </nav>
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $city->name }} Haberleri</h1>
    <p class="text-slate-600 mt-2">{{ $city->name }} ilinden en güncel haberler ve gelişmeler</p>
</div>

{{-- Hava Durumu + Nöbetçi Eczane Kompakt Widget --}}
@php
    $weather = \App\Models\WeatherForecast::byCity($city->id)->today()->first();
    $pharmacies = \App\Models\Pharmacy::byCity($city->id)->today()->take(2)->get();
@endphp
@if($weather || $pharmacies->isNotEmpty())
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
    @if($weather)
    <a href="{{ route('weather.show', $city->slug) }}" class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 hover:border-blue-200 hover:shadow-sm transition-all group">
        <span class="text-2xl">{{ $weather->icon_emoji }}</span>
        <div class="flex-1 min-w-0">
            <span class="text-lg font-extrabold text-slate-900">{{ intval($weather->temp_current ?? $weather->temp_max ?? 0) }}°C</span>
            <span class="text-xs text-slate-500 capitalize ml-1">{{ $weather->description }}</span>
        </div>
        <span class="text-[11px] font-bold text-blue-600 group-hover:text-blue-700 shrink-0">Detay →</span>
    </a>
    @endif
    @if($pharmacies->isNotEmpty())
    <a href="{{ route('pharmacy.show', $city->slug) }}" class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 hover:border-red-200 hover:shadow-sm transition-all group">
        <span class="material-symbols-outlined text-primary text-xl">local_pharmacy</span>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-primary uppercase tracking-wide leading-none">Nöbetçi Eczane</p>
            <p class="text-sm font-bold text-slate-900 mt-0.5 truncate">{{ $pharmacies->first()->name }}</p>
        </div>
        <span class="text-[11px] font-bold text-primary group-hover:text-red-700 shrink-0">Tümü →</span>
    </a>
    @endif
</div>
@endif

<div id="ga-liste" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    @forelse($articles as $article)
    <a href="{{ $article->url }}" class="group cursor-pointer block">
        <div class="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm">
            <img src="{{ $article->image_display_url }}" alt="{{ $article->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
            <div class="absolute top-3 left-3 bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase">{{ $article->category->name }}</div>
        </div>
        <h4 class="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
            {{ $article->title }}
        </h4>
        @if($article->excerpt)
        <p class="text-slate-600 text-sm line-clamp-2">{{ $article->excerpt }}</p>
        @endif
        <div class="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
            <span>{{ $article->category->name }}</span>
            <span>{{ $article->published_at->diffForHumans() }}</span>
        </div>
    </a>
    @empty
    <div class="col-span-4 text-center py-16 text-slate-500">
        <span class="material-symbols-outlined text-5xl mb-3 block">article</span>
        <p class="text-lg">Bu şehir için henüz haber bulunmuyor.</p>
    </div>
    @endforelse
</div>

@include('partials.sonsuz-kaydirma', ['liste' => 'ga-liste', 'sayfa' => $articles])

@include('partials.ad', ['position' => 'content_between'])

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $city->name],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

@endsection
