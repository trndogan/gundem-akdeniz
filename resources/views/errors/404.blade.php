@extends('layouts.app')

@section('title', 'Sayfa Bulunamadı - 404 | Gündem Akdeniz')
@section('meta_description', 'Aradığınız sayfa bulunamadı. Ana sayfaya dönebilir veya aşağıdaki bağlantıları kullanabilirsiniz.')

@section('content')
<div class="flex flex-col items-center justify-center py-20 text-center">
    {{-- 404 Büyük Numara --}}
    <div class="relative mb-8">
        <span class="text-[160px] md:text-[200px] font-black text-slate-100 leading-none select-none">404</span>
        <div class="absolute inset-0 flex items-center justify-center">
            <span class="material-symbols-outlined text-6xl md:text-7xl text-primary">search_off</span>
        </div>
    </div>

    {{-- Mesaj --}}
    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 mb-3">Sayfa Bulunamadı</h1>
    <p class="text-slate-500 max-w-md mb-8 leading-relaxed">
        Aradığınız sayfa kaldırılmış, adı değiştirilmiş veya geçici olarak kullanım dışı olabilir.
    </p>

    {{-- Butonlar --}}
    <div class="flex flex-col sm:flex-row items-center gap-3 mb-12">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-primary text-white font-bold px-6 py-3 rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-500/20">
            <span class="material-symbols-outlined text-lg">home</span>
            Ana Sayfaya Dön
        </a>
        <a href="{{ route('mayor.index') }}" class="inline-flex items-center gap-2 bg-slate-100 text-slate-700 font-bold px-6 py-3 rounded-xl hover:bg-slate-200 transition-colors">
            <span class="material-symbols-outlined text-lg">groups</span>
            Belediye Başkanları
        </a>
    </div>

    {{-- Hızlı Bağlantılar --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 max-w-lg w-full">
        <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Popüler Sayfalar</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm text-slate-600 hover:text-primary transition-colors p-2 rounded-lg hover:bg-slate-50">
                <span class="material-symbols-outlined text-base text-slate-400">newspaper</span>
                Son Haberler
            </a>
            <a href="{{ route('pharmacy.index') }}" class="flex items-center gap-2 text-sm text-slate-600 hover:text-primary transition-colors p-2 rounded-lg hover:bg-slate-50">
                <span class="material-symbols-outlined text-base text-slate-400">local_pharmacy</span>
                Nöbetçi Eczane
            </a>
            <a href="{{ route('weather.index') }}" class="flex items-center gap-2 text-sm text-slate-600 hover:text-primary transition-colors p-2 rounded-lg hover:bg-slate-50">
                <span class="material-symbols-outlined text-base text-slate-400">cloud</span>
                Hava Durumu
            </a>
            <a href="{{ route('mayor.index') }}" class="flex items-center gap-2 text-sm text-slate-600 hover:text-primary transition-colors p-2 rounded-lg hover:bg-slate-50">
                <span class="material-symbols-outlined text-base text-slate-400">account_balance</span>
                Başkanlar
            </a>
        </div>
    </div>
</div>
@endsection
