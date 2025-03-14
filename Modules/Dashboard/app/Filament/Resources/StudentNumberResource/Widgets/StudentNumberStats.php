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
    public ?array $filter = [];

    public ?string $search = null;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }

    // รับ event จาก ListStudentNumbers
    protected function getListeners(): array
    {
        return [
            'studentNumberFiltersUpdated' => 'updateFilters',
        ];
    }

    /**
     * เมธอดที่รับข้อมูล filter และ search จาก event
     */
    public function updateFilters(array $filters): void
    {
        // เก็บข้อมูล filter
        if (isset($filters['filters'])) {
            $this->filter = $filters['filters'];
        } else {
            $this->filter = $filters;
        }

        // เก็บคำค้นหา (ถ้ามี) หรือกำหนดให้เป็น null ถ้าไม่มี
        if (isset($filters['search'])) {
            $this->search = !empty($filters['search']) ? $filters['search'] : null;
        } else {
            $this->search = null;
        }

        $this->dispatch('refresh');
    }

    public function updatedTableSearch(): void
    {
        $this->updateWidgetStats();
    }

    /**
     * สร้าง query พื้นฐานที่ใช้ทั้ง filter และ search
     */
    protected function createBaseQuery(): Builder
    {
        $query = StudentNumberModel::query();

        // กรองตามสิทธิ์การเข้าถึง
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        // ใช้ Filter จาก TableFilters
        $this->applyFiltersToQuery($query);
        $this->applySearchToQuery($query);

        return $query;
    }

    /**
     * ใช้คำค้นหากับ query
     */
    protected function applySearchToQuery(Builder $query): void
    {
        $query->where(function ($q) {
            // ค้นหาตามชื่อโรงเรียน
            $q->whereHas('school', function ($schoolQuery) {
                $schoolQuery->where('school_name_th', 'like', "%{$this->search}%")
                    ->orWhere('school_name_en', 'like', "%{$this->search}%")
                    ->orWhere('school_id', 'like', "%{$this->search}%");
            });
            
            $q->orWhere('education_year', 'like', "%{$this->search}%");
        });
    }

    /**
     * ปรับปรุง getStats เพื่อใช้ query พื้นฐาน
     */
    protected function getStats(): array
    {
        $query = $this->createBaseQuery();

        $countQuery = clone $query;
        $stats = $countQuery->selectRaw('SUM(male_count) as total_male')
            ->selectRaw('SUM(female_count) as total_female')
            ->selectRaw('SUM(male_count + female_count) as total_students')
            ->first();

        // ป้องกันกรณี query ไม่มีผลลัพธ์
        $totalMale = $stats ? (int) $stats->total_male : 0;
        $totalFemale = $stats ? (int) $stats->total_female : 0;
        $totalStudents = $stats ? (int) $stats->total_students : 0;

        // สร้างคำอธิบาย Filter ที่ใช้
        $filterDescription = $this->getFilterDescription();
        $schoolCount = $this->getSchoolCount($query);

        // คำนวณข้อมูลกราฟตามฟิลเตอร์
        $chartData = $this->getChartDataByFilter($query);
        $trendData = $this->getYearTrend();

        return [
            Stat::make('จำนวนนักเรียนทั้งหมด', number_format($totalStudents))
                ->description($filterDescription)
                ->descriptionIcon('heroicon-m-funnel')
                ->chart($trendData)
                ->color('success'),

            Stat::make('จำนวนนักเรียนชาย', number_format($totalMale))
                ->description("คิดเป็น " . ($totalStudents > 0 ? round(($totalMale / $totalStudents) * 100, 1) : 0) . "% ของทั้งหมด")
                ->descriptionIcon('heroicon-m-user')
                ->chart($chartData['male'] ?? [])
                ->color('info'),

            Stat::make('จำนวนนักเรียนหญิง', number_format($totalFemale))
                ->description("คิดเป็น " . ($totalStudents > 0 ? round(($totalFemale / $totalStudents) * 100, 1) : 0) . "% ของทั้งหมด")
                ->descriptionIcon('heroicon-m-user')
                ->chart($chartData['female'] ?? [])
                ->color('warning'),

            Stat::make('จำนวนโรงเรียน', number_format($schoolCount))
                ->description('โรงเรียนที่มีข้อมูล')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('danger'),
        ];
    }

    protected function applyFiltersToQuery(Builder $query): void
    {
        if (empty($this->filter)) {
            return;
        }

        // Filter โรงเรียน
        if (isset($this->filter['school_id']) && isset($this->filter['school_id']['value']) && !empty($this->filter['school_id']['value'])) {
            $schoolId = $this->filter['school_id']['value'];
            if (is_array($schoolId)) {
                $query->whereIn('school_id', $schoolId);
            } else {
                $query->where('school_id', $schoolId);
            }
        }

        // Filter ปีการศึกษา
        if (isset($this->filter['education_year']) && isset($this->filter['education_year']['value']) && !empty($this->filter['education_year']['value'])) {
            $educationYear = $this->filter['education_year']['value'];
            if (is_array($educationYear)) {
                $query->whereIn('education_year', $educationYear);
            } else {
                $query->where('education_year', $educationYear);
            }
        }

        // Filter ระดับชั้น - สำหรับแบบ multiple select
        if (isset($this->filter['grade_id']) && isset($this->filter['grade_id']['values']) && !empty($this->filter['grade_id']['values'])) {
            $gradeIds = $this->filter['grade_id']['values'];
            $query->whereIn('grade_id', $gradeIds);
        }
        // รองรับแบบเก่า (single value) เพื่อความเข้ากันได้
        else if (isset($this->filter['grade_id']) && isset($this->filter['grade_id']['value']) && !empty($this->filter['grade_id']['value'])) {
            $query->where('grade_id', $this->filter['grade_id']['value']);
        }

        // Filter เขตพื้นที่การศึกษา (ถ้ามี)
        if (isset($this->filter['area_id']) && isset($this->filter['area_id']['value']) && !empty($this->filter['area_id']['value'])) {
            $areaId = $this->filter['area_id']['value'];
            if (is_array($areaId)) {
                $query->whereHas('school', function ($q) use ($areaId) {
                    $q->whereIn('area_id', $areaId);
                });
            } else {
                $query->whereHas('school', function ($q) use ($areaId) {
                    $q->where('area_id', $areaId);
                });
            }
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                // ค้นหาตามชื่อโรงเรียน
                $q->whereHas('school', function ($schoolQuery) {
                    $schoolQuery->where('school_name_th', 'like', "%{$this->search}%")
                        ->orWhere('school_name_en', 'like', "%{$this->search}%")
                        ->orWhere('school_id', 'like', "%{$this->search}%");
                });

                $q->orWhere('education_year', 'like', "%{$this->search}%");
            });
        }
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

        if (!empty($this->search)) {
            $descriptions[] = "ค้นหา: {$this->search}";
        }

        // คำอธิบายตาม Filter
        if (!empty($this->filter)) {
            // โรงเรียน
            if (isset($this->filter['school_id']) && isset($this->filter['school_id']['value']) && !empty($this->filter['school_id']['value'])) {
                $schoolId = $this->filter['school_id']['value'];

                if (is_array($schoolId)) {
                    $schools = SchoolModel::whereIn('id', $schoolId)->get();

                    if ($schools->isNotEmpty()) {
                        $schoolNames = [];
                        foreach ($schools as $school) {
                            $schoolNames[] = "โรงเรียน{$school->school_name_th}";
                        }
                        $descriptions[] = implode(', ', $schoolNames);
                    }
                } else {
                    $school = SchoolModel::find($schoolId);
                    if ($school && !in_array("โรงเรียน{$school->school_name_th}", $descriptions)) {
                        $descriptions[] = "โรงเรียน{$school->school_name_th}";
                    }
                }
            }

            // ปีการศึกษา
            if (isset($this->filter['education_year']) && isset($this->filter['education_year']['value']) && !empty($this->filter['education_year']['value'])) {
                $educationYear = $this->filter['education_year']['value'];

                if (is_array($educationYear)) {
                    $descriptions[] = "ปีการศึกษา " . implode(', ', $educationYear);
                } else {
                    $descriptions[] = "ปีการศึกษา {$educationYear}";
                }
            }

            // ระดับชั้น
            if (isset($this->filter['grade_id']) && isset($this->filter['grade_id']['value']) && !empty($this->filter['grade_id']['value'])) {
                $gradeId = $this->filter['grade_id']['value'];

                if (is_array($gradeId)) {
                    // กรณีเป็น array (มีหลายระดับชั้นที่ถูกเลือก)
                    $grades = GradeLevelsModel::whereIn('id', $gradeId)->get();

                    if ($grades->isNotEmpty()) {
                        $gradeNames = [];
                        foreach ($grades as $grade) {
                            $gradeNames[] = "ระดับชั้น{$grade->grade_name}";
                        }
                        $descriptions[] = implode(', ', $gradeNames);
                    }
                } else {
                    // กรณีเป็นค่าเดียว
                    $grade = GradeLevelsModel::find($gradeId);
                    if ($grade) {
                        $descriptions[] = "ระดับชั้น{$grade->grade_name}";
                    }
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
        $schoolQuery = clone $query;
        return $schoolQuery->distinct('school_id')->count('school_id');
    }

    /**
     * สร้างข้อมูลกราฟตามฟิลเตอร์ที่เลือก
     */
    protected function getChartDataByFilter(Builder $query): array
    {
        // เตรียมข้อมูลสำหรับกราฟ
        $maleData = [];
        $femaleData = [];

        $chartQuery = clone $query;

        // ตรวจสอบการกรองระดับชั้น
        $hasGradeFilter = isset($this->filter['grade_id']) &&
            isset($this->filter['grade_id']['value']) &&
            !empty($this->filter['grade_id']['value']);

        // ตรวจสอบการกรองโรงเรียน
        $hasSchoolFilter = isset($this->filter['school_id']) &&
            isset($this->filter['school_id']['value']) &&
            !empty($this->filter['school_id']['value']);

        // ตรวจสอบการกรองปีการศึกษา
        $hasYearFilter = isset($this->filter['education_year']) &&
            isset($this->filter['education_year']['value']) &&
            !empty($this->filter['education_year']['value']);

        // กรณีมีการกรองระดับชั้น - แสดงข้อมูลเทรนด์ตามปีการศึกษา
        if ($hasGradeFilter) {
            $results = $chartQuery->select('education_year')
                ->selectRaw('SUM(male_count) as total_male')
                ->selectRaw('SUM(female_count) as total_female')
                ->groupBy('education_year')
                ->orderBy('education_year', 'asc')
                ->limit(5)
                ->get();

            foreach ($results as $item) {
                $maleData[] = (int) $item->total_male;
                $femaleData[] = (int) $item->total_female;
            }
        }
        // กรณีมีการกรองโรงเรียนหรือปีการศึกษา - แสดงข้อมูลแยกตามระดับชั้น
        else if ($hasSchoolFilter || $hasYearFilter) {
            $results = $chartQuery->select('grade_id')
                ->selectRaw('SUM(male_count) as total_male')
                ->selectRaw('SUM(female_count) as total_female')
                ->groupBy('grade_id')
                ->orderBy('grade_id', 'asc')
                ->get();

            foreach ($results as $item) {
                $maleData[] = (int) $item->total_male;
                $femaleData[] = (int) $item->total_female;
            }
        }
        // ไม่มีการกรอง - แสดงข้อมูลตามปีการศึกษาล่าสุด 5 ปี
        else {
            $results = $chartQuery->select('education_year')
                ->selectRaw('SUM(male_count) as total_male')
                ->selectRaw('SUM(female_count) as total_female')
                ->groupBy('education_year')
                ->orderBy('education_year', 'desc')
                ->limit(5)
                ->get()
                ->sortBy('education_year');

            foreach ($results as $item) {
                $maleData[] = (int) $item->total_male;
                $femaleData[] = (int) $item->total_female;
            }
        }

        return [
            'male' => $maleData,
            'female' => $femaleData
        ];
    }

    /**
     * สร้างข้อมูลเทรนด์ตามปีการศึกษา
     */
    protected function getYearTrend(): array
    {
        $query = StudentNumberModel::query();

        // กรองตามสิทธิ์การเข้าถึง
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        // ใช้ Filter เฉพาะส่วนที่เกี่ยวข้องกับการดู trend
        if (isset($this->filter['school_id']) && isset($this->filter['school_id']['value']) && !empty($this->filter['school_id']['value'])) {
            $schoolId = $this->filter['school_id']['value'];
            if (is_array($schoolId)) {
                $query->whereIn('school_id', $schoolId);
            } else {
                $query->where('school_id', $schoolId);
            }
        }

        if (isset($this->filter['grade_id']) && isset($this->filter['grade_id']['value']) && !empty($this->filter['grade_id']['value'])) {
            $gradeId = $this->filter['grade_id']['value'];
            if (is_array($gradeId)) {
                $query->whereIn('grade_id', $gradeId);
            } else {
                $query->where('grade_id', $gradeId);
            }
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                // ค้นหาตามชื่อโรงเรียน
                $q->whereHas('school', function ($schoolQuery) {
                    $schoolQuery->where('school_name_th', 'like', "%{$this->search}%")
                        ->orWhere('school_name_en', 'like', "%{$this->search}%")
                        ->orWhere('school_id', 'like', "%{$this->search}%");
                });

                // เพิ่มเงื่อนไขการค้นหาอื่นๆ ตามต้องการ
                $q->orWhere('education_year', 'like', "%{$this->search}%");
            });
        }

        // ดึงข้อมูล 5 ปีล่าสุด
        $results = $query->select('education_year')
            ->selectRaw('SUM(male_count + female_count) as total_students')
            ->groupBy('education_year')
            ->orderBy('education_year', 'desc')
            ->limit(5)
            ->get()
            ->sortBy('education_year');

        $trendData = [];
        foreach ($results as $item) {
            $trendData[] = (int) $item->total_students;
        }

        return $trendData;
    }
}
