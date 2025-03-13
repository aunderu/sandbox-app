<?php

namespace Modules\Dashboard\Filament\Resources\StudentNumberResource\Pages;

use App\Enums\UserRole;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Filament\Resources\StudentNumberResource;
use Modules\Dashboard\Filament\Resources\StudentNumberResource\Widgets\StudentNumberStats;

class ListStudentNumbers extends ListRecords
{
    protected static string $resource = StudentNumberResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StudentNumberStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
        ];
    }

    // รีเฟรช widget เมื่อมีการกรอง - อัปเดตวิธีการส่ง event
    public function filterTable(): void
    {
        parent::filterTable();
        
        // ส่ง event ไปยัง widget เมื่อมีการกรอง
        $this->dispatch('table-filter');
    }
    
    // เพิ่ม hook นี้เพื่อให้แน่ใจว่าจะเรียกใช้ทุกครั้งที่มีการกรอง
    protected function afterFiltering(): void
    {
        // ส่ง event ไปยัง widget เมื่อมีการกรอง
        $this->dispatch('table-filter');
    }
    
    // เพิ่ม hook นี้เพื่อให้มั่นใจว่า Widget จะได้รับการอัปเดตเมื่อยกเลิกการกรอง
    protected function afterFilteringIsReset(): void
    {
        // ส่ง event ไปยัง widget เมื่อมีการยกเลิกการกรอง
        $this->dispatch('table-filter');
    }
}
