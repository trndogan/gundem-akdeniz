@extends('layouts.app')

@section('title', 'Gündem Akdeniz - Akdeniz\'in Haber Portalı')
@section('og_title', 'Gündem Akdeniz - Akdeniz Bölgesi Haber Portalı')

@section('content')

<style>
    /* Manşet kartı: üstte 16:9 görsel, altında başlık.
       Görsel 16:9 üretiliyor, kutu da 16:9 olunca ne boşluk kalır ne kırpma olur;
       kartın kalan yüksekliğini başlık bloğu doldurur. */
    .ga-manset__golge { position: absolute; inset: auto 0 0 0; height: 45%;
                        background: linear-gradient(to top, rgba(15,23,42,.75), transparent); }

    .ga-manset__alt { flex: 1; display: flex; flex-direction: column; justify-content: center;
                      gap: .45rem; padding: 1.15rem 1.4rem 1.4rem; min-width: 0; }
    .ga-manset__baslik { font-size: 1.5rem; line-height: 1.2; font-weight: 800; color: #fff;
                         letter-spacing: -.015em; display: -webkit-box; -webkit-line-clamp: 2;
                         -webkit-box-orient: vertical; overflow: hidden; }
    .ga-manset__ozet { font-size: .875rem; line-height: 1.5; color: #b6c2d2;
                       display: -webkit-box; -webkit-line-clamp: 2;
                       -webkit-box-orient: vertical; overflow: hidden; }

    @media (min-width: 1024px) {
        .ga-manset { aspect-ratio: 16 / 9; flex: none; }
        .ga-manset__baslik { font-size: 1.65rem; }
    }
    @media (max-width: 640px) {
        .ga-manset__baslik { font-size: 1.2rem; }
        .ga-manset__ozet { display: none; }
    }
</style>


    <!-- Bento Hero Grid -->
    <section class="grid grid-cols-1 lg:grid-cols-5 gap-4 h-auto lg:h-[600px] mb-12">

        <!-- Main Story (3/5) -->
        @if($headline)
        <a href="{{ $headline->url }}" class="lg:col-span-3 relative rounded-xl overflow-hidden group bento-card cursor-pointer block bg-slate-900 flex flex-col">
            <div class="relative flex-1 min-h-[300px] lg:min-h-0 overflow-hidden ga-manset">
                <img src="{{ $headline->image_display_url }}" alt="{{ $headline->title }}" class="absolute inset-0 w-full h-full object-contain transition-transform duration-700 group-hover:scale-105" loading="eager" fetchpriority="high">
                <div class="ga-manset__golge"></div>
                <div class="absolute bottom-4 left-4 flex gap-2">
                    <span class="bg-primary text-white text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Özel Haber</span>
                    @if($headline->city)
                        <span class="bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">{{ $headline->city->name }}</span>
                    @endif
                </div>
            </div>

            <div class="ga-manset__alt">
                <h1 class="ga-manset__baslik">{{ $headline->title }}</h1>
                @if($headline->excerpt)
                    <p class="ga-manset__ozet">{{ $headline->excerpt }}</p>
                @endif
            </div>
        </a>
        @endif

        <!-- Trending Stack (2/5) -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-4">
            @foreach($featuredArticles->take(4) as $featured)
            <a href="{{ $featured->url }}" class="relative rounded-xl overflow-hidden bento-card cursor-pointer border border-gray-200 bg-white shadow-sm block">
                <div class="flex h-full">
                    <div class="w-1/3 relative overflow-hidden bg-slate-900">
                        <img src="{{ $featured->image_display_url }}" alt="{{ $featured->title }}" class="absolute inset-0 w-full h-full object-contain" loading="lazy">
                    </div>
                    <div class="w-2/3 p-4 flex flex-col justify-center">
                        <span class="text-primary text-[10px] font-bold uppercase mb-1">{{ $featured->category->name }}</span>
                        <h3 class="text-base font-bold leading-snug text-slate-900 group-hover:text-primary transition-colors">
                            {{ $featured->title }}
                        </h3>
                        @if($featured->excerpt)
                        <p class="text-xs text-slate-600 mt-2 line-clamp-1">{{ $featured->excerpt }}</p>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>

    @include('partials.ad', ['position' => 'content_between'])

    <!-- Regional Hub Section (Livewire) -->
    <livewire:city-news />

    <!-- Latest News Section -->
    <section class="py-12 border-t border-gray-200">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">Son Haberler</h2>
                <p class="text-slate-600 mt-1">Gündemden en sıcak gelişmeler ve son dakika haberleri</p>
            </div>
        </div>

        <!-- News Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($latestArticles as $article)
            <a href="{{ $article->url }}" class="group cursor-pointer block">
                <div class="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm bg-slate-900">
                    <img src="{{ $article->image_display_url }}" alt="{{ $article->title }}" class="absolute inset-0 w-full h-full object-contain group-hover:scale-110 transition-transform duration-500" loading="lazy">
                    <div class="absolute top-3 left-3 bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase">{{ $article->category->name }}</div>
                </div>
                <h4 class="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
                    {{ $article->title }}
                </h4>
                @if($article->excerpt)
                <p class="text-slate-600 text-sm line-clamp-2">
                    {{ $article->excerpt }}
                </p>
                @endif
                <div class="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
                    <span>{{ $article->city?->name ?? $article->category->name }}</span>
                    <span>{{ $article->published_at->diffForHumans() }}</span>
                </div>
            </a>
            @endforeach
        </div>
    </section>

{{-- WebSite JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'Gündem Akdeniz',
    'url' => url('/'),
    'description' => 'Akdeniz Bölgesi\'nin öncü haber platformu',
    'inLanguage' => 'tr',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Gündem Akdeniz',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png'),
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>

@endsection
