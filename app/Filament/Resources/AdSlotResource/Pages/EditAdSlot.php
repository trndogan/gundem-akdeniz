<?php

namespace App\Filament\Resources\AdSlotResource\Pages;

use App\Filament\Resources\AdSlotResource;
use App\Filament\Support\DeferredImageUpload;
use App\Helpers\PathHelper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdSlot extends EditRecord
{
    protected static string $resource = AdSlotResource::class;

    protected ?string $oldAdImageUrlToDelete = null;
    protected ?string $newAdImageUrl = null;
    protected bool $shouldDeleteOldOnSave = false;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tmp = $data['_deferred_ad_image'] ?? null;
        $this->oldAdImageUrlToDelete = $this->record?->image_url;

        DeferredImageUpload::consume($data, '_deferred_ad_image', 'image_url', 'ads');

        // Dosya seçildiyse eski görseli silmek için bilgileri saklıyoruz.
        $this->shouldDeleteOldOnSave = $tmp instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
        if ($this->shouldDeleteOldOnSave) {
            $this->newAdImageUrl = $data['image_url'] ?? null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->shouldDeleteOldOnSave) {
            return;
        }

        if (filled($this->oldAdImageUrlToDelete) && $this->oldAdImageUrlToDelete !== ($this->newAdImageUrl ?? null)) {
            PathHelper::deleteStoredUploadFile($this->oldAdImageUrlToDelete);
        }

        $this->oldAdImageUrlToDelete = null;
        $this->newAdImageUrl = null;
        $this->shouldDeleteOldOnSave = false;
    }
}
