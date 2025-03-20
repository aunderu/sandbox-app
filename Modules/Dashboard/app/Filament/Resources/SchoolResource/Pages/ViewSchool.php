<?php

namespace Modules\Dashboard\Filament\Resources\SchoolResource\Pages;

use Modules\Dashboard\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class ViewSchool extends ViewRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || 
                    (Auth::user()->role === UserRole::SCHOOLADMIN && Auth::user()->school_id === $this->record->school_id)),
                    
            Actions\DeleteAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN),
        ];
    }
}