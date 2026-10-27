@php
    $tickerNews = \Illuminate\Support\Facades\Cache::remember('ticker.breaking', 60, function () {
        return \App\Models\Article::published()
            ->where('is_breaking', true)
            ->latest('published_at')
            ->take(5)
            ->get(['id', 'title', 'slug', 'category_id']);
    });
@endphp

@if($tickerNews->isNotEmpty())
<div class="bg-gray-100 border-b border-gray-200 overflow-hidden h-11 flex items-center">
    <div class="flex items-center w-full">
        <div class="flex items-center gap-2 font-bold text-xs tracking-wider uppercase shrink-0 px-5 bg-gray-100 z-10">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
            <span class="text-slate-900">SON DAKİKA</span>
        </div>
        <div class="flex-1 overflow-hidden relative">
            <div class="flex items-center gap-6 text-sm font-medium whitespace-nowrap animate-marquee text-slate-700">
                @foreach($tickerNews as $news)
                    <a href="{{ $news->url }}" class="hover:text-primary transition-colors">{{ $news->title }}</a>
                    <span class="text-gray-400">•</span>
                @endforeach
                {{-- Duplicate for seamless loop --}}
                @foreach($tickerNews as $news)
                    <a href="{{ $news->url }}" class="hover:text-primary transition-colors">{{ $news->title }}</a>
                    <span class="text-gray-400">•</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
