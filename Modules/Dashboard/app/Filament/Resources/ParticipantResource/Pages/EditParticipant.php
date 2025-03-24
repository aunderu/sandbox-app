<?php

namespace Modules\Dashboard\Filament\Resources\ParticipantResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\ParticipantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditParticipant extends EditRecord
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('ลบรายการ')
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
            Actions\ForceDeleteAction::make()
                ->label('ลบถาวร')
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
            Actions\RestoreAction::make()
                ->label('กู้คืนรายการ')
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
