<?php

namespace Modules\Dashboard\Filament\Resources\UsersResource\Pages;

use Modules\Dashboard\Filament\Resources\UsersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUsers extends EditRecord
{
    protected static string $resource = UsersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(function () {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    return $user->isSuperAdmin();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.users.index');
    }
}
