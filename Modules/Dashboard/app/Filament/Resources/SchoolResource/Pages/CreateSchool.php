<?php

namespace Modules\Dashboard\Filament\Resources\SchoolResource\Pages;

use Modules\Dashboard\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.schools.index');
    }
}
