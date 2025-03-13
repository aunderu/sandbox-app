<?php

namespace Modules\Dashboard\Filament\Resources\InnovationTypesResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\InnovationTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListInnovationTypes extends ListRecords
{
    protected static string $resource = InnovationTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN),
        ];
    }
}
