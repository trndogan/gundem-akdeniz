@php
    $footerDesc = \App\Models\SiteSetting::get('footer_description', 'Akdeniz Bölgesi\'nin öncü haber platformu. Güncel, tarafsız ve güvenilir habercilik.');
    $footerCopyright = \App\Models\SiteSetting::get('footer_copyright', '© ' . date('Y') . ' GundemAkdeniz.com - Tüm hakları saklıdır.');
    $footerTagline = \App\Models\SiteSetting::get('footer_tagline', 'Akdeniz Bölgesi\'nin Güvenilir Haber Kaynağı');
    $socialFb = \App\Models\SiteSetting::get('social_facebook', '#');
    $socialTw = \App\Models\SiteSetting::get('social_twitter', '#');
    $socialIg = \App\Models\SiteSetting::get('social_instagram', '#');
    $socialTk = \App\Models\SiteSetting::get('social_tiktok', '#');
    $serviceCards = \App\Models\SiteSetting::getJson('footer_service_cards', [
        ['title' => 'Hava Durumu', 'subtitle' => 'İlçe ilçe detaylı', 'icon' => 'wb_sunny', 'url' => '/hava-durumu', 'is_active' => true],
        ['title' => 'Nöbetçi Eczane', 'subtitle' => 'Canlı listeler', 'icon' => 'local_pharmacy', 'url' => '/nobetci-eczaneler', 'is_active' => true],
        ['title' => 'Belediye Başkanları', 'subtitle' => 'Başkanlar rehberi', 'icon' => 'account_balance', 'url' => '/belediye-baskanlari', 'is_active' => true],
        ['title' => 'Vefat Haberleri', 'subtitle' => 'Taziye ilanları', 'icon' => 'sentiment_dissatisfied', 'url' => '#', 'is_active' => true],
        ['title' => 'İş İlanları', 'subtitle' => 'Bölgesel fırsatlar', 'icon' => 'work', 'url' => '/is-ilanlari', 'is_active' => true],
        ['title' => 'Haber Gönder', 'subtitle' => 'İhbar ve bilgi', 'icon' => 'tips_and_updates', 'url' => '/haber-gonder', 'is_active' => true],
    ]);
    $extraLinks = \App\Models\SiteSetting::getJson('footer_extra_links', [
        ['title' => 'Haber Gönder', 'url' => '/haber-gonder', 'is_active' => true],
        ['title' => 'RSS', 'url' => '/sitemap.xml', 'is_active' => true],
    ]);
    $activeCards = collect($serviceCards)->where('is_active', true);
    $activeExtraLinks = collect($extraLinks)->where('is_active', true);
@endphp

