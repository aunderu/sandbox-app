<?php

namespace Modules\Dashboard\Filament\Resources\InnovationTypesResource\Pages;

use Modules\Dashboard\Filament\Resources\InnovationTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInnovationTypes extends EditRecord
{
    protected static string $resource = InnovationTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.innovation-types.index');
    }
}
