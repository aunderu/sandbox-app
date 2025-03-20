<?php

namespace Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Pages;

use Filament\Notifications\Notification;
use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoreCompetencyAssessment extends CreateRecord
{
    protected static string $resource = CoreCompetencyAssessmentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.core-competency-assessments.index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ตรวจสอบว่ามีข้อมูลซ้ำหรือไม่
        $duplicateCheck = CoreCompetencyAssessmentResource::hasDuplicate($data['school_id'], $data['education_year']);
        
        if ($duplicateCheck) {
            Notification::make()
                ->danger()
                ->title('เกิดข้อผิดพลาด')
                ->body("ไม่สามารถสร้างรายการได้ เนื่องจากมีข้อมูลการประเมินของโรงเรียนและปีการศึกษานี้อยู่แล้ว")
                ->persistent()
                ->send();
            
            $this->halt();
        }
        
        // สร้าง ID
        $data['id'] = CoreCompetencyAssessmentResource::generateCompetencyId($data['school_id'], $data['education_year']);
        
        return $data;
    }
}
