<?php

namespace Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;

use Modules\Dashboard\Filament\Resources\InnovationsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInnovations extends EditRecord
{
    protected static string $resource = InnovationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.innovations.index');
    }
}
