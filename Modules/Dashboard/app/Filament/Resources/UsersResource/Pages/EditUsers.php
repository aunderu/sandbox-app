<?php

namespace Modules\Dashboard\Filament\Resources\UsersResource\Pages;

use Modules\Dashboard\Filament\Resources\UsersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUsers extends EditRecord
{
    protected static string $resource = UsersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.users.index');
    }
}
