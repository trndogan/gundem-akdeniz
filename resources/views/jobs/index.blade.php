@extends('layouts.app')

@section('title', 'İş İlanları - Akdeniz Bölgesi | Gündem Akdeniz')
@section('meta_description', 'Akdeniz Bölgesi iş ilanları. Antalya, Mersin, Adana, Hatay ve çevre illerde güncel iş fırsatları.')
@section('og_title', 'İş İlanları - Gündem Akdeniz')
@section('canonical', route('job.index'))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">İş İlanları</span>
</nav>

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">İş İlanları</h1>
        <p class="text-slate-600 mt-1">Akdeniz Bölgesi'ndeki güncel iş fırsatları</p>
    </div>
    <a href="{{ route('job.create') }}" class="inline-flex items-center gap-2 bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-red-700 transition-colors shrink-0">
        <span class="material-symbols-outlined text-lg">add_circle</span>
        Ücretsiz İlan Ver
    </a>
</div>

{{-- Başarı Mesajı --}}
@if(session('success'))
<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center gap-3">
    <span class="material-symbols-outlined text-green-600 text-xl">check_circle</span>
    <p class="text-sm font-semibold text-green-700">{{ session('success') }}</p>
</div>
@endif

{{-- Filtreler --}}
<form method="GET" action="{{ route('job.index') }}" class="flex flex-wrap gap-3 mb-8 bg-white border border-gray-200 rounded-xl p-4">
    <select name="city" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
        <option value="">Tüm Şehirler</option>
        @foreach($cities as $city)
        <option value="{{ $city->slug }}" {{ request('city') == $city->slug ? 'selected' : '' }}>{{ $city->name }}</option>
        @endforeach
    </select>
    <select name="type" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
        <option value="">Tüm Türler</option>
        <option value="full-time" {{ request('type') == 'full-time' ? 'selected' : '' }}>Tam Zamanlı</option>
        <option value="part-time" {{ request('type') == 'part-time' ? 'selected' : '' }}>Yarı Zamanlı</option>
        <option value="contract" {{ request('type') == 'contract' ? 'selected' : '' }}>Sözleşmeli</option>
        <option value="internship" {{ request('type') == 'internship' ? 'selected' : '' }}>Staj</option>
        <option value="freelance" {{ request('type') == 'freelance' ? 'selected' : '' }}>Freelance</option>
    </select>
    @if($sectors->isNotEmpty())
    <select name="sector" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
        <option value="">Tüm Sektörler</option>
        @foreach($sectors as $sector)
        <option value="{{ $sector }}" {{ request('sector') == $sector ? 'selected' : '' }}>{{ $sector }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="bg-slate-900 text-white text-sm font-bold px-5 py-2 rounded-lg hover:bg-slate-800 transition-colors">
        <span class="material-symbols-outlined text-sm align-middle mr-1">search</span>Filtrele
    </button>
    @if(request()->hasAny(['city', 'type', 'sector']))
    <a href="{{ route('job.index') }}" class="text-sm text-slate-500 hover:text-primary px-3 py-2 transition-colors">Temizle</a>
    @endif
</form>

{{-- İlan Listesi --}}
<div class="space-y-3 mb-12">
    @forelse($jobs as $job)
    <a href="{{ $job->url }}" class="group block bg-white border border-gray-200 rounded-xl p-5 hover:border-red-200 hover:shadow-md transition-all">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    @if($job->is_featured)
                    <span class="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded uppercase">Öne Çıkan</span>
                    @endif
                    @if($job->is_expired)
                    <span class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Süresi Doldu</span>
                    @endif
                    <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase">{{ $job->employment_type_label }}</span>
                </div>
                <h2 class="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">{{ $job->title }}</h2>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-slate-400">business</span>
                        {{ $job->company_name }}
                    </span>
                    @if($job->city)
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-slate-400">location_on</span>
                        {{ $job->city->name }}{{ $job->district ? ', ' . $job->district : '' }}
                    </span>
                    @endif
                    @if($job->sector)
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-slate-400">category</span>
                        {{ $job->sector }}
                    </span>
                    @endif
                </div>
            </div>
            <div class="text-right shrink-0">
                @if($job->salary_text)
                <p class="text-lg font-extrabold text-slate-900">{{ $job->salary_text }}</p>
                @endif
                @if($job->deadline && !$job->is_expired)
                <p class="text-xs text-slate-400 mt-1">Son: {{ $job->deadline->translatedFormat('d M Y') }}</p>
                @endif
                <p class="text-[11px] text-slate-400 mt-1">{{ $job->created_at->diffForHumans() }}</p>
            </div>
        </div>
    </a>
    @empty
    <div class="text-center py-20 bg-white border border-gray-200 rounded-xl">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-slate-400 text-3xl">work</span>
        </div>
        <p class="text-lg font-bold text-slate-900">Henüz ilan bulunmuyor</p>
        <p class="text-sm text-slate-500 mt-1 mb-4">İlk ilanı siz verin!</p>
        <a href="{{ route('job.create') }}" class="inline-flex items-center gap-2 bg-primary text-white text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-red-700 transition-colors">
            <span class="material-symbols-outlined text-lg">add_circle</span>
            Ücretsiz İlan Ver
        </a>
    </div>
    @endforelse
</div>

{{ $jobs->withQueryString()->links() }}

@include('partials.ad', ['position' => 'content_between'])

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'İş İlanları'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

@endsection
