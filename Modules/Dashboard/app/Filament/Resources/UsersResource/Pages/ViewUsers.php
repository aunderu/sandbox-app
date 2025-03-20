<?php

namespace Modules\Dashboard\Filament\Resources\UsersResource\Pages;

use Modules\Dashboard\Filament\Resources\UsersResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewUsers extends ViewRecord
{
    protected static string $resource = UsersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}