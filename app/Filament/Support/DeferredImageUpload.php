<?php

namespace App\Filament\Support;

use App\Services\PublicImageService;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class DeferredImageUpload
{
    /**
     * Geçici yüklemeyi WebP olarak public/images altına taşır ve $targetKey alanına yazar.
     */
    public static function consume(array &$data, string $uploadKey, string $targetKey, string $subdir): void
    {
        $tmp = $data[$uploadKey] ?? null;

        if (! $tmp instanceof TemporaryUploadedFile) {
            unset($data[$uploadKey]);

            return;
        }

        try {
            $data[$targetKey] = app(PublicImageService::class)->saveAsWebp($tmp, $subdir);
        } finally {
            $tmp->delete();
        }

        unset($data[$uploadKey]);
    }
}
