@extends('layouts.app')

@section('title', 'Nöbetçi Eczaneler ' . now()->translatedFormat('d F Y') . ' - Gündem Akdeniz')
@section('meta_description', 'Akdeniz Bölgesi nöbetçi eczane listesi (' . now()->translatedFormat('d F Y') . '). Antalya, Mersin, Adana, Hatay, Isparta, Burdur, Osmaniye ve Kahramanmaraş nöbetçi eczaneleri.')
@section('og_title', 'Nöbetçi Eczaneler - Gündem Akdeniz')
@section('canonical', route('pharmacy.index'))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">Nöbetçi Eczaneler</span>
</nav>

{{-- Hero --}}
<div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-primary via-red-600 to-red-700 mb-10">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 400 200" fill="none"><circle cx="350" cy="30" r="120" fill="white"/><circle cx="50" cy="180" r="80" fill="white"/></svg>
    </div>
    <div class="relative px-6 py-10 md:px-10 md:py-12 text-center">
        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-white text-3xl">local_pharmacy</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-2">Nöbetçi Eczaneler</h1>
        <p class="text-white/80 text-base md:text-lg max-w-xl mx-auto">Akdeniz Bölgesi'ndeki tüm illerin güncel nöbetçi eczane bilgilerine anında ulaşın.</p>
        <div class="flex items-center justify-center gap-2 mt-4 text-white/70 text-sm">
            <span class="material-symbols-outlined text-sm">calendar_today</span>
            <span>{{ now()->translatedFormat('d F Y, l') }}</span>
        </div>
    </div>
</div>

{{-- Şehir Kartları --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
    @foreach($cities as $city)
    @php
        $count = \App\Models\Pharmacy::byCity($city->id)->today()->count();
    @endphp
    <a href="{{ route('pharmacy.show', $city->slug) }}" class="group relative bg-white border border-gray-200 rounded-xl p-5 hover:border-red-200 hover:shadow-lg transition-all duration-300 overflow-hidden">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-50 group-hover:bg-red-100 rounded-xl flex items-center justify-center transition-colors shrink-0">
                <span class="material-symbols-outlined text-primary text-2xl">local_pharmacy</span>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">{{ $city->name }}</h2>
                <p class="text-sm text-slate-500">
                    @if($count > 0)
                        <span class="font-semibold text-primary">{{ $count }}</span> nöbetçi eczane
                    @else
                        Nöbetçi eczaneler
                    @endif
                </p>
            </div>
            <span class="material-symbols-outlined text-slate-300 group-hover:text-primary group-hover:translate-x-1 transition-all">arrow_forward</span>
        </div>
    </a>
    @endforeach
</div>

@include('partials.ad', ['position' => 'content_between'])

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Nöbetçi Eczaneler'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

@endsection
