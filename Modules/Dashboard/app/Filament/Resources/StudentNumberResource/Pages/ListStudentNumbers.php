<?php

namespace Modules\Dashboard\Filament\Resources\StudentNumberResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\StudentNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
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

    /**
     * จับเหตุการณ์เมื่อมีการอัพเดตตัวกรอง
     */
    public function updatedTableFilters(): void
    {
        $this->updateWidgetStats();
    }

    /**
     * จับเหตุการณ์เมื่อมีการค้นหา
     */
    public function updatedTableSearch(): void
    {
        $this->updateWidgetStats();
    }

    /**
     * เมื่อรีเซ็ตตัวกรอง
     */
    public function resetTableFiltersForm(): void
    {
        parent::resetTableFiltersForm();
        $this->updateWidgetStats();
    }

    /**
     * ส่ง event เมื่อมีการลบ filter ผ่านปุ่ม X
     */
    public function removeTableFilter(string $filterName, ?string $field = null, bool $isRemovingAllFilters = false): void
    {
        parent::removeTableFilter($filterName, $field, $isRemovingAllFilters);
        $this->updateWidgetStats();
    }

    /**
     * รีเซ็ตการค้นหา
     */
    public function resetTableSearch(): void
    {
        parent::resetTableSearch();
        $this->updateWidgetStats();
    }

    /**
     * ส่งข้อมูลปัจจุบันไปยัง widget stats
     */
    private function updateWidgetStats(): void
    {
        // สร้าง array สำหรับเก็บข้อมูลที่จะส่งไปยัง widget
        $data = [
            'filters' => $this->getTableFiltersForm()->getRawState(),
            'search' => $this->getTableSearch(),
        ];

        // ส่ง event พร้อมข้อมูลไปยัง widget
        $this->dispatch('studentNumberFiltersUpdated', filters: $data);

        // Log ข้อมูลเพื่อตรวจสอบ
        // \Log::debug('Updated widget stats', $data);
    }
}
