@extends('layouts.app')

@section('title', $mayor->seo_title ?? $mayor->name . ' - ' . $mayor->title . ' | Gündem Akdeniz')
@section('meta_description', $mayor->seo_description ?? $mayor->name . ', ' . ($mayor->municipality_name ?? $mayor->location_text) . ' ' . $mayor->title . '. Biyografi, haberler ve resmi açıklamalar.')
@section('og_title', $mayor->seo_title ?? $mayor->name . ' - ' . $mayor->title)
@section('og_image', $mayor->photo_display_url ?? asset('images/og-default.jpg'))
@section('canonical', $mayor->url)

@php
    $partyColor = $mayor->party?->primary_color ?? '#dc2626';
    $partySecondary = $mayor->party?->secondary_color ?? '#991b1b';
@endphp

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="{{ route('mayor.index') }}" class="hover:text-primary transition-colors">Belediye Başkanları</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">{{ $mayor->name }}</span>
</nav>

{{-- Hero — Parti Temalı --}}
<div class="relative rounded-2xl overflow-hidden mb-8" style="background: linear-gradient(135deg, {{ $partyColor }}, {{ $partySecondary }})">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 600 300" fill="none"><circle cx="500" cy="80" r="150" fill="white"/><circle cx="100" cy="250" r="80" fill="white"/></svg>
    </div>
    <div class="relative px-6 py-8 md:px-10 md:py-10">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
            {{-- Fotoğraf --}}
            <div class="w-32 h-32 md:w-40 md:h-40 rounded-2xl overflow-hidden border-4 border-white/30 shadow-xl shrink-0">
                @if($mayor->has_photo)
                <img src="{{ $mayor->photo_display_url }}" alt="{{ $mayor->name }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full bg-white/20 flex items-center justify-center text-white font-black text-5xl">
                    {{ $mayor->initials }}
                </div>
                @endif
            </div>

            {{-- Bilgiler --}}
            <div class="text-center md:text-left flex-1">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-3">
                    @if($mayor->party)
                    <span class="bg-white/20 backdrop-blur-sm text-white text-xs font-bold px-3 py-1 rounded-lg">{{ $mayor->party->name }}</span>
                    @endif
                    @if($mayor->municipality_name)
                    <span class="bg-white/10 text-white/80 text-xs font-bold px-3 py-1 rounded-lg">{{ $mayor->municipality_name }}</span>
                    @endif
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-1">{{ $mayor->name }}</h1>
                <p class="text-white/80 text-lg">{{ $mayor->title }}{{ $mayor->location_text ? ' · ' . $mayor->location_text : '' }}</p>

                @if($mayor->term_start)
                <p class="text-white/60 text-sm mt-2">
                    Görev: {{ $mayor->term_start->translatedFormat('Y') }} - {{ $mayor->term_end ? $mayor->term_end->translatedFormat('Y') : 'Devam ediyor' }}
                </p>
                @endif

                {{-- Sosyal Medya --}}
                @php $social = $mayor->social_url; @endphp
                @if($social['twitter'] || $social['instagram'] || $social['facebook'] || $social['website'])
                <div class="flex items-center justify-center md:justify-start gap-3 mt-4">
                    @if($social['twitter'])
                    <a href="{{ $social['twitter'] }}" target="_blank" rel="noopener noreferrer nofollow" class="w-9 h-9 rounded-lg bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    @endif
                    @if($social['instagram'])
                    <a href="{{ $social['instagram'] }}" target="_blank" rel="noopener noreferrer nofollow" class="w-9 h-9 rounded-lg bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-colors">
                        <span class="material-symbols-outlined text-lg">photo_camera</span>
                    </a>
                    @endif
                    @if($social['facebook'])
                    <a href="{{ $social['facebook'] }}" target="_blank" rel="noopener noreferrer nofollow" class="w-9 h-9 rounded-lg bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    @endif
                    @if($social['website'])
                    <a href="{{ $social['website'] }}" target="_blank" rel="noopener noreferrer nofollow" class="w-9 h-9 rounded-lg bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-colors">
                        <span class="material-symbols-outlined text-lg">language</span>
                    </a>
                    @endif
                </div>
                @endif
            </div>

            {{-- Parti & Belediye Logoları --}}
            <div class="hidden md:flex items-center gap-4 shrink-0">
                @if($mayor->municipality_logo_url)
                <div class="bg-white rounded-lg p-2 shadow-sm">
                    <img src="{{ $mayor->municipality_logo_display_url }}" alt="{{ $mayor->municipality_name }}" class="h-14 w-auto object-contain">
                </div>
                @endif
                @if($mayor->party?->logo_url)
                <div class="bg-white rounded-lg p-2 shadow-sm">
                    <img src="{{ $mayor->party->logo_display_url }}" alt="{{ $mayor->party->name }}" class="h-14 w-auto object-contain">
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-8">

