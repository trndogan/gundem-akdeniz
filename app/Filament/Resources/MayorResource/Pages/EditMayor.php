<?php

namespace App\Filament\Resources\MayorResource\Pages;

use App\Filament\Resources\MayorResource;
use App\Filament\Support\DeferredImageUpload;
use App\Helpers\PathHelper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMayor extends EditRecord
{
    protected static string $resource = MayorResource::class;

    protected ?string $oldPhotoUrlToDelete = null;
    protected ?string $newPhotoUrl = null;
    protected bool $shouldDeleteOldOnSave = false;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tmp = $data['_deferred_photo'] ?? null;
        $this->oldPhotoUrlToDelete = $this->record?->photo_url;

        DeferredImageUpload::consume($data, '_deferred_photo', 'photo_url', 'mayors');

        // Dosya seçildiyse eski görseli silmek için bilgileri saklıyoruz.
        $this->shouldDeleteOldOnSave = $tmp instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
        if ($this->shouldDeleteOldOnSave) {
            $this->newPhotoUrl = $data['photo_url'] ?? null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->shouldDeleteOldOnSave) {
            return;
        }

        if (filled($this->oldPhotoUrlToDelete) && $this->oldPhotoUrlToDelete !== ($this->newPhotoUrl ?? null)) {
            PathHelper::deleteStoredUploadFile($this->oldPhotoUrlToDelete);
        }

        $this->oldPhotoUrlToDelete = null;
        $this->newPhotoUrl = null;
        $this->shouldDeleteOldOnSave = false;
    }
}
