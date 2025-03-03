<?php

namespace Modules\Dashboard\Filament\Resources\InnovationTypesResource\Pages;

use Modules\Dashboard\Filament\Resources\InnovationTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInnovationTypes extends ListRecords
{
    protected static string $resource = InnovationTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
