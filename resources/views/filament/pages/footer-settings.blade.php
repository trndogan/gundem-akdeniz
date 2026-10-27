<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
