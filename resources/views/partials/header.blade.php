@php
    $navCategories = \App\Models\Category::active()->inMenu()->orderBy('sort_order')->get();
    $navCities = \App\Models\City::active()->inMenu()->orderBy('sort_order')->get();
    $headerServiceCards = collect(\App\Models\SiteSetting::getJson('footer_service_cards', [
        ['title' => 'Hava Durumu', 'subtitle' => 'İlçe ilçe detaylı', 'icon' => 'wb_sunny', 'url' => '/hava-durumu', 'is_active' => true],
        ['title' => 'Nöbetçi Eczane', 'subtitle' => 'Canlı listeler', 'icon' => 'local_pharmacy', 'url' => '/nobetci-eczaneler', 'is_active' => true],
        ['title' => 'Belediye Başkanları', 'subtitle' => 'Başkanlar rehberi', 'icon' => 'account_balance', 'url' => '/belediye-baskanlari', 'is_active' => true],
        ['title' => 'Vefat Haberleri', 'subtitle' => 'Taziye ilanları', 'icon' => 'sentiment_dissatisfied', 'url' => '#', 'is_active' => true],
        ['title' => 'İş İlanları', 'subtitle' => 'Bölgesel fırsatlar', 'icon' => 'work', 'url' => '/is-ilanlari', 'is_active' => true],
        ['title' => 'Haber Gönder', 'subtitle' => 'İhbar ve bilgi', 'icon' => 'tips_and_updates', 'url' => '/haber-gonder', 'is_active' => true],
    ]))->where('is_active', true)->filter(fn($c) => ($c['title'] ?? '') !== 'Haber Gönder');
    $mobileBottomCards = $headerServiceCards->filter(fn($c) => ($c['title'] ?? '') !== 'Vefat Haberleri');
@endphp