{{-- Sol: İçerik --}}
<div class="flex-1 min-w-0">

    {{-- Biyografi --}}
    @if($mayor->bio || $mayor->bio_full)
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-xl" style="color: {{ $partyColor }}">person</span>
            Biyografi
        </h2>
        @if($mayor->bio)
        <p class="text-slate-600 leading-relaxed mb-4">{{ $mayor->bio }}</p>
        @endif
        @if($mayor->bio_full)
        <div class="mayor-bio max-w-none text-slate-600 leading-relaxed">
            {!! $mayor->bio_full !!}
        </div>
        <style>
            .mayor-bio p { margin-bottom: 1rem; line-height: 1.75; }
            .mayor-bio h2, .mayor-bio h3, .mayor-bio h4 { font-weight: 800; color: #0f172a; margin-top: 1.5rem; margin-bottom: 0.75rem; }
            .mayor-bio h2 { font-size: 1.25rem; }
            .mayor-bio h3 { font-size: 1.125rem; }
            .mayor-bio strong, .mayor-bio b { font-weight: 700; color: #1e293b; }
            .mayor-bio a { color: #dc2626; text-decoration: underline; }
            .mayor-bio a:hover { color: #991b1b; }
            .mayor-bio ul, .mayor-bio ol { margin: 0.75rem 0; padding-left: 1.5rem; }
            .mayor-bio li { margin-bottom: 0.25rem; }
            .mayor-bio blockquote { border-left: 4px solid #dc2626; padding-left: 1rem; margin: 1rem 0; color: #475569; font-style: italic; }
        </style>
        @endif
    </div>
    @endif

    {{-- Resmi Açıklamalar (Basın Odası) --}}
    @if($pressReleases->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-xl" style="color: {{ $partyColor }}">campaign</span>
            Resmi Açıklamalar
        </h2>
        <div class="space-y-3">
            @foreach($pressReleases as $pr)
            <a href="{{ $pr->url }}" class="group flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors">
                <div class="w-2 h-2 rounded-full mt-2 shrink-0" style="background-color: {{ $partyColor }}"></div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-sm text-slate-900 group-hover:text-primary transition-colors line-clamp-2">{{ $pr->title }}</h3>
                    <p class="text-xs text-slate-400 mt-1">{{ $pr->published_at->translatedFormat('d F Y') }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Haber Arşivi --}}
    @if($articles->isNotEmpty())
    <div class="mb-6">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-xl" style="color: {{ $partyColor }}">newspaper</span>
            Haber Arşivi
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($articles as $article)
            <a href="{{ $article->url }}" class="group flex gap-4 bg-white border border-gray-200 rounded-xl p-4 hover:border-red-200 hover:shadow-sm transition-all">
                <div class="w-24 h-16 rounded-lg overflow-hidden shrink-0">
                    <img src="{{ $article->image_display_url }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-sm text-slate-900 group-hover:text-primary transition-colors line-clamp-2">{{ $article->title }}</h3>
                    <div class="flex items-center gap-2 mt-1.5 text-[11px] text-slate-400">
                        <span>{{ $article->category->name }}</span>
                        <span>&middot;</span>
                        <span>{{ $article->published_at->diffForHumans() }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($articles->isEmpty() && $pressReleases->isEmpty() && !$mayor->bio && !$mayor->bio_full)
    <div class="text-center py-16 bg-white border border-gray-200 rounded-xl mb-6">
        <span class="material-symbols-outlined text-slate-300 text-5xl mb-3 block">article</span>
        <p class="text-slate-500">Henüz içerik eklenmemiş.</p>
    </div>
    @endif
</div>

{{-- Sağ: Sidebar --}}
<aside class="lg:w-[300px] shrink-0">
    <div class="sticky top-24 space-y-4">
        {{-- Bilgi Kartı --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-900 mb-3">Profil Bilgileri</h3>
            <dl class="space-y-2.5 text-sm">
                @if($mayor->party)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Parti</dt>
                    <dd class="font-semibold" style="color: {{ $partyColor }}">{{ $mayor->party->name }}</dd>
                </div>
                @endif
                @if($mayor->city)
                <div class="flex justify-between">
                    <dt class="text-slate-500">İl</dt>
                    <dd class="font-semibold text-slate-700">{{ $mayor->city->name }}</dd>
                </div>
                @endif
                @if($mayor->district)
                <div class="flex justify-between">
                    <dt class="text-slate-500">İlçe</dt>
                    <dd class="font-semibold text-slate-700">{{ $mayor->district }}</dd>
                </div>
                @endif
                @if($mayor->municipality_name)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Belediye</dt>
                    <dd class="font-semibold text-slate-700 text-right">{{ $mayor->municipality_name }}</dd>
                </div>
                @endif
                @if($mayor->term_start)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Görev Süresi</dt>
                    <dd class="font-semibold text-slate-700">{{ $mayor->term_start->format('Y') }} - {{ $mayor->term_end ? $mayor->term_end->format('Y') : 'Devam' }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam Haber</dt>
                    <dd class="font-semibold text-slate-700">{{ $articles->count() }}</dd>
                </div>
            </dl>
        </div>

        @include('partials.ad', ['position' => 'sidebar_top'])
    </div>
</aside>

</div>

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Belediye Başkanları', 'item' => route('mayor.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $mayor->name],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

{{-- Person Schema.org JSON-LD --}}
<script type="application/ld+json">
@php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $mayor->name,
    'jobTitle' => $mayor->title,
    'description' => $mayor->bio ?? ($mayor->name . ', ' . ($mayor->municipality_name ?? '') . ' ' . $mayor->title),
    'url' => $mayor->url,
];

if ($mayor->photo_display_url) {
    $schema['image'] = $mayor->photo_display_url;
}

if ($mayor->city) {
    $schema['workLocation'] = [
        '@type' => 'Place',
        'name' => $mayor->municipality_name ?? $mayor->city->name,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $mayor->district ?? $mayor->city->name,
            'addressRegion' => $mayor->city->name,
            'addressCountry' => 'TR',
        ],
    ];
}

if ($mayor->party) {
    $schema['memberOf'] = [
        '@type' => 'Organization',
        'name' => $mayor->party->name,
    ];
}

if ($mayor->municipality_name) {
    $schema['worksFor'] = [
        '@type' => 'GovernmentOrganization',
        'name' => $mayor->municipality_name,
    ];
}

$sameAs = [];
if ($mayor->social_twitter) $sameAs[] = $mayor->social_twitter;
if ($mayor->social_instagram) $sameAs[] = $mayor->social_instagram;
if ($mayor->social_facebook) $sameAs[] = $mayor->social_facebook;
if ($mayor->social_website) $sameAs[] = $mayor->social_website;
if (!empty($sameAs)) $schema['sameAs'] = $sameAs;

echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>

@endsection
