@php
    $ads = \App\Models\AdSlot::active()->position($position)->get();
@endphp

@foreach($ads as $ad)
<div class="ad-slot ad-{{ $position }} my-4">
    @if($ad->code)
        {!! $ad->code !!}
    @elseif($ad->image_display_url)
        @if($ad->link_url)
            <a href="{{ $ad->link_url }}" target="_blank" rel="noopener noreferrer sponsored">
                <img src="{{ $ad->image_display_url }}" alt="{{ $ad->name }}" class="w-full rounded-lg" loading="lazy">
            </a>
        @else
            <img src="{{ $ad->image_display_url }}" alt="{{ $ad->name }}" class="w-full rounded-lg" loading="lazy">
        @endif
    @endif
</div>
@endforeach
