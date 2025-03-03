<?php

namespace Modules\Dashboard\Filament\Resources\SchoolResource\Pages;

use Modules\Dashboard\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.schools.index');
    }
}
