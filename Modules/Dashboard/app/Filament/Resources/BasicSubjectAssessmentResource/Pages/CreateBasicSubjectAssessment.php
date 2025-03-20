<?php

namespace Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Pages;

use Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBasicSubjectAssessment extends CreateRecord
{
    protected static string $resource = BasicSubjectAssessmentResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.basic-subject-assessments.index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ตรวจสอบว่ามีข้อมูลซ้ำหรือไม่
        $duplicateCheck = BasicSubjectAssessmentResource::hasDuplicate($data['school_id'], $data['education_year']);
        
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
        $data['id'] = BasicSubjectAssessmentResource::generateSubjectId($data['school_id'], $data['education_year']);
        
        return $data;
    }
}
