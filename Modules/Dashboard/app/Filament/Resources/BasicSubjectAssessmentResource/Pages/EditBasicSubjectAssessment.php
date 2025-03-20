<?php

namespace Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Pages;

use Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBasicSubjectAssessment extends EditRecord
{
    protected static string $resource = BasicSubjectAssessmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ตรวจสอบว่ามีการเปลี่ยนแปลงโรงเรียนหรือปีการศึกษาหรือไม่
        if ($this->record->school_id != $data['school_id'] || $this->record->education_year != $data['education_year']) {
            // ตรวจสอบว่ามีข้อมูลซ้ำหรือไม่
            $duplicateCheck = BasicSubjectAssessmentResource::hasDuplicate(
                $data['school_id'], 
                $data['education_year'], 
                $this->record->id
            );
            
            if ($duplicateCheck) {
                Notification::make()
                    ->danger()
                    ->title('เกิดข้อผิดพลาด')
                    ->body("ไม่สามารถบันทึกการเปลี่ยนแปลงได้ เนื่องจากมีข้อมูลการประเมินของโรงเรียนและปีการศึกษานี้อยู่แล้ว")
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
            
            // สร้าง ID ใหม่
            $data['id'] = BasicSubjectAssessmentResource::generateSubjectId($data['school_id'], $data['education_year']);
        }
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
