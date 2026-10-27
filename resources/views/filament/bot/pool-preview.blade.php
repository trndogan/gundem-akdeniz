{{-- Havuzdaki ham haberin önizlemesi: kaynaktan ne çekildiği burada görülür --}}
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
        <div>
            <div class="text-gray-500 dark:text-gray-400">Kaynak</div>
            <div class="font-medium text-gray-950 dark:text-white">{{ $item->source?->name ?? '—' }}</div>
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">Kelime</div>
            <div class="font-medium text-gray-950 dark:text-white">{{ $item->word_count }}</div>
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">Kaynak tarihi</div>
            <div class="font-medium text-gray-950 dark:text-white">
                {{ $item->source_published_at?->format('d.m.Y') ?? '—' }}
            </div>
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">Durum</div>
            <div class="font-medium text-gray-950 dark:text-white">{{ $item->status }}</div>
        </div>
    </div>

    @if ($item->image_url)
        <img src="{{ $item->image_url }}" alt="" class="w-full rounded-lg" style="max-height:320px;object-fit:cover">
    @else
        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-700">
            Kaynakta görsel bulunamadı. Kapak yapay zekâ ile ya da marka kartıyla üretilecek.
        </div>
    @endif

    <div>
        <div class="mb-1 text-sm text-gray-500 dark:text-gray-400">Çekilen başlık</div>
        <div class="rounded-lg bg-gray-50 p-3 font-medium text-gray-950 dark:bg-gray-900 dark:text-white">
            {{ $item->title }}
        </div>
    </div>

    <div>
        <div class="mb-1 text-sm text-gray-500 dark:text-gray-400">Çekilen gövde</div>
        <div class="max-h-96 overflow-y-auto whitespace-pre-line rounded-lg bg-gray-50 p-3 text-sm leading-relaxed text-gray-700 dark:bg-gray-900 dark:text-gray-300">
            {{ $item->content }}
        </div>
    </div>

    @if ($item->reject_reason)
        <div class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-950 dark:text-danger-400">
            {{ $item->reject_reason }}
        </div>
    @endif

    <div class="text-xs text-gray-500 dark:text-gray-400">
        <a href="{{ $item->url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline">
            Haberi kaynağında aç
        </a>
    </div>
</div>
