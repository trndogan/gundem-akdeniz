@extends('layouts.app')

@section('title', 'Ücretsiz İlan Ver - İş İlanları | Gündem Akdeniz')
@section('meta_description', 'Akdeniz Bölgesi\'nde ücretsiz iş ilanı verin. Antalya, Mersin, Adana ve çevre illerde binlerce iş arayana ulaşın.')
@section('canonical', route('job.create'))

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="{{ route('job.index') }}" class="hover:text-primary transition-colors">İş İlanları</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold">İlan Ver</span>
</nav>

<div class="max-w-2xl mx-auto">

    <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Ücretsiz İlan Ver</h1>
        <p class="text-slate-600 mt-2">İlanınız yönetici onayından sonra yayınlanacaktır.</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-2 text-red-700 font-bold text-sm mb-2">
            <span class="material-symbols-outlined text-lg">error</span>
            Lütfen hataları düzeltin
        </div>
        <ul class="text-sm text-red-600 space-y-1 ml-7">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('job.store') }}" class="space-y-6">
        @csrf

        {{-- Pozisyon & Şirket --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">work</span>
                Pozisyon Bilgileri
            </h2>
            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">Pozisyon Adı <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary @error('title') border-red-300 @enderror"
                           placeholder="Örn: Garson, Muhasebeci, Yazılım Geliştirici">
                </div>
                <div>
                    <label for="company_name" class="block text-sm font-semibold text-slate-700 mb-1">Şirket / İşveren Adı <span class="text-red-500">*</span></label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary @error('company_name') border-red-300 @enderror"
                           placeholder="Şirket veya işletme adı">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="sector" class="block text-sm font-semibold text-slate-700 mb-1">Sektör</label>
                        <input type="text" name="sector" id="sector" value="{{ old('sector') }}"
                               class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="Örn: Turizm, İnşaat">
                    </div>
                    <div>
                        <label for="employment_type" class="block text-sm font-semibold text-slate-700 mb-1">Çalışma Türü <span class="text-red-500">*</span></label>
                        <select name="employment_type" id="employment_type" required
                                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="full-time" {{ old('employment_type') == 'full-time' ? 'selected' : '' }}>Tam Zamanlı</option>
                            <option value="part-time" {{ old('employment_type') == 'part-time' ? 'selected' : '' }}>Yarı Zamanlı</option>
                            <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>Sözleşmeli</option>
                            <option value="internship" {{ old('employment_type') == 'internship' ? 'selected' : '' }}>Staj</option>
                            <option value="freelance" {{ old('employment_type') == 'freelance' ? 'selected' : '' }}>Freelance</option>
                            <option value="temporary" {{ old('employment_type') == 'temporary' ? 'selected' : '' }}>Geçici</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Konum --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">location_on</span>
                Konum
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="city_id" class="block text-sm font-semibold text-slate-700 mb-1">Şehir</label>
                    <select name="city_id" id="city_id"
                            class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Seçiniz</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="district" class="block text-sm font-semibold text-slate-700 mb-1">İlçe</label>
                    <input type="text" name="district" id="district" value="{{ old('district') }}"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="Örn: Muratpaşa, Mezitli">
                </div>
            </div>
        </div>

        {{-- Maaş --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">payments</span>
                Maaş Bilgisi <span class="text-xs text-slate-400 font-normal">(Opsiyonel)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="salary_min" class="block text-sm font-semibold text-slate-700 mb-1">Minimum (TL)</label>
                    <input type="number" name="salary_min" id="salary_min" value="{{ old('salary_min') }}"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="Örn: 25000">
                </div>
                <div>
                    <label for="salary_max" class="block text-sm font-semibold text-slate-700 mb-1">Maksimum (TL)</label>
                    <input type="number" name="salary_max" id="salary_max" value="{{ old('salary_max') }}"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="Örn: 40000">
                </div>
            </div>
        </div>

        {{-- İş Tanımı --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">description</span>
                İş Tanımı
            </h2>
            <div>
                <label for="description" class="block text-sm font-semibold text-slate-700 mb-1">Açıklama <span class="text-red-500">*</span></label>
                <textarea name="description" id="description" rows="6" required
                          class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary @error('description') border-red-300 @enderror"
                          placeholder="İş tanımı, görevler, aranan nitelikler, çalışma saatleri vb.">{{ old('description') }}</textarea>
                <p class="text-xs text-slate-400 mt-1">En az 30 karakter yazınız.</p>
            </div>
            <div class="mt-4">
                <label for="deadline" class="block text-sm font-semibold text-slate-700 mb-1">Son Başvuru Tarihi</label>
                <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}"
                       class="w-full sm:w-auto border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                       min="{{ now()->addDay()->format('Y-m-d') }}">
            </div>
        </div>

        {{-- İletişim --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">contact_phone</span>
                İletişim Bilgileri
            </h2>
            <p class="text-xs text-slate-500 mb-4">En az bir iletişim bilgisi giriniz.</p>
            <div class="space-y-4">
                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1">Telefon</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="05XX XXX XX XX">
                </div>
                <div>
                    <label for="whatsapp" class="block text-sm font-semibold text-slate-700 mb-1">WhatsApp</label>
                    <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp') }}"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="905XXXXXXXXX (ülke kodu ile)">
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">E-posta</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="ornek@firma.com">
                </div>
            </div>
        </div>

        {{-- Gönder --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('job.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition-colors">← İlanlara Dön</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white font-bold px-8 py-3 rounded-xl hover:bg-red-700 transition-colors text-sm">
                <span class="material-symbols-outlined text-lg">send</span>
                İlanı Gönder
            </button>
        </div>
    </form>

</div>

@endsection