<footer class="ga-alt">
    <div class="ga-alt__ic">

        {{-- Üst: marka, sosyal ve bağlantı sütunları --}}
        <div class="ga-alt__ust">
            <div class="ga-alt__marka">
                <a href="{{ route('home') }}" class="ga-alt__logo">
                    {{-- Koyu zemin sürümü: siyah metin beyaza çevrilmiş, kırmızı korunmuş --}}
                    <img src="{{ asset('images/marka/logo-beyaz.png') }}"
                         srcset="{{ asset('images/marka/logo-beyaz.png') }} 1x, {{ asset('images/marka/logo-beyaz@2x.png') }} 2x"
                         alt="Gündem Akdeniz" width="229" height="80" loading="lazy">
                </a>
                <p class="ga-alt__aciklama">{{ $footerDesc }}</p>

                <div class="ga-alt__sosyal">
                    <a href="{{ $socialFb }}" target="_blank" rel="noopener" aria-label="Facebook" style="--ga-marka:#1877F2">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="{{ $socialTw }}" target="_blank" rel="noopener" aria-label="X" style="--ga-marka:#000000">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="{{ $socialIg }}" target="_blank" rel="noopener" aria-label="Instagram" style="--ga-marka:#E4405F">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="{{ $socialTk }}" target="_blank" rel="noopener" aria-label="TikTok" style="--ga-marka:#25F4EE">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/></svg>
                    </a>
                </div>
            </div>

            @php
                $footerCities = \App\Models\City::active()->orderBy('sort_order')->get();
                $footerCategories = \App\Models\Category::active()->orderBy('sort_order')->get();
                $footerPages = \App\Models\Page::footer()->get();
            @endphp

            <nav class="ga-alt__sutun" aria-label="Bölgeler">
                <h3>Bölgeler</h3>
                <ul>
                    @foreach($footerCities as $city)
                        <li><a href="{{ url('/' . $city->slug) }}">{{ $city->name }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav class="ga-alt__sutun" aria-label="Kategoriler">
                <h3>Kategoriler</h3>
                <ul>
                    @foreach($footerCategories as $cat)
                        <li><a href="{{ url('/' . $cat->slug) }}">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav class="ga-alt__sutun" aria-label="Kurumsal">
                <h3>Kurumsal</h3>
                <ul>
                    @foreach($footerPages as $fp)
                        <li><a href="{{ url('/' . $fp->slug) }}">{{ $fp->title }}</a></li>
                    @endforeach
                    @foreach($activeExtraLinks as $link)
                        <li><a href="{{ url($link['url']) }}">{{ $link['title'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>

        {{-- Hizmet kısayolları --}}
        @if($activeCards->isNotEmpty())
        <div class="ga-alt__servis">
            @foreach($activeCards as $card)
            <a href="{{ url($card['url']) }}" class="ga-alt__kart">
                <span class="material-symbols-outlined">{{ $card['icon'] }}</span>
                <span class="ga-alt__kart-metin">
                    <strong>{{ $card['title'] }}</strong>
                    <small>{{ $card['subtitle'] }}</small>
                </span>
            </a>
            @endforeach
        </div>
        @endif

        <div class="ga-alt__son">
            <p>{!! $footerCopyright !!}</p>
            <p class="ga-alt__slogan">{{ $footerTagline }}</p>
        </div>
    </div>
</footer>

<style>
    /* Alt bilgi. Sitenin geri kalanı açık zeminde, alt bilgi koyu: sayfa burada biter.
       Sıkı tutulur: bağlantı listeleri iki sütuna bölünür, yoksa 11 satırlık kategori
       listesi alt bilgiyi ekran boyu uzatıyordu.
       Tailwind derlemesi sabit olduğu için tüm sınıflar burada tanımlıdır. */
    .ga-alt { background: #0f1218; color: #cbd2dc; margin-top: 2.5rem;
              border-top: 3px solid var(--color-primary, #fe0000); }
    .ga-alt__ic { max-width: 1440px; margin: 0 auto; padding: 1.9rem 1.5rem 1rem; }

    .ga-alt__ust { display: grid; grid-template-columns: 1.1fr 1fr 1.15fr .9fr; gap: 1.75rem; }

    .ga-alt__logo { display: inline-flex; align-items: center; gap: .55rem; text-decoration: none; }
    .ga-alt__logo img { height: 62px; width: auto; display: block; }
    @media (max-width: 640px) { .ga-alt__logo img { height: 48px; } }

    .ga-alt__aciklama { margin: .6rem 0 .8rem; font-size: .78rem; line-height: 1.5;
                        color: #8d97a6; max-width: 32ch; }

    .ga-alt__sosyal { display: flex; gap: .4rem; }
    .ga-alt__sosyal a { width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #262c37; color: #8d97a6; background: #151a22;
        transition: color .18s ease, border-color .18s ease, background .18s ease; }
    .ga-alt__sosyal a:hover { color: #fff; background: var(--ga-marka, #fe0000);
                              border-color: var(--ga-marka, #fe0000); }
    .ga-alt__sosyal svg { width: 14px; height: 14px; }

    .ga-alt__sutun h3 { font-size: .66rem; font-weight: 800; letter-spacing: .13em;
        text-transform: uppercase; color: #fff; margin: 0 0 .6rem; padding-bottom: .4rem;
        border-bottom: 1px solid #232935; position: relative; }
    .ga-alt__sutun h3::after { content: ''; position: absolute; left: 0; bottom: -1px;
        width: 24px; height: 2px; background: var(--color-primary, #fe0000); }
    /* Uzun listeler iki sütuna akar; alt bilgi yüksekliği yarıya iner */
    .ga-alt__sutun ul { list-style: none; margin: 0; padding: 0;
                        columns: 2; column-gap: 1rem; }
    .ga-alt__sutun li { margin-bottom: .3rem; break-inside: avoid; }
    .ga-alt__sutun a { font-size: .8rem; color: #98a2b1; text-decoration: none;
                       transition: color .15s ease; display: inline-block; }
    .ga-alt__sutun a:hover { color: #fff; }

    .ga-alt__servis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .5rem; margin-top: 1.4rem; padding-top: 1.15rem; border-top: 1px solid #1c222c; }
    .ga-alt__kart { display: flex; align-items: center; gap: .5rem; text-decoration: none;
        padding: .5rem .6rem; border-radius: 9px; background: #151a22;
        border: 1px solid #212734; transition: border-color .18s ease, background .18s ease; }
    .ga-alt__kart:hover { background: #1a2029; border-color: #343d4d; }
    .ga-alt__kart .material-symbols-outlined { font-size: 17px; color: var(--color-primary, #fe0000); }
    .ga-alt__kart-metin { display: flex; flex-direction: column; min-width: 0; }
    .ga-alt__kart strong { font-size: .73rem; font-weight: 700; color: #eef1f5; line-height: 1.2; }
    .ga-alt__kart small { font-size: .63rem; color: #798394; line-height: 1.3; }

    .ga-alt__son { display: flex; justify-content: space-between; align-items: center;
        gap: .75rem; flex-wrap: wrap; margin-top: 1.15rem; padding-top: .8rem;
        border-top: 1px solid #1c222c; font-size: .73rem; color: #6f7987; }
    .ga-alt__son p { margin: 0; }
    .ga-alt__slogan { font-size: .7rem; }

    @media (max-width: 1024px) {
        .ga-alt__ust { grid-template-columns: 1fr 1fr; gap: 1.25rem 1.5rem; }
        .ga-alt__marka { grid-column: 1 / -1; }
        .ga-alt__servis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .ga-alt__ic { padding: 1.5rem 1.15rem .9rem; }
        .ga-alt__ust { grid-template-columns: 1fr; gap: 1.1rem; }
        .ga-alt__sutun ul { columns: 2; }
        .ga-alt__servis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ga-alt__son { flex-direction: column; align-items: flex-start; gap: .35rem; }
    }
</style>
