<?php

namespace Modules\Dashboard\Filament\Resources\InnovationTypesResource\Pages;

use Modules\Dashboard\Filament\Resources\InnovationTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInnovationTypes extends CreateRecord
{
    protected static string $resource = InnovationTypesResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.innovation-types.index');
    }
}

