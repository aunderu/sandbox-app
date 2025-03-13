<?php

namespace Modules\Dashboard\Filament\Resources\SchoolResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.schools.index');
    }
}
