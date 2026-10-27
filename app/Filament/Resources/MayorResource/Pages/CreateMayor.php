<?php

namespace App\Filament\Resources\MayorResource\Pages;

use App\Filament\Resources\MayorResource;
use App\Filament\Support\DeferredImageUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateMayor extends CreateRecord
{
    protected static string $resource = MayorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        DeferredImageUpload::consume($data, '_deferred_photo', 'photo_url', 'mayors');

        return $data;
    }
}
