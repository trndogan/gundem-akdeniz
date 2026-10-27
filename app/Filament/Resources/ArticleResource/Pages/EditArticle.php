<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Filament\Support\DeferredImageUpload;
use App\Helpers\PathHelper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected ?string $oldImageUrlToDelete = null;
    protected ?string $newImageUrl = null;
    protected bool $shouldDeleteOldOnSave = false;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tmp = $data['_deferred_image'] ?? null;
        $this->oldImageUrlToDelete = $this->record?->image_url;

        DeferredImageUpload::consume($data, '_deferred_image', 'image_url', 'articles');

        // Dosya seçildiyse eski görseli silmek için bilgileri saklıyoruz.
        $this->shouldDeleteOldOnSave = $tmp instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
        if ($this->shouldDeleteOldOnSave) {
            $this->newImageUrl = $data['image_url'] ?? null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->shouldDeleteOldOnSave) {
            return;
        }

        if (filled($this->oldImageUrlToDelete) && $this->oldImageUrlToDelete !== ($this->newImageUrl ?? null)) {
            PathHelper::deleteStoredUploadFile($this->oldImageUrlToDelete);
        }

        $this->oldImageUrlToDelete = null;
        $this->newImageUrl = null;
        $this->shouldDeleteOldOnSave = false;
    }
}
