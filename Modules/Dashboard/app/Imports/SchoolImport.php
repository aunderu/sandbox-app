<?php

namespace Modules\Dashboard\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;
use Modules\Sandbox\Models\SchoolModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SchoolImport implements
    ToModel,
    SkipsOnError,
    WithStartRow,
    SkipsOnFailure,
    WithBatchInserts,
    WithChunkReading,
    WithCalculatedFormulas,
    WithValidation,
    WithUpserts
{
    protected $rows = 0;
    protected $failures = [];
    protected $errors = [];
    protected $startRow = 3; // เริ่มอ่านข้อมูลจากแถวที่ 3 (เพราะแถวที่ 1-2 เป็นหัวกลุ่มและหัวตาราง)
    protected $removeSchoolPrefix = true; // เปิดใช้งานการตัดคำว่า "โรงเรียน"

    public function uniqueBy()
    {
        return 'school_id';
    }
    public function startRow(): int
    {
        return $this->startRow;
    }

    // เพิ่มกฎการตรวจสอบข้อมูล
    public function rules(): array
    {
        return [
            '0' => 'required', // รหัสโรงเรียน
            '1' => 'required', // ชื่อโรงเรียนภาษาไทย
        ];
    }

    // ข้อความแสดงเมื่อเกิดข้อผิดพลาด
    public function customValidationMessages()
    {
        return [
            '0.required' => 'กรุณาระบุรหัสโรงเรียน',
            '1.required' => 'กรุณาระบุชื่อโรงเรียนภาษาไทย',
        ];
    }

    /**
     * ฟังก์ชันตัดคำว่า "โรงเรียน" และคำนำหน้าสถานศึกษาภาษาไทยออกจากชื่อ
     *
     * @param string $name ชื่อสถานศึกษาภาษาไทย
     * @return string ชื่อสถานศึกษาที่ตัดคำนำหน้าออกแล้ว
     */
    protected function removeThaiSchoolPrefix($name)
    {
        if (empty($name) || !$this->removeSchoolPrefix) {
            return $name;
        }

        // รายการคำนำหน้าสถานศึกษาภาษาไทยที่ต้องการตัดออก
        $prefixes = [
            'โรงเรียน',
            // 'วิทยาลัย',
            // 'สถาบัน',
            // 'มหาวิทยาลัย',
            // 'โรงเรียนสาขา',
            // 'ศูนย์การศึกษา',
            // 'ศูนย์พัฒนาเด็กเล็ก',
        ];

        $name = trim($name); 

        foreach ($prefixes as $prefix) {
            // ตรวจสอบว่าชื่อขึ้นต้นด้วยคำนำหน้าหรือไม่
            if (mb_stripos($name, $prefix, 0, 'UTF-8') === 0) {
                $name = trim(mb_substr($name, mb_strlen($prefix, 'UTF-8')));
                break;
            }
        }

        return $name;
    }

    protected function prepareSchoolData(array $row, string $schoolNameTh)
    {
        $foundingDate = $this->parseFoundingDate($row);
        $courseTypeArray = $this->parseCourseType($row);

        $schoolData = [
            'school_id' => trim($row['0'] ?? ''),
            'school_name_th' => $schoolNameTh,
            'school_name_en' => trim($row['2'] ?? ''),
            'ministry' => trim($row['3'] ?? ''),
            'department' => trim($row['4'] ?? ''),
            'area' => trim($row['5'] ?? ''),
            'school_sizes' => trim($row['6'] ?? ''),
            'founding_date' => $foundingDate,
            'school_course_type' => $courseTypeArray,
            'course_attachment' => trim($row['9'] ?? ''),
            'original_filename' => trim($row['10'] ?? ''),
            'principal_prefix_code' => trim($row['11'] ?? ''),
            'principal_name_thai' => trim($row['12'] ?? ''),
            'principal_middle_name_thai' => trim($row['13'] ?? ''),
            'principal_lastname_thai' => trim($row['14'] ?? ''),
            'deputy_principal_prefix_code' => trim($row['15'] ?? ''),
            'deputy_principal_name_thai' => trim($row['16'] ?? ''),
            'deputy_principal_middle_name_thai' => trim($row['17'] ?? ''),
            'deputy_principal_lastname_thai' => trim($row['18'] ?? ''),
            'house_id' => trim($row['19'] ?? ''),
            'village_no' => trim($row['20'] ?? ''),
            'road' => trim($row['21'] ?? ''),
            'sub_district' => trim($row['22'] ?? ''),
            'district' => trim($row['23'] ?? ''),
            'province' => trim($row['24'] ?? ''),
            'postal_code' => trim($row['25'] ?? ''),
            'phone' => trim($row['26'] ?? ''),
            'fax' => trim($row['27'] ?? ''),
            'email' => trim($row['28'] ?? ''),
            'website' => trim($row['29'] ?? ''),
            'latitude' => $row['30'] ?? null,
            'longitude' => $row['31'] ?? null,
            'student_amount' => intval($row['32'] ?? 0),
            'disadvantaged_student_amount' => intval($row['33'] ?? 0),
            'teacher_amount' => intval($row['34'] ?? 0),
        ];

        return $schoolData;
    }

    protected function parseFoundingDate($row)
    {
        $foundingDate = null;

        if (!empty($row['7'])) {
            try {
                // ถ้าเป็นตัวเลข (Excel serial date) ให้แปลงเป็นวันที่
                if (is_numeric($row['7'])) {
                    $foundingDate = Date::excelToDateTimeObject($row['7'])->format('Y-m-d');
                } else {
                    // พยายามแปลงวันที่จากรูปแบบต่างๆ
                    $dateValue = trim($row['7']);

                    // ตรวจสอบรูปแบบ DD-MM-YYYY
                    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateValue, $matches)) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $year = $matches[3];
                        $foundingDate = "{$year}-{$month}-{$day}";
                    }
                    // ตรวจสอบรูปแบบ YYYY-MM-DD
                    elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateValue, $matches)) {
                        $year = $matches[1];
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                        $foundingDate = "{$year}-{$month}-{$day}";
                    }
                    // ตรวจสอบรูปแบบ DD/MM/YYYY
                    elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateValue, $matches)) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $year = $matches[3];
                        $foundingDate = "{$year}-{$month}-{$day}";
                    } else {
                        // ใช้ Carbon พยายามแปลงวันที่
                        $foundingDate = Carbon::parse($dateValue)->format('Y-m-d');
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ไม่สามารถแปลงวันที่ได้: ' . $row['7'], ['exception' => $e->getMessage()]);
                $foundingDate = null;
            }
        }

        return $foundingDate;
    }

    protected function parseCourseType($row)
    {
        $courseType = !empty($row['8']) ? $row['8'] : null;
        $courseTypeArray = null;

        if ($courseType) {
            if (is_string($courseType)) {
                if (strpos($courseType, ',') !== false) {
                    $courseTypeArray = array_map('trim', explode(',', $courseType));
                } else {
                    $courseTypeArray = [trim($courseType)];
                }
            }
            elseif (is_array($courseType)) {
                $courseTypeArray = $courseType;
            }
        }

        return $courseTypeArray;
    }

    public function model(array $row)
    {
        Log::debug('Processing row', [
            'row_num' => $this->startRow + $this->rows,
            'school_id' => $row['0'] ?? '',
            'school_name' => $row['1'] ?? '',
            'school_name_en' => $row['2'] ?? '',
            'ministry' => $row['3'] ?? '',
            'department' => $row['4'] ?? '',
            'area' => $row['5'] ?? '',
            'school_sizes' => $row['6'] ?? '',
            'founding_date' => $row['7'] ?? '',
            'school_course_type' => $row['8'] ?? '',
            'course_attachment' => $row['9'] ?? '',
            'original_filename' => $row['10'] ?? '',
            'principal_prefix_code' => $row['11'] ?? '',
            'principal_name_thai' => $row['12'] ?? '',
            'principal_middle_name_thai' => $row['13'] ?? '',
            'principal_lastname_thai' => $row['14'] ?? '',
            'deputy_principal_prefix_code' => $row['15'] ?? '',
            'deputy_principal_name_thai' => $row['16'] ?? '',
            'deputy_principal_middle_name_thai' => $row['17'] ?? '',
            'deputy_principal_lastname_thai' => $row['18'] ?? '',
            'house_id' => $row['19'] ?? '',
            'village_no' => $row['20'] ?? '',
            'road' => $row['21'] ?? '',
            'sub_district' => $row['22'] ?? '',
            'district' => $row['23'] ?? '',
            'province' => $row['24'] ?? '',
            'postal_code' => $row['25'] ?? '',
            'phone' => $row['26'] ?? '',
            'fax' => $row['27'] ?? '',
            'email' => $row['28'] ?? '',
            'website' => $row['29'] ?? '',
            'latitude' => $row['30'] ?? null,
            'longitude' => $row['31'] ?? null,
            'student_amount' => intval($row['32'] ?? 0),
            'disadvantaged_student_amount' => intval($row['33'] ?? 0),
            'teacher_amount' => intval($row['34'] ?? 0),

        ]);

        if (empty($row['0']) || empty($row['1'])) {
            return null;
        }

        try {
            $this->rows++;

            // ตัดคำว่า "โรงเรียน" ออกจากชื่อภาษาไทย
            $schoolNameTh = $this->removeThaiSchoolPrefix(trim($row['1'] ?? ''));

            // สร้างข้อมูลโรงเรียน
            $schoolData = $this->prepareSchoolData($row, $schoolNameTh);

            return new SchoolModel($schoolData);
        } catch (\Exception $e) {
            $this->errors[] = "แถวที่ " . ($this->startRow + $this->rows) . ": " . $e->getMessage();
            Log::error('Error processing school row: ' . $e->getMessage(), [
                'row_num' => $this->startRow + $this->rows,
                'school_id' => $row['0'] ?? 'empty',
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    // เมธอดสำหรับการจัดการข้อผิดพลาด
    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
        Log::error('School import error: ' . $e->getMessage());

        return null; // ข้ามแถวที่มีปัญหา
    }

    // เมธอดสำหรับการจัดการความล้มเหลวในการตรวจสอบความถูกต้อง
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];

            Log::warning('School import validation failure', [
                'row' => $failure->row(),
                'errors' => $failure->errors(),
            ]);
        }
    }

    // เมธอดสำหรับการดึงจำนวนแถวที่นำเข้า
    public function getRowCount(): int
    {
        return $this->rows;
    }

    // เมธอดสำหรับการดึง failures
    public function getFailures(): array
    {
        return $this->failures;
    }

    // เมธอดสำหรับการดึง errors
    public function getErrors(): array
    {
        return $this->errors;
    }

    // กำหนดขนาด batch สำหรับการนำเข้า
    public function batchSize(): int
    {
        return 100;
    }

    // กำหนดขนาด chunk สำหรับการอ่านไฟล์
    public function chunkSize(): int
    {
        return 100;
    }

    // ตั้งค่า startRow (เมธอดสำหรับตั้งค่าจากภายนอก)
    public function setStartRow(int $startRow): self
    {
        $this->startRow = $startRow;
        return $this;
    }

    /**
     * เปิด/ปิด การตัดคำว่า "โรงเรียน" จากชื่อ
     */
    public function setRemoveSchoolPrefix(bool $remove): self
    {
        $this->removeSchoolPrefix = $remove;
        return $this;
    }
}