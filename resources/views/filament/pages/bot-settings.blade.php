<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit">
                Ayarları kaydet
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="resetPrompt">
                Talimatı varsayılana döndür
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
