<section class="py-12 border-t border-gray-200" wire:key="city-news-section">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">Bölgesel Haberler</h2>
            <p class="text-slate-600 mt-1">Akdeniz kıyılarından yerel haberleri keşfedin</p>
        </div>

        <!-- Tabbed Cities -->
        <div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200 overflow-x-auto no-scrollbar gap-1">
            @foreach($cities as $city)
                <button
                    wire:click="selectCity({{ $city->id }})"
                    class="px-5 py-2 text-sm font-bold rounded-md transition-all whitespace-nowrap
                        {{ $selectedCityId === $city->id ? 'bg-primary text-white shadow-lg' : 'hover:bg-white text-slate-600 hover:text-slate-900' }}"
                >
                    {{ $city->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- City News Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" wire:loading.class="opacity-50">
        @forelse($articles as $article)
        <a href="{{ $article->url }}" class="group cursor-pointer block" wire:key="city-article-{{ $article->id }}">
            <div class="relative aspect-video rounded-xl overflow-hidden mb-4 border border-gray-200 shadow-sm bg-slate-900">
                <img src="{{ $article->image_display_url }}" alt="{{ $article->title }}" class="absolute inset-0 w-full h-full object-contain group-hover:scale-110 transition-transform duration-500" loading="lazy">
                <div class="absolute top-3 left-3 bg-black/70 backdrop-blur-md px-2 py-1 rounded text-[10px] font-bold uppercase text-white">{{ $article->category->name }}</div>
            </div>
            <h4 class="font-bold text-lg leading-tight mb-2 text-slate-900 group-hover:text-primary transition-colors">
                {{ $article->title }}
            </h4>
            @if($article->excerpt)
            <p class="text-slate-600 text-sm line-clamp-2">
                {{ $article->excerpt }}
            </p>
            @endif
            <div class="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
                <span>{{ $article->city?->name ?? '' }}</span>
                <span>{{ $article->published_at->diffForHumans() }}</span>
            </div>
        </a>
        @empty
        <div class="col-span-4 text-center py-12 text-slate-500">
            <span class="material-symbols-outlined text-4xl mb-2 block">article</span>
            <p>Bu şehir için henüz haber bulunmuyor.</p>
        </div>
        @endforelse
    </div>
</section>
