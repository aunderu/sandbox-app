<?php

namespace Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\InnovationsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditInnovations extends EditRecord
{
    protected static string $resource = InnovationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.innovations.index');
    }
}
