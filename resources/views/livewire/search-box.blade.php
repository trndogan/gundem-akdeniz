<div class="relative" x-data="{ focused: false }" @click.away="focused = false">
    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
    <input
        wire:model.live.debounce.300ms="query"
        @focus="focused = true"
        class="bg-white border border-gray-200 rounded-full pl-10 pr-4 py-1.5 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none w-64 transition-all"
        placeholder="Haber Ara..."
        type="text"
    />

    @if($showResults && $results->count() > 0)
    <div x-show="focused" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden z-50">
        @foreach($results as $result)
        <a href="{{ $result->url }}" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0">
            <div class="shrink-0">
                <span class="text-primary text-[10px] font-bold uppercase">{{ $result->category->name }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ $result->title }}</p>
            </div>
        </a>
        @endforeach
    </div>
    @elseif($showResults && $results->count() === 0)
    <div x-show="focused" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 p-4 z-50">
        <p class="text-sm text-slate-500 text-center">Sonuç bulunamadı.</p>
    </div>
    @endif
</div>
