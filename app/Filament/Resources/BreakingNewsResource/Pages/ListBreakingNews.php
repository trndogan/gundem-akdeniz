<?php

namespace App\Filament\Resources\BreakingNewsResource\Pages;

use App\Filament\Resources\BreakingNewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBreakingNews extends ListRecords
{
    protected static string $resource = BreakingNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
