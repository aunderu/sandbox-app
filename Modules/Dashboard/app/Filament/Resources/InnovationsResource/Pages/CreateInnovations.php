<?php

namespace Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;

use Modules\Dashboard\Filament\Resources\InnovationsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInnovations extends CreateRecord
{
    protected static string $resource = InnovationsResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.innovations.index');
    }
}
