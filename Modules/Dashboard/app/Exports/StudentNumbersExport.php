<?php

namespace Modules\Dashboard\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Sandbox\Models\GradeLevelsModel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Sandbox\Models\StudentNumberModel;
use Modules\Sandbox\Models\SchoolModel;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentNumbersExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    protected $filters = [];
    protected $rowsExported = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // ดึงข้อมูลโรงเรียน
        $schoolsQuery = SchoolModel::query();

        // กรองตามบทบาทผู้ใช้
        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
            $schoolsQuery->where('school_id', Auth::user()->school_id);
        }

        // กรองตามเงื่อนไขที่ส่งมาถ้ามี
        if (isset($this->filters['school_id']) && !empty($this->filters['school_id'])) {
            $schoolsQuery->where('school_id', $this->filters['school_id']);
        }

        $schools = $schoolsQuery->get();

        // ดึงข้อมูลระดับชั้นทั้งหมดที่มีการใช้งาน
        $grades = GradeLevelsModel::orderBy('id')->get();

        // Log ข้อมูลเกี่ยวกับ grade เพื่อ debug
        Log::debug('Grades data: ' . json_encode($grades->toArray()));

        // ดึงข้อมูลจำนวนนักเรียนโดยตรงจาก DB เพื่อป้องกันปัญหา relationship
        $studentNumbersRaw = DB::table('student_number')
            ->select('*')
            ->when(isset($this->filters['grade_id']) && !empty($this->filters['grade_id']), function ($query) {
                return $query->where('grade_id', $this->filters['grade_id']);
            })
            ->when(isset($this->filters['education_year']) && !empty($this->filters['education_year']), function ($query) {
                return $query->where('education_year', $this->filters['education_year']);
            })
            ->get();

        // แสดงข้อมูลเพื่อ debug
        Log::debug('Schools count: ' . $schools->count());
        Log::debug('Student numbers raw count: ' . $studentNumbersRaw->count());
        if ($studentNumbersRaw->count() > 0) {
            Log::debug('First raw student number: ' . json_encode($studentNumbersRaw->first()));
        }

        // จัดรูปแบบข้อมูลใหม่เป็นแนวตั้ง
        $result = new Collection();

        foreach ($schools as $school) {
            $schoolId = $school->id; // บันทึก school->id เพื่อใช้เชื่อมโยงกับ student_numbers

            $row = [
                'school_id' => $school->school_id,
                'school_name' => $school->school_name_th,
            ];

            // Log school info เพื่อ debug
            Log::debug('Processing school: ' . json_encode([
                'id' => $school->id,
                'school_id' => $school->school_id,
                'name' => $school->school_name_th
            ]));

            // สร้างข้อมูลจำนวนนักเรียนตามระดับชั้น
            foreach ($grades as $grade) {
                // ค้นหาข้อมูลจำนวนนักเรียน
                $studentNumber = null;

                // ตรวจสอบการเชื่อมโยงที่ถูกต้อง
                foreach ($studentNumbersRaw as $record) {
                    // ถ้าเชื่อมโยงด้วยชื่อระดับชั้นแทน ID
                    if (
                        ($record->school_id == $schoolId || $record->school_id == $school->school_id) &&
                        $record->grade_id == $grade->id
                    ) {
                        $studentNumber = $record;
                        break;
                    }
                }

                // Log เพื่อ debug
                Log::debug("Search student number for school_id: {$schoolId} or {$school->school_id}, grade_id: {$grade->id}, grade_name: {$grade->grade_name} - " .
                    ($studentNumber ? "FOUND: male={$studentNumber->male_count}, female={$studentNumber->female_count}" : "NOT FOUND"));

                // เพิ่มข้อมูลลงในแถว
                $row['male_' . $grade->id] = $studentNumber ? $studentNumber->male_count : 0;
                $row['female_' . $grade->id] = $studentNumber ? $studentNumber->female_count : 0;
            }

            $result->push((object) $row);
            $this->rowsExported++;
        }

        // ตรวจสอบข้อมูลใน result ก่อนส่งออก
        Log::debug('Result collection count: ' . $result->count());
        if ($result->count() > 0) {
            Log::debug('First row in result: ' . json_encode($result->first()));
        }

        return $result;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'name' => 'TH SarabunPSK'
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF']
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'ข้อมูลจำนวนนักเรียน';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // เพิ่มแถวด้านบน 2 แถวเพื่อใส่หัวข้อ
                $sheet->insertNewRowBefore(1, 4);

                // กำหนดหัวข้อในแถวแรก
                $sheet->setCellValue('A1', 'ข้อมูลจำนวนนักเรียน');
                $sheet->setCellValue('A2', 'สำนักงานศึกษาธิการจังหวัดยะลา ปีการศึกษา ' . ($this->filters['education_year'] ?? date('Y') + 543));

                // ดึงข้อมูลระดับชั้นทั้งหมด
                $grades = GradeLevelsModel::orderBy('id')->get();

                // เริ่มต้นคอลัมน์ที่ C (เพราะ A คือรหัสโรงเรียน, B คือชื่อโรงเรียน)
                $currentColumn = 2;

                // สร้างหัวตารางแถวที่ 1 (รหัสโรงเรียนและชื่อโรงเรียน)
                $sheet->setCellValueByColumnAndRow(1, 3, 'รหัสสถานศึกษา'); // A3
                $sheet->setCellValueByColumnAndRow(2, 3, 'ชื่อโรงเรียน'); // B3
    
                // รวมเซลล์แนวตั้งสำหรับรหัสและชื่อโรงเรียน
                $sheet->mergeCells('A3:A4');
                $sheet->mergeCells('B3:B4');

                // สร้างหัวตารางสำหรับแต่ละระดับชั้น (ชาย, หญิง) แบบ 2 แถว
                foreach ($grades as $grade) {
                    // ระดับชั้น (ครอบคลุม 2 คอลัมน์: ชาย และ หญิง)
                    $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColumn + 1);
                    $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColumn + 2);
                    $sheet->setCellValueByColumnAndRow($currentColumn + 1, 3, $grade->grade_name);
                    $sheet->mergeCells("{$startCol}3:{$endCol}3");

                    // ชาย และ หญิง
                    $sheet->setCellValueByColumnAndRow($currentColumn + 1, 4, 'ชาย');
                    $sheet->setCellValueByColumnAndRow($currentColumn + 2, 4, 'หญิง');

                    $currentColumn += 2; // เลื่อนไป 2 คอลัมน์ถัดไป
                }

                // การรวมเซลล์สำหรับหัวข้อด้านบนสุด
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColumn);
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");

                // กำหนดรูปแบบตัวอักษรสำหรับหัวข้อ
                $sheet->getStyle("A1:{$lastColumn}2")->getFont()
                    ->setName('TH SarabunPSK')
                    ->setSize(20)
                    ->setBold(true);

                // จัดซ้ายสำหรับหัวข้อ
                $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                    ->setIndent(2);

                // กำหนดความสูงของแถวหัวข้อ
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(25);

                // ตั้งค่าหัวข้อแบบพิเศษ
                $sheet->getStyle('A1')->getFont()->setSize(24);

                // กำหนดความสูงของแถวหัวตาราง
                $sheet->getRowDimension(3)->setRowHeight(30);
                $sheet->getRowDimension(4)->setRowHeight(30);

                // จัดกึ่งกลางสำหรับหัวตาราง
                $sheet->getStyle("A3:{$lastColumn}4")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // ตั้งพื้นหลังสีสำหรับหัวตาราง
                $sheet->getStyle("A3:{$lastColumn}4")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E9ECEF');

                // ทำให้หัวตารางเป็นตัวหนา
                $sheet->getStyle("A3:{$lastColumn}4")->getFont()
                    ->setBold(true);

                // ตั้งค่าขนาดตัวอักษรเป็น 16 สำหรับทุกเซลล์ในตาราง
                $lastRow = $sheet->getHighestRow();

                // กำหนดฟอนต์ TH SarabunPSK ขนาด 16 สำหรับทุกเซลล์ในตาราง
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getFont()
                    ->setName('TH SarabunPSK')
                    ->setSize(16);

                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getFont()
                    ->setBold(false);

                // กำหนดความสูงของแถวข้อมูลให้พอดีกับขนาดตัวอักษร
                for ($i = 5; $i <= $lastRow; $i++) {  // เริ่มจากแถวที่ 5 (ข้อมูล)
                    $sheet->getRowDimension($i)->setRowHeight(25); // ปรับความสูงของแถวข้อมูล
                }

                // แสดงเส้นขอบสำหรับทั้งตาราง
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];

                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->applyFromArray($styleArray);

                // จัดแนวข้อมูลในแถวข้อมูลให้ชิดซ้าย
                $sheet->getStyle("A5:B{$lastRow}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                // จัดแนวข้อมูลตัวเลขในแถวข้อมูลให้อยู่กึ่งกลาง
                $sheet->getStyle("C5:{$lastColumn}{$lastRow}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // ล็อกจำนวนข้อมูลที่ export
                // Log::info('Exported ' . $this->rowsExported . ' school records to Excel.');
            },
        ];
    }
}