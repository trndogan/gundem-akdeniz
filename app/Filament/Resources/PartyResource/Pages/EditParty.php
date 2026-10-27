<?php

namespace App\Filament\Resources\PartyResource\Pages;

use App\Filament\Resources\PartyResource;
use App\Filament\Support\DeferredImageUpload;
use App\Helpers\PathHelper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParty extends EditRecord
{
    protected static string $resource = PartyResource::class;

    protected ?string $oldLogoUrlToDelete = null;
    protected ?string $newLogoUrl = null;
    protected bool $shouldDeleteOldOnSave = false;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tmp = $data['_deferred_logo'] ?? null;
        $this->oldLogoUrlToDelete = $this->record?->logo_url;

        DeferredImageUpload::consume($data, '_deferred_logo', 'logo_url', 'parties');

        // Dosya seçildiyse eski görseli silmek için bilgileri saklıyoruz.
        $this->shouldDeleteOldOnSave = $tmp instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
        if ($this->shouldDeleteOldOnSave) {
            $this->newLogoUrl = $data['logo_url'] ?? null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->shouldDeleteOldOnSave) {
            return;
        }

        if (filled($this->oldLogoUrlToDelete) && $this->oldLogoUrlToDelete !== ($this->newLogoUrl ?? null)) {
            PathHelper::deleteStoredUploadFile($this->oldLogoUrlToDelete);
        }

        $this->oldLogoUrlToDelete = null;
        $this->newLogoUrl = null;
        $this->shouldDeleteOldOnSave = false;
    }
}
