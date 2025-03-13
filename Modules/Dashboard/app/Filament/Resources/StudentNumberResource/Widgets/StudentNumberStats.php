<?php

namespace Modules\Dashboard\Filament\Resources\StudentNumberResource\Widgets;

use App\Enums\UserRole;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Sandbox\Models\GradeLevelsModel;
use Modules\Sandbox\Models\SchoolModel;
use Modules\Sandbox\Models\StudentNumberModel;

class StudentNumberStats extends BaseWidget
{
    // ให้ Widget reload ทุกครั้งที่มีการใช้ Filter
    protected static bool $isLazy = false;
    
    // เพิ่ม TableFilters จาก Resource
    public ?array $filter = [];
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return true;
    }
    
    protected function getStats(): array
    {
        // สร้าง Query สำหรับคำนวณสถิติ
        $query = StudentNumberModel::query();
        
        // กรองตามสิทธิ์การเข้าถึง
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        } 
        
        // ใช้ Filter จาก TableFilters
        $this->applyFiltersToQuery($query);

        // คำนวณสถิติจาก Query
        $totalMale = $query->sum('male_count');
        $totalFemale = $query->sum('female_count');
        $totalStudents = $totalMale + $totalFemale;
        
        // สร้างคำอธิบาย Filter ที่ใช้
        $filterDescription = $this->getFilterDescription();
        $schoolCount = $this->getSchoolCount($query);
        
        return [
            Stat::make('จำนวนนักเรียนทั้งหมด', number_format($totalStudents))
                ->description($filterDescription)
                ->descriptionIcon('heroicon-m-funnel')
                ->color('success'),
                
            Stat::make('จำนวนนักเรียนชาย', number_format($totalMale))
                ->description("คิดเป็น " . ($totalStudents > 0 ? round(($totalMale / $totalStudents) * 100, 1) : 0) . "% ของทั้งหมด")
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),
                
            Stat::make('จำนวนนักเรียนหญิง', number_format($totalFemale))
                ->description("คิดเป็น " . ($totalStudents > 0 ? round(($totalFemale / $totalStudents) * 100, 1) : 0) . "% ของทั้งหมด")
                ->descriptionIcon('heroicon-m-user')
                ->color('warning'),
                
            Stat::make('จำนวนโรงเรียน', number_format($schoolCount))
                ->description('โรงเรียนที่มีข้อมูล')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('danger'),
        ];
    }
    
    /**
     * นำ Filter มาใช้กับ Query
     */
    protected function applyFiltersToQuery(Builder $query): void
    {
        // ดึง filter จาก Filament TableFilters
        if (empty($this->filter)) {
            return;
        }

        // Filter โรงเรียน
        if (isset($this->filter['school_id']) && $this->filter['school_id']) {
            $query->where('school_id', $this->filter['school_id']);
        }

        // Filter ปีการศึกษา
        if (isset($this->filter['education_year']) && $this->filter['education_year']) {
            $query->where('education_year', $this->filter['education_year']);
        }

        // Filter ระดับชั้น
        if (isset($this->filter['grade_id']) && $this->filter['grade_id']) {
            $query->where('grade_id', $this->filter['grade_id']);
        }
        
        // ถ้ามี Filter เพิ่มเติมสามารถเพิ่มได้ตามต้องการ
        // ...
    }

    /**
     * สร้างคำอธิบาย Filter ที่ใช้
     */
    protected function getFilterDescription(): string
    {
        $descriptions = [];
        
        // คำอธิบายตามสิทธิ์ผู้ใช้
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $school = SchoolModel::find(Auth::user()->school_id);
            if ($school) {
                $descriptions[] = "โรงเรียน{$school->school_name_th}";
            }
        }
        
        // คำอธิบายตาม Filter
        if (!empty($this->filter)) {
            // โรงเรียน
            if (isset($this->filter['school_id']) && $this->filter['school_id']) {
                $school = SchoolModel::find($this->filter['school_id']);
                if ($school && !in_array("โรงเรียน{$school->school_name_th}", $descriptions)) {
                    $descriptions[] = "โรงเรียน{$school->school_name_th}";
                }
            }
            
            // ปีการศึกษา
            if (isset($this->filter['education_year']) && $this->filter['education_year']) {
                $descriptions[] = "ปีการศึกษา {$this->filter['education_year']}";
            }
            
            // ระดับชั้น
            if (isset($this->filter['grade_id']) && $this->filter['grade_id']) {
                $grade = GradeLevelsModel::find($this->filter['grade_id']);
                if ($grade) {
                    $descriptions[] = "ระดับชั้น{$grade->grade_name}";
                }
            }
        }
        
        return !empty($descriptions) ? implode(' | ', $descriptions) : 'ข้อมูลทั้งหมด';
    }

    /**
     * นับจำนวนโรงเรียนที่มีข้อมูล
     */
    protected function getSchoolCount(Builder $query): int
    {
        // Clone query เพื่อไม่ให้กระทบกับ query หลัก
        $schoolQuery = clone $query;
        return $schoolQuery->distinct('school_id')->count('school_id');
    }
}
