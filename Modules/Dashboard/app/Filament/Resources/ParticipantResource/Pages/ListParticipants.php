<?php

namespace Modules\Dashboard\Filament\Resources\ParticipantResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\ParticipantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListParticipants extends ListRecords
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN)
                ->label('บันทึกข้อมูลผู้ร่วมมือใหม่'),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }
}
