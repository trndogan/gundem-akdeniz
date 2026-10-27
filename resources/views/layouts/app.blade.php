<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Google Tag Manager: head'in mümkün olduğunca üstünde durmalı ki
         sayfa etkileşimleri kaçmasın. GTM_ID tanımlı değilse hiç basılmaz. --}}
    @if(config('services.gtm.id'))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ config('services.gtm.id') }}');</script>
    @endif

    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta name="referrer" content="origin-when-cross-origin">
    @hasSection('noindex')
    <meta name="robots" content="noindex, follow">
    @else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    @endif
    <title>@yield('title', 'Gündem Akdeniz - Akdeniz\'in Haber Portalı')</title>
    <meta name="description" content="@yield('meta_description', 'Akdeniz Bölgesi\'nin öncü haber platformu. Antalya, Mersin, Adana, Hatay ve tüm Akdeniz illerinden güncel haberler.')">
    <meta name="keywords" content="@yield('meta_keywords', 'akdeniz haberleri, antalya haber, mersin haber, adana haber, gündem akdeniz')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', 'Gündem Akdeniz')">
    <meta property="og:description" content="@yield('meta_description', 'Akdeniz Bölgesi\'nin öncü haber platformu.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Gündem Akdeniz">
    <meta property="og:locale" content="tr_TR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Gündem Akdeniz')">
    <meta name="twitter:description" content="@yield('meta_description', 'Akdeniz Bölgesi\'nin öncü haber platformu.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"></noscript>

    <style>
        body { font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .material-symbols-outlined { font-size: 24px; width: 24px; height: 24px; overflow: hidden; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @yield('head')
</head>
<body class="bg-gray-50 font-sans text-slate-900 antialiased overflow-x-hidden pb-16 lg:pb-0">
@if(config('services.gtm.id'))
{{-- JavaScript kapalıyken de sayfa görüntülemesi sayılsın --}}
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('services.gtm.id') }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif

    @include('partials.ad', ['position' => 'header_top'])

    @include('partials.header')
    @include('partials.breaking-news')

    @include('partials.ad', ['position' => 'header_bottom'])

    <main class="max-w-[1440px] mx-auto px-4 sm:px-6 py-8">
        @yield('content')
    </main>

    @include('partials.ad', ['position' => 'footer_top'])

    @include('partials.footer')

    @livewireScripts
    @yield('scripts')
</body>
</html>