<!-- Sticky Glass Header -->
<nav class="sticky top-0 z-50 glass-header" x-data="{ mobileOpen: false, citiesOpen: false }" x-effect="document.body.classList.toggle('overflow-hidden', mobileOpen)">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6">
        <div class="ga-ustbar flex items-center justify-between">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 sm:gap-3 shrink-0">
                {{-- Marka görseli. srcset ile yüksek yoğunluklu ekranlarda 2x sürüm kullanılır.
                     width/height verilir ki sayfa yüklenirken satır kaymasın. --}}
                <img src="{{ asset('images/marka/logo.png') }}"
                     srcset="{{ asset('images/marka/logo.png') }} 1x, {{ asset('images/marka/logo@2x.png') }} 2x"
                     alt="Gündem Akdeniz" width="229" height="80"
                     class="ga-logo" fetchpriority="high">
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-6 xl:gap-8">
                @foreach($navCategories->take(1) as $cat)
                    <a class="text-[13px] font-bold hover:text-primary transition-colors uppercase tracking-wide" href="{{ url('/' . $cat->slug) }}">{{ $cat->name }}</a>
                @endforeach

                <!-- Şehirler Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="text-[13px] font-bold hover:text-primary transition-colors uppercase tracking-wide flex items-center gap-1">
                        Şehirler
                        <span class="material-symbols-outlined text-sm transition-transform" :class="open && 'rotate-180'">expand_more</span>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute top-full left-1/2 -translate-x-1/2 mt-3 bg-white rounded-xl shadow-xl border border-gray-100 py-2 min-w-[200px]"
                         style="display: none;">
                        @foreach($navCities as $city)
                            <a href="{{ url('/' . $city->slug) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors group/item">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                <span class="text-sm font-semibold text-slate-700 group-hover/item:text-primary transition-colors">{{ $city->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @foreach($navCategories->skip(1)->take(4) as $cat)
                    <a class="text-[13px] font-bold hover:text-primary transition-colors uppercase tracking-wide" href="{{ url('/' . $cat->slug) }}">{{ $cat->name }}</a>
                @endforeach
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Search (desktop) -->
                <div class="hidden md:block">
                    <livewire:search-box />
                </div>

                <!-- Haber Gönder Button -->
                <a href="{{ route('tip.create') }}" class="hidden sm:flex items-center gap-1.5 bg-primary hover:bg-red-700 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-bold transition-all">
                    <span class="material-symbols-outlined text-sm">tips_and_updates</span>
                    <span>HABER GÖNDER</span>
                </a>

                <!-- Mobile Menu Toggle -->
                <button class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 transition-colors" @click="mobileOpen = !mobileOpen">
                    <span class="material-symbols-outlined text-2xl text-slate-900" x-text="mobileOpen ? 'close' : 'menu'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden border-t border-gray-200 bg-white/95 backdrop-blur-lg max-h-[calc(100vh-56px)] overflow-y-auto"
         style="display: none;"
         @click.outside="mobileOpen = false">
        <div class="max-w-[1440px] mx-auto px-4 py-4 space-y-1">
            <!-- Mobile Search -->
            <div class="mb-3 md:hidden">
                <livewire:search-box />
            </div>

            <!-- Mobile Haber Gönder Button -->
            <a href="{{ route('tip.create') }}" class="sm:hidden flex items-center gap-2 px-3 py-2.5 rounded-lg bg-primary/5 text-primary font-bold text-sm mb-2">
                <span class="material-symbols-outlined text-sm">tips_and_updates</span>
                HABER GÖNDER
            </a>

            <!-- Kategoriler -->
            @foreach($navCategories as $cat)
                <a href="{{ url('/' . $cat->slug) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors" @click="mobileOpen = false">
                    <span class="text-sm font-semibold text-slate-700">{{ $cat->name }}</span>
                </a>
            @endforeach

            <!-- Şehirler (Mobil) -->
            <div class="border-t border-gray-100 pt-2 mt-2">
                <button @click="citiesOpen = !citiesOpen" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                    <span class="text-sm font-bold text-slate-900 uppercase tracking-wide">Şehirler</span>
                    <span class="material-symbols-outlined text-sm text-slate-400 transition-transform" :class="citiesOpen && 'rotate-180'">expand_more</span>
                </button>
                <div x-show="citiesOpen" x-collapse class="grid grid-cols-2 gap-1 px-1 pb-2">
                    @foreach($navCities as $city)
                        <a href="{{ url('/' . $city->slug) }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors" @click="mobileOpen = false">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            <span class="text-sm font-medium text-slate-600">{{ $city->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Header Service Cards Bar (Desktop) -->
<div class="hidden lg:block bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-[1440px] mx-auto px-4">
        <div class="flex items-center justify-center gap-6 py-2">
            @foreach($headerServiceCards as $card)
                <a href="{{ $card['url'] ?? '#' }}" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors group">
                    <span class="material-symbols-outlined text-base text-primary">{{ $card['icon'] ?? 'link' }}</span>
                    <div class="leading-tight">
                        <span class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors">{{ $card['title'] }}</span>
                        <span class="text-[10px] text-slate-400 block">{{ $card['subtitle'] ?? '' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Mobile Bottom Bar (Sabit Alt Menü) -->
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.08)]">
    <div class="grid grid-cols-4 py-2 safe-area-bottom">
        @foreach($mobileBottomCards->take(4) as $card)
            <a href="{{ $card['url'] ?? '#' }}" class="flex flex-col items-center justify-center gap-1 active:bg-gray-100 transition-colors">
                <span class="material-symbols-outlined text-[22px] text-primary">{{ $card['icon'] ?? 'link' }}</span>
                <span class="text-[10px] font-semibold text-slate-600 text-center leading-none">{{ $card['title'] }}</span>
            </a>
        @endforeach
    </div>
</div>

<style>
    /* Logo yüksekliği sabit, genişlik orana göre. Mobilde küçülür. */
    /* Logo yüksekliği sabit, genişlik orana göre. Üst çubuk logoya göre
       yükselir; Tailwind derlemesi sabit olduğu için yükseklik burada verilir. */
    .ga-ustbar { min-height: 96px; }
    .ga-logo { height: 72px; width: auto; display: block; }

    @media (max-width: 1024px) { .ga-ustbar { min-height: 82px; } .ga-logo { height: 60px; } }
    @media (max-width: 640px)  { .ga-ustbar { min-height: 66px; } .ga-logo { height: 46px; } }
</style>
