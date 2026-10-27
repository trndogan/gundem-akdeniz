<?php

namespace App\Filament\Resources\PartyResource\Pages;

use App\Filament\Resources\PartyResource;
use App\Filament\Support\DeferredImageUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateParty extends CreateRecord
{
    protected static string $resource = PartyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        DeferredImageUpload::consume($data, '_deferred_logo', 'logo_url', 'parties');

        return $data;
    }
}
