<?php

namespace Modules\Dashboard\Filament\Resources\UsersResource\Pages;

use Modules\Dashboard\Filament\Resources\UsersResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UsersResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->visible(function () {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                return $user->isSuperAdmin();
            }),
        ];
    }
}
