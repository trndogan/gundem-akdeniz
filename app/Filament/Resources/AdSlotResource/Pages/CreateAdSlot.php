<?php

namespace App\Filament\Resources\AdSlotResource\Pages;

use App\Filament\Resources\AdSlotResource;
use App\Filament\Support\DeferredImageUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateAdSlot extends CreateRecord
{
    protected static string $resource = AdSlotResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        DeferredImageUpload::consume($data, '_deferred_ad_image', 'image_url', 'ads');

        return $data;
    }
}
