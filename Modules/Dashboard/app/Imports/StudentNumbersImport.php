<?php

namespace Modules\Dashboard\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
use Modules\Sandbox\Models\GradeLevelsModel;
use Throwable;
use Modules\Sandbox\Models\StudentNumberModel;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Modules\Sandbox\Models\SchoolModel;
use Illuminate\Support\Facades\Log;

class StudentNumbersImport implements
    ToModel,
    WithStartRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure,
    WithBatchInserts,
    WithChunkReading,
    SkipsEmptyRows
{
    protected $rows = 0;
    protected $failures = [];
    protected $errors = [];
    protected $startRow = 3; // เริ่มอ่านข้อมูลจากแถวที่ 3 (หลังหัวตารางในแบบฟอร์ม)

    /**
     * กำหนดแถวที่จะเริ่มอ่านข้อมูล
     */
    public function startRow(): int
    {
        return $this->startRow;
    }

    /**
     * กำหนดว่าแถวใดเป็นแถวว่างที่ควรข้าม
     */
    public function isEmptyRow(array $row): bool
    {
        // ข้ามแถวที่ไม่มีข้อมูลปีการศึกษา, โรงเรียน หรือระดับชั้น
        return empty($row[0]) || empty($row[1]) || empty($row[2]);
    }

    /**
     * แปลงข้อมูลจาก Excel เป็นโมเดล
     */
    public function model(array $row)
    {
        try {
            // บันทึก Log เพื่อตรวจสอบข้อมูลที่อ่านได้
            Log::debug('Processing student numbers row', [
                'row_data' => $row,
                'row_num' => $this->startRow + $this->rows
            ]);

            // อ่านข้อมูลจากแถวที่กำหนด (เนื่องจากไม่ใช้ WithHeadingRow จึงอ่านตามตำแหน่งคอลัมน์)
            $educationYear = !empty($row[0]) ? (int) $row[0] : null;
            $schoolName = !empty($row[1]) ? trim($row[1]) : null;
            $gradeName = !empty($row[2]) ? trim($row[2]) : null;
            $maleCount = !empty($row[3]) ? (int) $row[3] : 0;
            $femaleCount = !empty($row[4]) ? (int) $row[4] : 0;

            // ตรวจสอบข้อมูลเบื้องต้น
            if (empty($educationYear) || empty($schoolName) || empty($gradeName)) {
                Log::warning('Missing required data', [
                    'education_year' => $educationYear,
                    'school_name' => $schoolName,
                    'grade_name' => $gradeName
                ]);
                return null;
            }

            // เพิ่มเคาท์เตอร์แถว
            $this->rows++;

            // ค้นหา ID ของโรงเรียนและระดับชั้น
            $schoolId = $this->getSchoolId($schoolName);
            $yearId = $this->getYearId($gradeName);

            // ตรวจสอบว่ามีข้อมูลซ้ำหรือไม่
            $exists = StudentNumberModel::where('school_id', $schoolId)
                ->where('year_id', $yearId)
                ->where('education_year', $educationYear)
                ->exists();

            if ($exists) {
                // อัปเดตข้อมูลเดิม
                $record = StudentNumberModel::where('school_id', $schoolId)
                    ->where('year_id', $yearId)
                    ->where('education_year', $educationYear)
                    ->first();

                $record->male_count = $maleCount;
                $record->female_count = $femaleCount;
                $record->save();

                Log::info('Updated existing student number record', [
                    'id' => $record->id,
                    'school_id' => $schoolId,
                    'year_id' => $yearId,
                    'education_year' => $educationYear
                ]);

                return null;
            }

            // สร้างข้อมูลใหม่
            Log::info('Creating new student number record', [
                'school_id' => $schoolId,
                'year_id' => $yearId,
                'education_year' => $educationYear,
                'male_count' => $maleCount,
                'female_count' => $femaleCount
            ]);

            return new StudentNumberModel([
                'school_id' => $schoolId,
                'year_id' => $yearId,
                'education_year' => $educationYear,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
            ]);
        } catch (\Exception $e) {
            $this->errors[] = "แถวที่ " . ($this->startRow + $this->rows) . ": " . $e->getMessage();
            Log::error('Error processing student numbers row: ' . $e->getMessage(), [
                'row' => $row,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * ค้นหา ID ของโรงเรียนจากชื่อ
     */
    private function getSchoolId($schoolName)
    {
        // ถ้าผู้ใช้เป็น SchoolAdmin ให้ใช้ school_id ของผู้ใช้
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            Log::info('Using current user school ID', [
                'school_id' => Auth::user()->school_id
            ]);
            return Auth::user()->school_id;
        }

        // ตรวจสอบว่าชื่อโรงเรียนเป็นรหัสโรงเรียนหรือไม่
        if (is_numeric($schoolName)) {
            $school = SchoolModel::where('school_id', $schoolName)->first();
            if ($school) {
                Log::info('Found school by ID', [
                    'school name' => $schoolName,
                    'school_id' => $school->school_id
                ]);
                return $school->school_id;
            }
        }

        // ค้นหาโรงเรียนจากชื่อ (ตรงๆ ก่อน)
        $school = SchoolModel::where('school_name_th', $schoolName)->first();
        if ($school) {
            Log::info('Found school by name', [
                'school name' => $schoolName,
                'school_id' => $school->school_id
            ]);
            return $school->school_id;
        }

        // ค้นหาโรงเรียนจากชื่อ (โดยตัดคำว่า "โรงเรียน" ออก)
        $searchName = str_replace('โรงเรียน', '', $schoolName);
        $searchName = trim($searchName);

        $school = SchoolModel::where('school_name_th', $searchName)->first();
        if ($school) {
            Log::info('Found school by name (without prefix)', [
                'school name' => $schoolName,
                'school_id' => $school->school_id
            ]);
            return $school->school_id;
        }

        // ค้นหาโรงเรียนแบบคลุมเครือ (LIKE) โดยใช้ชื่อที่ตัด "โรงเรียน" ออกแล้ว
        $school = SchoolModel::where('school_name_th', 'like', '%' . $searchName . '%')->first();
        if ($school) {
            Log::info('Found school by name (like search)', [
                'school name' => $schoolName,
                'school_id' => $school->school_id
            ]);
            return $school->school_id;
        }

        // ค้นหาโรงเรียนแบบคลุมเครือ (LIKE) โดยใช้ชื่อเดิม
        $school = SchoolModel::where('school_name_th', 'like', '%' . $schoolName . '%')->first();
        if ($school) {
            Log::info('Found school by name (like search)', [
                'school name' => $schoolName,
                'school_id' => $school->school_id
            ]);
            return $school->school_id;
        }

        // ค้นหาโรงเรียนโดยเพิ่มคำว่า "โรงเรียน" ไปข้างหน้า (กรณีที่ template ไม่มีคำว่า "โรงเรียน")
        if (mb_strpos($schoolName, 'โรงเรียน', 0, 'UTF-8') !== 0) {
            $searchWithPrefix = 'โรงเรียน' . $schoolName;
            $school = SchoolModel::where('school_name_th', $searchWithPrefix)->first();
            if ($school) {
                Log::info('Found school by name (with prefix)', [
                    'school name' => $schoolName,
                    'school_id' => $school->school_id
                ]);
                return $school->school_id;
            }

            // คลุมเครืออีกครั้ง
            $school = SchoolModel::where('school_name_th', 'like', '%' . $searchWithPrefix . '%')->first();
            if ($school) {
                Log::info('Found school by name (like search with prefix)', [
                    'school name' => $schoolName,
                    'school_id' => $school->school_id
                ]);
                return $school->school_id;
            }
        }

        // บันทึก log เพื่อการตรวจสอบ
        Log::warning("ไม่พบข้อมูลโรงเรียน: {$schoolName}", [
            'original_name' => $schoolName,
            'search_without_prefix' => $searchName
        ]);

        throw new \Exception("ไม่พบข้อมูลโรงเรียน: {$schoolName}");
    }

    /**
     * ค้นหา ID ของระดับชั้นจากชื่อ
     */
    private function getYearId($gradeName)
    {
        // ทำความสะอาดชื่อระดับชั้น
        $gradeName = trim($gradeName);

        // ปรับรูปแบบชื่อระดับชั้นให้เป็นมาตรฐาน
        $normalizedGrade = $this->normalizeGradeName($gradeName);

        // ค้นหาระดับชั้นจากชื่อ
        $grade = GradeLevelsModel::where('grade_name', 'like', '%' . $normalizedGrade . '%')
            ->orWhere('grade_name', 'like', '%' . $gradeName . '%')
            ->first();

        if (!$grade) {
            throw new \Exception("ไม่พบข้อมูลระดับชั้น: {$gradeName}");
        }

        return $grade->id;
    }

    /**
     * ปรับรูปแบบชื่อระดับชั้นให้เป็นมาตรฐาน
     */
    private function normalizeGradeName($gradeName)
    {
        // แปลงชื่อย่อเป็นชื่อเต็ม
        $nameMap = [
            'ป.1' => 'ประถมศึกษาปีที่ 1',
            'ป.2' => 'ประถมศึกษาปีที่ 2',
            'ป.3' => 'ประถมศึกษาปีที่ 3',
            'ป.4' => 'ประถมศึกษาปีที่ 4',
            'ป.5' => 'ประถมศึกษาปีที่ 5',
            'ป.6' => 'ประถมศึกษาปีที่ 6',
            'ม.1' => 'มัธยมศึกษาปีที่ 1',
            'ม.2' => 'มัธยมศึกษาปีที่ 2',
            'ม.3' => 'มัธยมศึกษาปีที่ 3',
            'ม.4' => 'มัธยมศึกษาปีที่ 4',
            'ม.5' => 'มัธยมศึกษาปีที่ 5',
            'ม.6' => 'มัธยมศึกษาปีที่ 6',
            'อ.1' => 'อนุบาล 1',
            'อ.2' => 'อนุบาล 2',
            'อ.3' => 'อนุบาล 3',
        ];

        return $nameMap[$gradeName] ?? $gradeName;
    }

    /**
     * กำหนดกฎตรวจสอบข้อมูล
     */
    public function rules(): array
    {
        return [
            '0' => 'required|numeric', // ปีการศึกษา
            '1' => 'required|string',  // โรงเรียน
            '2' => 'required|string',  // ระดับชั้น
            '3' => 'nullable|numeric|min:0', // จำนวนนักเรียนชาย
            '4' => 'nullable|numeric|min:0', // จำนวนนักเรียนหญิง
        ];
    }

    /**
     * ข้อความแสดงเมื่อการตรวจสอบล้มเหลว
     */
    public function customValidationMessages()
    {
        return [
            '0.required' => 'กรุณาระบุปีการศึกษา',
            '0.numeric' => 'ปีการศึกษาต้องเป็นตัวเลขเท่านั้น',
            '1.required' => 'กรุณาระบุชื่อโรงเรียน',
            '2.required' => 'กรุณาระบุระดับชั้น',
            '3.numeric' => 'จำนวนนักเรียนชายต้องเป็นตัวเลขเท่านั้น',
            '3.min' => 'จำนวนนักเรียนชายต้องไม่ต่ำกว่า 0',
            '4.numeric' => 'จำนวนนักเรียนหญิงต้องเป็นตัวเลขเท่านั้น',
            '4.min' => 'จำนวนนักเรียนหญิงต้องไม่ต่ำกว่า 0',
        ];
    }

    /**
     * กำหนดขนาด batch
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * กำหนดขนาด chunk
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * จัดการข้อผิดพลาดที่เกิดขึ้น
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
        Log::error('Student numbers import error: ' . $e->getMessage(), [
            'exception' => $e->getTraceAsString()
        ]);
        return null;
    }

    /**
     * จัดการความล้มเหลวในการตรวจสอบความถูกต้อง
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];

            Log::warning('Student numbers import validation failure', [
                'row' => $failure->row(),
                'errors' => $failure->errors(),
                'values' => $failure->values()
            ]);
        }
    }

    /**
     * ดึงจำนวนแถวที่นำเข้า
     */
    public function getRowCount(): int
    {
        return $this->rows;
    }

    /**
     * ดึงรายการความล้มเหลว
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * ดึงรายการข้อผิดพลาด
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * ตั้งค่าแถวเริ่มต้น
     */
    public function setStartRow(int $startRow): self
    {
        $this->startRow = $startRow;
        return $this;
    }
}