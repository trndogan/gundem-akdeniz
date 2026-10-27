<?php

namespace App\Filament\Resources\BreakingNewsResource\Pages;

use App\Filament\Resources\BreakingNewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBreakingNews extends EditRecord
{
    protected static string $resource = BreakingNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
