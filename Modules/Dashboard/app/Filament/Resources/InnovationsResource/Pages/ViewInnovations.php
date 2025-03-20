<?php

namespace Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;

use Modules\Dashboard\Filament\Resources\InnovationsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class ViewInnovations extends ViewRecord
{
    protected static string $resource = InnovationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || 
                    (Auth::user()->role === UserRole::SCHOOLADMIN && $this->record->school_id === Auth::user()->school_id)),
                    
            Actions\DeleteAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || 
                    (Auth::user()->role === UserRole::SCHOOLADMIN && $this->record->school_id === Auth::user()->school_id)),
        ];
    }
}