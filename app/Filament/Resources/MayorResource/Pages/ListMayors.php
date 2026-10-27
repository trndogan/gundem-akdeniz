<?php

namespace App\Filament\Resources\MayorResource\Pages;

use App\Filament\Resources\MayorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMayors extends ListRecords
{
    protected static string $resource = MayorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
