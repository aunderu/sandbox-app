<?php

namespace Modules\Dashboard\Filament\Resources\ParticipantResource\Pages;

use Modules\Dashboard\Filament\Resources\ParticipantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateParticipant extends CreateRecord
{
    protected static string $resource = ParticipantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // เพิ่ม user_id ของผู้สร้างรายการ
        $data['user_id'] = Auth::id();

        // สร้างรหัสผู้เข้าร่วม
        $sectorType = $data['sector_type'] ?? '1';
        $areaCode = $data['area_code'] ?? '95';

        $data['participant_id'] = ParticipantResource::generateParticipantId($sectorType, $areaCode);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
