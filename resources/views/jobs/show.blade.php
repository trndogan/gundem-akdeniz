@extends('layouts.app')

@section('title', $job->seo_title ?? $job->title . ' - ' . $job->company_name . ' | Gündem Akdeniz')
@section('meta_description', $job->seo_description ?? $job->company_name . ' firması ' . ($job->city ? $job->city->name . ' ' : '') . $job->employment_type_label . ' ' . $job->title . ' arıyor.')
@section('og_title', $job->seo_title ?? $job->title . ' - ' . $job->company_name)
@section('canonical', $job->url)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <a href="{{ route('job.index') }}" class="hover:text-primary transition-colors">İş İlanları</a>
    <span class="material-symbols-outlined text-xs">chevron_right</span>
    <span class="text-slate-900 font-semibold line-clamp-1">{{ $job->title }}</span>
</nav>

<div class="flex flex-col lg:flex-row gap-8">

{{-- Sol: İlan Detayı --}}
<div class="flex-1 min-w-0">

    {{-- Header --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                @if($job->is_expired)
                <span class="inline-block bg-red-100 text-red-600 text-xs font-bold px-3 py-1 rounded-lg uppercase mb-2">Süresi Doldu</span>
                @elseif($job->is_featured)
                <span class="inline-block bg-amber-100 text-amber-700 text-xs font-bold px-3 py-1 rounded-lg uppercase mb-2">Öne Çıkan İlan</span>
                @endif
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $job->title }}</h1>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">business</span>
                <strong class="text-slate-700">{{ $job->company_name }}</strong>
            </span>
            @if($job->city)
            <span class="inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">location_on</span>
                {{ $job->city->name }}{{ $job->district ? ', ' . $job->district : '' }}
            </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">schedule</span>
                {{ $job->employment_type_label }}
            </span>
            @if($job->sector)
            <span class="inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">category</span>
                {{ $job->sector }}
            </span>
            @endif
            @if($job->salary_text)
            <span class="inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">payments</span>
                <strong class="text-slate-700">{{ $job->salary_text }}</strong>
            </span>
            @endif
        </div>

        @if($job->deadline && !$job->is_expired)
        <div class="mt-4 flex items-center gap-2 text-sm text-orange-600 bg-orange-50 rounded-lg px-3 py-2">
            <span class="material-symbols-outlined text-base">event</span>
            Son başvuru: <strong>{{ $job->deadline->translatedFormat('d F Y') }}</strong>
        </div>
        @endif
    </div>

    {{-- İş Tanımı --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4">İş Tanımı</h2>
        <div class="prose prose-slate max-w-none prose-headings:font-extrabold prose-a:text-primary">
            {!! $job->description !!}
        </div>
    </div>

    {{-- Aranan Nitelikler --}}
    @if($job->requirements)
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4">Aranan Nitelikler</h2>
        <div class="prose prose-slate max-w-none prose-headings:font-extrabold prose-a:text-primary">
            {!! $job->requirements !!}
        </div>
    </div>
    @endif
</div>

{{-- Sağ: Sidebar --}}
<aside class="lg:w-[320px] shrink-0">
    <div class="sticky top-24 space-y-4">

        {{-- Başvuru Butonları --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-900 mb-4">Hemen Başvur</h3>
            <div class="space-y-3">
                @if($job->whatsapp)
                <a href="{{ $job->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-green-500 text-white font-bold rounded-xl hover:bg-green-600 transition-colors text-sm">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp ile Başvur
                </a>
                @endif
                @if($job->phone)
                <a href="tel:{{ $job->phone }}"
                   class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-primary text-white font-bold rounded-xl hover:bg-red-700 transition-colors text-sm">
                    <span class="material-symbols-outlined text-lg">call</span>
                    {{ $job->phone }}
                </a>
                @endif
                @if($job->email)
                <a href="mailto:{{ $job->email }}?subject={{ urlencode($job->title . ' Başvurusu') }}"
                   class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-900 transition-colors text-sm">
                    <span class="material-symbols-outlined text-lg">mail</span>
                    E-posta Gönder
                </a>
                @endif
                @if(!$job->whatsapp && !$job->phone && !$job->email)
                <p class="text-sm text-slate-400 text-center">İletişim bilgisi eklenmemiş.</p>
                @endif
            </div>
        </div>

        {{-- İlan Bilgileri --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-bold text-slate-900 mb-3">İlan Bilgileri</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Yayın Tarihi</dt>
                    <dd class="font-semibold text-slate-700">{{ $job->created_at->translatedFormat('d M Y') }}</dd>
                </div>
                @if($job->deadline)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Son Başvuru</dt>
                    <dd class="font-semibold {{ $job->is_expired ? 'text-red-600' : 'text-slate-700' }}">{{ $job->deadline->translatedFormat('d M Y') }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-500">Görüntülenme</dt>
                    <dd class="font-semibold text-slate-700">{{ number_format($job->view_count) }}</dd>
                </div>
                @if($job->source)
                <div class="flex justify-between">
                    <dt class="text-slate-500">Kaynak</dt>
                    <dd class="font-semibold text-slate-700">{{ $job->source }}</dd>
                </div>
                @endif
            </dl>
        </div>

        @include('partials.ad', ['position' => 'sidebar_top'])
    </div>
</aside>

</div>

{{-- İlgili İlanlar --}}
@if($relatedJobs->isNotEmpty())
<section class="py-10 border-t border-gray-200 mt-10">
    <h2 class="text-xl font-extrabold text-slate-900 mb-6">Benzer İlanlar</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($relatedJobs as $related)
        <a href="{{ $related->url }}" class="group bg-white border border-gray-200 rounded-xl p-4 hover:border-red-200 hover:shadow-md transition-all">
            <h3 class="font-bold text-slate-900 group-hover:text-primary transition-colors mb-1">{{ $related->title }}</h3>
            <p class="text-sm text-slate-500">{{ $related->company_name }}</p>
            <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                @if($related->city)
                <span>{{ $related->city->name }}</span>
                @endif
                <span>{{ $related->employment_type_label }}</span>
            </div>
            @if($related->salary_text)
            <p class="text-sm font-bold text-slate-900 mt-2">{{ $related->salary_text }}</p>
            @endif
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'İş İlanları', 'item' => route('job.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $job->title],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

{{-- JobPosting JSON-LD --}}
<script type="application/ld+json">
@php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'JobPosting',
    'title' => $job->title,
    'description' => strip_tags($job->description),
    'datePosted' => $job->created_at->format('Y-m-d'),
    'employmentType' => strtoupper(str_replace('-', '_', $job->employment_type)),
    'hiringOrganization' => [
        '@type' => 'Organization',
        'name' => $job->company_name,
    ],
    'jobLocation' => [
        '@type' => 'Place',
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $job->city ? $job->city->name : 'Akdeniz Bölgesi',
            'addressRegion' => $job->district ?? ($job->city ? $job->city->name : ''),
            'addressCountry' => 'TR',
        ],
    ],
];

if ($job->deadline) {
    $schema['validThrough'] = $job->deadline->format('Y-m-d') . 'T23:59:59+03:00';
}

if ($job->salary_min || $job->salary_max) {
    $schema['baseSalary'] = [
        '@type' => 'MonetaryAmount',
        'currency' => 'TRY',
        'value' => [
            '@type' => 'QuantitativeValue',
            'unitText' => 'MONTH',
        ],
    ];
    if ($job->salary_min && $job->salary_max) {
        $schema['baseSalary']['value']['minValue'] = (float) $job->salary_min;
        $schema['baseSalary']['value']['maxValue'] = (float) $job->salary_max;
    } elseif ($job->salary_min) {
        $schema['baseSalary']['value']['value'] = (float) $job->salary_min;
    } else {
        $schema['baseSalary']['value']['value'] = (float) $job->salary_max;
    }
}

echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>

@endsection
