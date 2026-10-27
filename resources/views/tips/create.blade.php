@extends('layouts.app')

@section('title', 'Haber Gönder - Gündem Akdeniz')
@section('meta_description', 'Akdeniz Bölgesi ile ilgili haber ihbarınızı bize gönderin. Editör ekibimiz en kısa sürede inceleyecektir.')
@section('canonical', route('tip.create'))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">Haber Gönder</span>
</nav>

<div class="max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-primary text-3xl">tips_and_updates</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Haber Gönder</h1>
        <p class="text-slate-600 mt-2">Çevrenizdeki önemli olayları, haberleri veya ihbarları bize iletin.<br>Editör ekibimiz en kısa sürede inceleyecektir.</p>
    </div>

    {{-- Başarı Mesajı --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 flex items-start gap-3">
        <span class="material-symbols-outlined text-emerald-600 text-xl mt-0.5">check_circle</span>
        <div>
            <p class="font-bold text-emerald-800 text-sm">Gönderildi!</p>
            <p class="text-emerald-700 text-sm">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    {{-- Hata Mesajları --}}
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-red-500 text-xl mt-0.5">error</span>
            <div>
                <p class="font-bold text-red-800 text-sm">Lütfen aşağıdaki hataları düzeltin:</p>
                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('tip.store') }}" method="POST" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 shadow-sm">
        @csrf

        {{-- Kişisel Bilgiler --}}
        <div class="mb-8">
            <h2 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-primary">person</span>
                Kişisel Bilgiler
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-bold text-slate-700 mb-1.5">Ad Soyad <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('name') border-red-300 @enderror"
                        placeholder="Adınız Soyadınız">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-bold text-slate-700 mb-1.5">Telefon</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('phone') border-red-300 @enderror"
                        placeholder="05XX XXX XX XX">
                </div>
            </div>
            <div class="mt-4">
                <label for="email" class="block text-sm font-bold text-slate-700 mb-1.5">E-posta</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('email') border-red-300 @enderror"
                    placeholder="ornek@email.com">
                <p class="text-xs text-slate-400 mt-1">Sizinle iletişime geçmemiz için gereklidir.</p>
            </div>
        </div>

        {{-- Haber Detayları --}}
        <div class="mb-8">
            <h2 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-primary">article</span>
                Haber Detayları
            </h2>
            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-bold text-slate-700 mb-1.5">Haber Başlığı <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('title') border-red-300 @enderror"
                        placeholder="Haberin kısa başlığı">
                </div>
                <div>
                    <label for="city_id" class="block text-sm font-bold text-slate-700 mb-1.5">İl</label>
                    <select name="city_id" id="city_id"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white @error('city_id') border-red-300 @enderror">
                        <option value="">İl seçin (opsiyonel)</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="content" class="block text-sm font-bold text-slate-700 mb-1.5">Haber İçeriği <span class="text-red-500">*</span></label>
                    <textarea name="content" id="content" rows="8" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-y @error('content') border-red-300 @enderror"
                        placeholder="Haberin detaylarını yazın. Ne oldu, nerede oldu, ne zaman oldu gibi bilgileri paylaşın...">{{ old('content') }}</textarea>
                    <p class="text-xs text-slate-400 mt-1">En az birkaç cümle yazmanız değerlendirme sürecini hızlandırır.</p>
                </div>
                <div>
                    <label for="image" class="block text-sm font-bold text-slate-700 mb-1.5">Fotoğraf / Görsel</label>
                    <div class="relative">
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                            class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-all border border-gray-200 rounded-xl cursor-pointer @error('image') border-red-300 @enderror">
                    </div>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG veya WebP formatında, en fazla 5 MB.</p>
                </div>
            </div>
        </div>

        {{-- Bilgilendirme --}}
        <div class="bg-slate-50 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-slate-400 text-lg mt-0.5">info</span>
                <div class="text-xs text-slate-500 leading-relaxed">
                    <p class="font-bold text-slate-600 mb-1">Bilgilendirme</p>
                    <ul class="space-y-0.5">
                        <li>• Gönderdiğiniz ihbar editör ekibimiz tarafından incelenecektir.</li>
                        <li>• Onaylanan haberler sitemizde yayınlanabilir.</li>
                        <li>• Kişisel bilgileriniz üçüncü kişilerle paylaşılmaz.</li>
                        <li>• Asılsız veya yanıltıcı ihbarlar yasal sorumluluk doğurabilir.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Gönder --}}
        <button type="submit"
            class="w-full bg-primary hover:bg-red-700 text-white font-bold py-3.5 px-6 rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm hover:shadow-md">
            <span class="material-symbols-outlined text-xl">send</span>
            Haber İhbarını Gönder
        </button>
    </form>

    {{-- Alt Bilgi --}}
    <div class="text-center mt-6 text-xs text-slate-400">
        <p>Acil durumlarda lütfen 112'yi arayın. Bu form acil ihbar hattı değildir.</p>
    </div>
</div>

@endsection
