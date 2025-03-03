<?php

namespace Modules\Dashboard\Filament\Resources\UsersResource\Pages;

use Modules\Dashboard\Filament\Resources\UsersResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUsers extends CreateRecord
{
    protected static string $resource = UsersResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.users.index');
    }
}
