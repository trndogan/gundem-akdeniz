@extends('layouts.app')

@section('title', 'Belediye Başkanları - Akdeniz Bölgesi | Gündem Akdeniz')
@section('meta_description', 'Akdeniz Bölgesi belediye başkanları rehberi. Antalya, Mersin, Adana, Hatay ve çevre illerin başkanları, partileri ve profilleri.')
@section('og_title', 'Belediye Başkanları - Gündem Akdeniz')
@section('canonical', route('mayor.index'))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">Belediye Başkanları</span>
</nav>

{{-- Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Akdeniz Başkanlar Rehberi</h1>
    <p class="text-slate-600 mt-2">Akdeniz Bölgesi belediye başkanları, profilleri ve haberleri</p>
</div>

{{-- Trend: En Çok Haberi Yapılan Başkanlar --}}
@if($trending->isNotEmpty() && !request()->hasAny(['party', 'city']))
<div class="mb-10">
    <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-primary text-xl">trending_up</span>
        <h2 class="text-lg font-extrabold text-slate-900">Gündemde</h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        @foreach($trending as $index => $tm)
        <a href="{{ $tm->url }}" class="group relative bg-white border border-gray-200 rounded-xl p-4 text-center hover:shadow-lg hover:border-red-200 transition-all">
            <div class="absolute top-2 left-2 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black {{ $index === 0 ? 'bg-amber-400 text-white' : ($index === 1 ? 'bg-slate-400 text-white' : ($index === 2 ? 'bg-amber-700 text-white' : 'bg-slate-100 text-slate-500')) }}">
                {{ $index + 1 }}
            </div>
            <div class="w-16 h-16 rounded-full overflow-hidden border-2 mx-auto mb-3" style="border-color: {{ $tm->party?->primary_color ?? '#e2e8f0' }}">
                @if($tm->has_photo)
                <img src="{{ $tm->photo_display_url }}" alt="{{ $tm->name }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-white font-black text-lg" style="background-color: {{ $tm->party?->primary_color ?? '#64748b' }}">
                    {{ $tm->initials }}
                </div>
                @endif
            </div>
            <h3 class="font-bold text-sm text-slate-900 group-hover:text-primary transition-colors leading-tight">{{ $tm->name }}</h3>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $tm->city?->name }}</p>
            <div class="mt-2 inline-flex items-center gap-1 bg-slate-50 rounded-full px-2 py-0.5">
                <span class="material-symbols-outlined text-[11px] text-primary">article</span>
                <span class="text-[11px] font-bold text-slate-600">{{ $tm->articles_count }} haber</span>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Filtreler --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        {{-- Parti Filtreleri --}}
        <div class="flex flex-wrap gap-2 flex-1">
            <a href="{{ route('mayor.index', request()->only('city')) }}"
               class="px-4 py-2 rounded-lg text-sm font-bold transition-all {{ !request('party') ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                Tümü
            </a>
            @foreach($parties as $party)
            <a href="{{ route('mayor.index', array_merge(request()->only('city'), ['party' => $party->slug])) }}"
               class="px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-1.5"
               style="{{ request('party') == $party->slug ? 'background-color:' . $party->primary_color . ';color:#fff;box-shadow:0 2px 8px ' . $party->primary_color . '40' : 'background-color:#f8fafc;color:#475569' }}">
                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ request('party') == $party->slug ? '#fff' : $party->primary_color }}"></span>
                {{ $party->short_name ?? $party->name }}
            </a>
            @endforeach
        </div>

        {{-- İl Filtresi --}}
        <div class="flex flex-wrap gap-1.5">
            @foreach($cities as $city)
            <a href="{{ route('mayor.index', array_merge(request()->only('party'), ['city' => request('city') == $city->slug ? null : $city->slug])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('city') == $city->slug ? 'bg-primary text-white' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">
                {{ $city->name }}
            </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Başkan Kartları --}}
@if($mayors->isNotEmpty())
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-12">
    @foreach($mayors as $mayor)
    <a href="{{ $mayor->url }}" class="group block bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg hover:border-red-200 transition-all duration-300">
        {{-- Parti Renk Gradient Üst Bar --}}
        <div class="h-24 relative" style="background: linear-gradient(135deg, {{ $mayor->party?->primary_color ?? '#64748b' }}, {{ $mayor->party?->secondary_color ?? '#94a3b8' }})">
            <div class="absolute inset-0 opacity-20">
                <svg class="w-full h-full" viewBox="0 0 300 100" fill="none"><circle cx="250" cy="20" r="60" fill="white"/></svg>
            </div>
            {{-- Haber Sayısı Badge --}}
            @if($mayor->articles_count > 0)
            <div class="absolute top-3 right-3 bg-white/20 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-lg flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">article</span>
                {{ $mayor->articles_count }} haber
            </div>
            @endif
        </div>

        <div class="px-5 pb-5 -mt-10 relative">
            {{-- Fotoğraf / Avatar --}}
            <div class="w-20 h-20 rounded-xl overflow-hidden border-4 border-white shadow-lg mb-3" style="background-color: {{ $mayor->party?->primary_color ?? '#e2e8f0' }}">
                @if($mayor->has_photo)
                <img src="{{ $mayor->photo_display_url }}" alt="{{ $mayor->name }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-white font-black text-2xl">
                    {{ $mayor->initials }}
                </div>
                @endif
            </div>

            {{-- İsim & Unvan --}}
            <h2 class="font-extrabold text-slate-900 group-hover:text-primary transition-colors text-lg leading-tight">{{ $mayor->name }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">{{ $mayor->title }}</p>

            {{-- Detaylar --}}
            <div class="mt-3 space-y-2 text-sm">
                @if($mayor->city || $mayor->district)
                <div class="flex items-center gap-2 text-slate-500">
                    <span class="material-symbols-outlined text-sm text-slate-300">location_on</span>
                    <span>{{ $mayor->location_text }}</span>
                </div>
                @endif
                @if($mayor->party)
                <div class="flex items-center gap-2 text-slate-500">
                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $mayor->party->primary_color }}"></span>
                    <span class="font-semibold">{{ $mayor->party->name }}</span>
                </div>
                @endif
                @if($mayor->municipality_name)
                <div class="flex items-center gap-2 text-slate-500">
                    <span class="material-symbols-outlined text-sm text-slate-300">account_balance</span>
                    <span class="truncate">{{ $mayor->municipality_name }}</span>
                </div>
                @endif
            </div>

            {{-- Profili Gör --}}
            <div class="mt-4 pt-3 border-t border-gray-100">
                <span class="text-xs font-bold text-primary group-hover:text-red-700 transition-colors flex items-center gap-1">
                    Profili İncele
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </span>
            </div>
        </div>
    </a>
    @endforeach
</div>
@else
<div class="text-center py-20 bg-white border border-gray-200 rounded-xl mb-12">
    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-slate-400 text-3xl">person_search</span>
    </div>
    <p class="text-lg font-bold text-slate-900">Başkan bulunamadı</p>
    <p class="text-sm text-slate-500 mt-1">Filtreleri değiştirmeyi deneyin.</p>
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
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Belediye Başkanları'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

@endsection
