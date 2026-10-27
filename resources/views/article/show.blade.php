@extends('layouts.app')

@section('title', $article->seo_title ?? $article->title . ' - Gündem Akdeniz')
@section('meta_description', $article->seo_description ?? $article->excerpt)
@section('meta_keywords', $article->seo_keywords ?? '')
@section('og_type', 'article')
@section('og_title', $article->seo_title ?? $article->title)
@section('og_image', $article->image_display_url)
@section('canonical', $article->url)

@section('content')

<style>
    /* Sitenin Tailwind derlemesi sabit olduğu için bu sayfadaki yeni bileşenler
       kendi CSS'iyle çizilir; derlemede olmayan yardımcı sınıflara güvenilmez. */

    @media (min-width: 1024px) {
        .ga-satir { justify-content: center; }
        .ga-okuma { max-width: 780px; }
        .ga-yan   { width: 340px; flex: 0 0 340px; }
    }
    .ga-okuma h1 { font-size: clamp(1.55rem, 2.4vw, 2.2rem); line-height: 1.2; }

    /* Künye */
    .ga-kunye { border-top: 1px solid #e5e7eb; margin-top: 1.25rem; padding-top: .9rem; }
    .ga-kunye__satir { display: flex; flex-wrap: wrap; align-items: center;
                       column-gap: 1.25rem; row-gap: .4rem; font-size: 13px; color: #64748b; }
    .ga-kunye__oge { display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; }
    .ga-kunye__oge b { color: #334155; font-weight: 600; }
    .ga-kunye__oge a { color: inherit; }
    .ga-kunye__oge a:hover b { color: #fe0000; }
    .ga-kunye__ikon { font-size: 16px; line-height: 1; opacity: .7; }

    /* Paylaşım ve yazı boyutu */
    .ga-araclar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
                  gap: .75rem; margin-top: .9rem; padding-top: .9rem; border-top: 1px solid #f1f5f9; }
    .ga-share { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .ga-share__label { font-size: 12px; font-weight: 700; letter-spacing: .04em;
                       text-transform: uppercase; color: #64748b; margin-right: .35rem; }
    .ga-share__btn { width: 36px; height: 36px; display: inline-flex; align-items: center;
                     justify-content: center; border: 1px solid #e5e7eb; border-radius: 9px;
                     color: #475569; background: #fff; transition: all .16s ease; cursor: pointer; }
    .ga-share__btn svg { width: 17px; height: 17px; display: block; }
    .ga-share__btn:hover { background: var(--ga-marka); border-color: var(--ga-marka); color: #fff; }

    .ga-font { display: inline-flex; gap: .25rem; }
    .ga-font button { width: 38px; height: 32px; border: 1px solid #e5e7eb; border-radius: 9px;
                      background: #fff; color: #64748b; font-weight: 700; cursor: pointer;
                      transition: all .16s ease; }
    .ga-font button:hover { background: #f8fafc; }
    .ga-font button.secili { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }

    /* Öne çıkan görsel */
    .ga-figur { margin: 0 0 2rem; }
    .ga-figur__ac { display: block; width: 100%; padding: 0; border: 1px solid #e5e7eb;
                    border-radius: 12px; overflow: hidden; background: #0f172a;
                    cursor: zoom-in; position: relative; }
    .ga-figur__ac img { display: block; width: 100%; height: auto; }
    .ga-figur__zoom { position: absolute; top: 12px; right: 12px; width: 34px; height: 34px;
                      border-radius: 999px; background: rgba(0,0,0,.55); color: #fff;
                      display: flex; align-items: center; justify-content: center;
                      opacity: 0; transition: opacity .18s ease; }
    .ga-figur__zoom .material-symbols-outlined { font-size: 19px; }
    .ga-figur__ac:hover .ga-figur__zoom { opacity: 1; }
    .ga-figur figcaption { margin-top: .5rem; font-size: 12px; color: #64748b; }

    .ga-alt-paylas { padding: 1.1rem 0; margin-bottom: 3rem;
                     border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }

    /* Büyütme katmanı */
    .ga-lightbox { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.9);
                   display: flex; align-items: center; justify-content: center; padding: 1rem; cursor: zoom-out; }
    .ga-lightbox img { max-width: 100%; max-height: 100%; border-radius: 10px; }

    /* En çok okunanlar. Sıra numarası tipografik: dolu kırmızı balonlar yerine
       ilk üçü marka renginde, kalanı soluk. 10 satır sığsın diye satırlar dar. */
    .ga-cok { background: #fff; border: 1px solid #e8eaed; border-radius: 14px; overflow: hidden; }
    .ga-cok__ust { display: flex; align-items: center; gap: .55rem;
                   padding: .9rem 1rem .75rem; border-bottom: 1px solid #f1f3f5; }
    .ga-cok__cizgi { width: 3px; height: 15px; border-radius: 2px;
                     background: var(--color-primary, #fe0000); }
    .ga-cok__ust h3 { margin: 0; font-size: 12px; font-weight: 800; color: #0f172a;
                      letter-spacing: .09em; text-transform: uppercase; }

    .ga-cok__satir { display: flex; align-items: center; gap: .7rem;
                     padding: .5rem 1rem; text-decoration: none;
                     transition: background .15s ease; }
    .ga-cok__satir + .ga-cok__satir { border-top: 1px solid #f6f7f8; }
    .ga-cok__satir:hover { background: #fafbfc; }

    .ga-cok__no { flex: 0 0 16px; text-align: center; line-height: 1;
                  font-size: 15px; font-weight: 800; color: #cbd5e1;
                  font-variant-numeric: tabular-nums; }
    .ga-cok__satir--ilk .ga-cok__no { color: var(--color-primary, #fe0000); }

    .ga-cok__gorsel { flex: 0 0 62px; width: 62px; height: 46px; border-radius: 8px;
                      overflow: hidden; background: #f1f5f9; }
    .ga-cok__gorsel img { width: 100%; height: 100%; object-fit: cover; display: block;
                          transition: transform .35s ease; }
    .ga-cok__satir:hover .ga-cok__gorsel img { transform: scale(1.07); }

    .ga-cok__metin { flex: 1; min-width: 0; }
    .ga-cok__baslik { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
                      overflow: hidden; font-size: 13px; font-weight: 700;
                      line-height: 1.35; color: #1e293b; transition: color .15s ease; }
    .ga-cok__satir:hover .ga-cok__baslik { color: var(--color-primary, #fe0000); }
    .ga-cok__meta { display: block; margin-top: .22rem; font-size: 10.5px; color: #94a3b8; }
    .ga-cok__meta b { font-weight: 700; text-transform: uppercase;
                      color: var(--color-primary, #fe0000); }
</style>


{{-- Okuma ilerleme çubuğu --}}
<div id="okuma-cubugu" class="fixed top-0 left-0 h-[3px] bg-primary z-50 w-0 transition-[width] duration-150"></div>


<div class="flex flex-col lg:flex-row gap-8 ga-satir">

{{-- Sol: Makale İçeriği --}}
<article class="flex-1 min-w-0 ga-okuma">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="{{ url('/' . $article->category->slug) }}" class="hover:text-primary transition-colors">{{ $article->category->name }}</a>
        @if($article->city)
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="{{ url('/' . $article->city->slug) }}" class="hover:text-primary transition-colors">{{ $article->city->name }}</a>
        @endif
    </nav>

    <!-- Article Header -->
    <header class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-primary text-white text-xs font-bold px-3 py-1 rounded uppercase">{{ $article->category->name }}</span>
            @if($article->city)
            <span class="bg-gray-100 text-slate-700 text-xs font-bold px-3 py-1 rounded">{{ $article->city->name }}</span>
            @endif
        </div>

        <h1 class="text-2xl sm:text-3xl lg:text-5xl font-extrabold leading-tight tracking-tight text-slate-900 mb-4">
            {{ $article->title }}
        </h1>

        @if($article->excerpt)
        <p class="text-base sm:text-xl text-slate-600 leading-relaxed mb-6">
            {{ $article->excerpt }}
        </p>
        @endif

        {{-- Künye: giriş, güncelleme, kaynak. Stil ga-* sınıflarıyla, derlemeden bağımsız. --}}
        <div class="ga-kunye">
            <div class="ga-kunye__satir">
                <span class="ga-kunye__oge">
                    <span class="material-symbols-outlined ga-kunye__ikon">person</span>
                    <b>{{ $article->author->name ?? 'Editör' }}</b>
                </span>

                <span class="ga-kunye__oge">
                    <span class="material-symbols-outlined ga-kunye__ikon">schedule</span>
                    Giriş
                    <b><time datetime="{{ $article->published_at->toISOString() }}">{{ $article->published_at->translatedFormat('d.m.Y H:i') }}</time></b>
                </span>

                @if($article->updated_at && $article->updated_at->gt($article->published_at->addMinutes(2)))
                <span class="ga-kunye__oge">
                    Güncelleme
                    <b><time datetime="{{ $article->updated_at->toISOString() }}">{{ $article->updated_at->translatedFormat('d.m.Y H:i') }}</time></b>
                </span>
                @endif

                <span class="ga-kunye__oge">
                    <span class="material-symbols-outlined ga-kunye__ikon">visibility</span>
                    {{ number_format($article->view_count) }}
                </span>

                <span class="ga-kunye__oge">
                    <span class="material-symbols-outlined ga-kunye__ikon">timer</span>
                    {{ $article->reading_time }} dk okuma
                </span>

                <span class="ga-kunye__oge">
                    <span class="material-symbols-outlined ga-kunye__ikon">source</span>
                    Kaynak
                    @if(!empty($article->source_url))
                        <a href="{{ $article->source_url }}" target="_blank" rel="noopener nofollow"><b>{{ $article->source_name }}</b></a>
                    @else
                        <b>{{ $article->source_name ?: 'Haber Merkezi' }}</b>
                    @endif
                </span>
            </div>

            <div class="ga-araclar">
                @include('partials.share', ['article' => $article])

                <div class="ga-font" role="group" aria-label="Yazı boyutu">
                    <button type="button" data-font-step="-1" style="font-size:12px">A-</button>
                    <button type="button" data-font-step="0" style="font-size:14px" class="secili">A</button>
                    <button type="button" data-font-step="1" style="font-size:16px">A+</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    @php
        $imgUrl = $article->image_display_url;
    @endphp
    @if($imgUrl)
    {{-- Sabit oran yok: görsel kendi oranında durur, ne kırpılır ne boşluk kalır --}}
    <figure class="ga-figur">
        <button type="button" data-lightbox="{{ $imgUrl }}" class="ga-figur__ac" aria-label="Görseli büyüt">
            <img src="{{ $imgUrl }}" alt="{{ $article->title }}" loading="eager" fetchpriority="high">
            <span class="ga-figur__zoom"><span class="material-symbols-outlined">zoom_in</span></span>
        </button>
        @if($article->city)
        <figcaption>{{ $article->city->name }} · {{ $article->category->name }}</figcaption>
        @endif
    </figure>
    @endif

    <!-- Article Body -->
    <div class="article-body max-w-none mb-8 overflow-hidden break-words">
        {!! strip_tags($article->content_html ?? $article->body, '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><a><strong><b><em><i><u><s><del><blockquote><figure><figcaption><img><table><thead><tbody><tfoot><tr><th><td><caption><hr><pre><code><sup><sub><span><iframe><video><source>') !!}
    </div>

    @include('partials.ad', ['position' => 'article_inline'])

    <div class="ga-alt-paylas">
        @include('partials.share', ['article' => $article, 'baslik' => 'Bu haberi paylaş'])
    </div>
</article>

{{-- Sağ: Sidebar --}}
<aside class="hidden lg:block shrink-0 ga-yan">
    <div class="sticky top-24 space-y-6">
        @include('partials.ad', ['position' => 'sidebar_top'])

        {{-- En Çok Okunanlar --}}
        @if(isset($mostRead) && $mostRead->isNotEmpty())
        <div class="ga-cok">
            <div class="ga-cok__ust">
                <span class="ga-cok__cizgi"></span>
                <h3>En Çok Okunanlar</h3>
            </div>

            @foreach($mostRead as $index => $item)
            <a href="{{ $item->url }}" class="ga-cok__satir @if($index < 3) ga-cok__satir--ilk @endif">
                <span class="ga-cok__no">{{ $index + 1 }}</span>

                <span class="ga-cok__gorsel">
                    @if($item->image_display_url)
                        <img src="{{ $item->image_display_url }}" alt="{{ $item->title }}" loading="lazy">
                    @endif
                </span>

                <span class="ga-cok__metin">
                    <span class="ga-cok__baslik">{{ $item->title }}</span>
                    <span class="ga-cok__meta">
                        @if($item->category?->name)<b>{{ $item->category->name }}</b> &middot; @endif
                        {{ number_format($item->view_count) }} okunma
                    </span>
                </span>
            </a>
            @endforeach
        </div>
        @endif

        @include('partials.ad', ['position' => 'sidebar_bottom'])
    </div>
</aside>

</div>{{-- flex row kapanış --}}

<!-- Related Articles -->
@if($relatedArticles->isNotEmpty())
<section class="py-12 border-t border-gray-200">
    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 mb-8">İlgili Haberler</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($relatedArticles as $related)
        <a href="{{ $related->url }}" class="group cursor-pointer block">
            <div class="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm bg-slate-900">
                <img src="{{ $related->image_display_url }}" alt="{{ $related->title }}" class="absolute inset-0 w-full h-full object-contain group-hover:scale-110 transition-transform duration-500" loading="lazy">
                <div class="absolute top-3 left-3 bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase">{{ $related->category->name }}</div>
            </div>
            <h4 class="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
                {{ $related->title }}
            </h4>
            <div class="mt-2 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
                {{ $related->published_at->diffForHumans() }}
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
$breadcrumbItems = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
    ['@type' => 'ListItem', 'position' => 2, 'name' => $article->category->name, 'item' => url('/' . $article->category->slug)],
];
if ($article->city) {
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $article->city->name, 'item' => url('/' . $article->city->slug)];
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $article->title];
} else {
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title];
}
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

{{-- NewsArticle JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article->title,
    'description' => $article->excerpt ?? Str::limit(strip_tags($article->body), 160),
    'image' => [$article->image_display_url],
    'datePublished' => $article->published_at->toISOString(),
    'dateModified' => $article->updated_at->toISOString(),
    'author' => [
        '@type' => 'Person',
        'name' => $article->author->name ?? 'Editör',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Gündem Akdeniz',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png'),
        ],
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $article->url,
    ],
    'articleSection' => $article->category->name,
    'wordCount' => str_word_count(strip_tags($article->body)),
    'inLanguage' => 'tr',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>

@endsection

@section('scripts')
<script>
(function () {
    // Okuma ilerlemesi
    const cubuk = document.getElementById('okuma-cubugu');
    const govde = document.querySelector('.article-body');
    if (cubuk && govde) {
        const guncelle = () => {
            const ust = govde.offsetTop;
            const yukseklik = govde.offsetHeight - window.innerHeight;
            const oran = yukseklik > 0 ? (window.scrollY - ust) / yukseklik : 0;
            cubuk.style.width = Math.max(0, Math.min(1, oran)) * 100 + '%';
        };
        window.addEventListener('scroll', guncelle, { passive: true });
        window.addEventListener('resize', guncelle);
        guncelle();
    }

    // Yazı boyutu: seçim tarayıcıda hatırlanır
    const boyutlar = ['0.94rem', '1.05rem', '1.18rem'];
    const dugmeler = document.querySelectorAll('[data-font-step]');
    const uygula = (adim) => {
        if (!govde) return;
        govde.style.fontSize = boyutlar[adim + 1] || boyutlar[1];
        dugmeler.forEach((d) => d.classList.toggle('secili', Number(d.dataset.fontStep) === adim));
        try { localStorage.setItem('ga-yazi-boyutu', String(adim)); } catch (e) {}
    };
    let baslangic = 0;
    try { baslangic = Number(localStorage.getItem('ga-yazi-boyutu') ?? 0); } catch (e) {}
    if (dugmeler.length) {
        uygula(Number.isFinite(baslangic) ? baslangic : 0);
        dugmeler.forEach((d) => d.addEventListener('click', () => uygula(Number(d.dataset.fontStep))));
    }

    // Görsel büyütme
    const acici = document.querySelector('[data-lightbox]');
    if (acici) {
        acici.addEventListener('click', () => {
            const katman = document.createElement('div');
            katman.className = 'ga-lightbox';
            katman.innerHTML = '<img src="' + acici.dataset.lightbox + '" alt="">';
            katman.addEventListener('click', () => katman.remove());
            document.addEventListener('keydown', function kapat(e) {
                if (e.key === 'Escape') { katman.remove(); document.removeEventListener('keydown', kapat); }
            });
            document.body.appendChild(katman);
        });
    }

    // Bağlantı kopyala
    document.querySelectorAll('[data-baglanti-kopyala]').forEach((d) => {
        d.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(window.location.href);
                const eski = d.getAttribute('aria-label');
                d.setAttribute('aria-label', 'Bağlantı kopyalandı');
                d.style.borderColor = '#fe0000';
                setTimeout(() => { d.setAttribute('aria-label', eski); d.style.borderColor = ''; }, 1600);
            } catch (e) {}
        });
    });
})();
</script>
@endsection
