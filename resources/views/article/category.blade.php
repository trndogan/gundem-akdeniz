@extends('layouts.app')

@section('title', $category->seo_title ?? $category->name . ' Haberleri - Gündem Akdeniz')
@section('meta_description', $category->seo_description ?? $category->description ?? $category->name . ' kategorisindeki en güncel haberler. Gündem Akdeniz.')
@section('og_title', $category->seo_title ?? $category->name . ' Haberleri - Gündem Akdeniz')
@section('canonical', url('/' . $category->slug))
@if($articles->currentPage() > 1)
@section('noindex', true)
@section('canonical', url()->current())
@endif

@section('content')

<div class="mb-8">
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-4">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-slate-900 font-semibold">{{ $category->name }}</span>
    </nav>
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $category->name }}</h1>
    @if($category->description)
    <p class="text-slate-600 mt-2">{{ $category->description }}</p>
    @endif
</div>

<div id="ga-liste" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    @forelse($articles as $article)
    <a href="{{ $article->url }}" class="group cursor-pointer block">
        <div class="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm">
            <img src="{{ $article->image_display_url }}" alt="{{ $article->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
            @if($article->city)
            <div class="absolute top-3 left-3 bg-black/70 backdrop-blur-md px-2 py-1 rounded text-[10px] font-bold uppercase text-white">{{ $article->city->name }}</div>
            @endif
        </div>
        <h4 class="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
            {{ $article->title }}
        </h4>
        @if($article->excerpt)
        <p class="text-slate-600 text-sm line-clamp-2">{{ $article->excerpt }}</p>
        @endif
        <div class="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
            <span>{{ $article->city?->name ?? $article->category->name }}</span>
            <span>{{ $article->published_at->diffForHumans() }}</span>
        </div>
    </a>
    @empty
    <div class="col-span-4 text-center py-16 text-slate-500">
        <span class="material-symbols-outlined text-5xl mb-3 block">article</span>
        <p class="text-lg">Bu kategoride henüz haber bulunmuyor.</p>
    </div>
    @endforelse
</div>

@include('partials.sonsuz-kaydirma', ['liste' => 'ga-liste', 'sayfa' => $articles])

@if($category->content)
<div class="bg-slate-50 border border-gray-200 rounded-xl p-6 mt-10 mb-10 prose prose-slate max-w-none prose-headings:font-extrabold prose-headings:text-slate-900 prose-a:text-primary">
    {!! $category->content !!}
</div>
@endif

@include('partials.ad', ['position' => 'content_between'])

{{-- Breadcrumb JSON-LD --}}
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $category->name],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
</script>

@endsection
