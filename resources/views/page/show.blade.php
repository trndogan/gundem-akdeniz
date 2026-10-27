@extends('layouts.app')

@section('title', $page->seo_title ?? $page->title . ' - Gündem Akdeniz')
@section('meta_description', $page->seo_description ?? Str::limit(strip_tags($page->body), 160))
@section('meta_keywords', $page->seo_keywords ?? '')

@section('content')

<div class="max-w-4xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Ana Sayfa</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-slate-900 font-semibold">{{ $page->title }}</span>
    </nav>

    <h1 class="text-3xl lg:text-4xl font-extrabold leading-tight tracking-tight text-slate-900 mb-8">
        {{ $page->title }}
    </h1>

    <div class="article-body max-w-none">
        {!! strip_tags($page->body, '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><a><strong><b><em><i><u><s><del><blockquote><figure><figcaption><img><table><thead><tbody><tfoot><tr><th><td><caption><hr><pre><code><sup><sub><span><iframe><video><source>') !!}
    </div>
</div>

@endsection
