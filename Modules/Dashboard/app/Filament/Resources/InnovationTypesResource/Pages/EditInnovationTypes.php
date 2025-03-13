<?php

namespace Modules\Dashboard\Filament\Resources\InnovationTypesResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\InnovationTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditInnovationTypes extends EditRecord
{
    protected static string $resource = InnovationTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.innovation-types.index');
    }
}
