<?php

namespace Modules\Dashboard\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Modules\Sandbox\Models\SchoolModel;

class StudentNumberTemplateExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithEvents
{
    protected string $title = 'แบบฟอร์มนำเข้าข้อมูลจำนวนนักเรียน';
    protected string $version = '1.0.0';
    protected string $headerBackgroundColor = '1A5DAB';
    protected string $exampleBackgroundColor = 'EBF3FD';
    protected string $instructionBackgroundColor = 'F8F9FA';
    protected string $borderColor = 'D0D0D0';
    
    protected ?string $schoolName = null;
    protected ?int $schoolId = null;

    /**
     * สร้าง instance ใหม่ของ StudentNumberTemplateExport
     * สามารถระบุ schoolId เพื่อบังคับใช้โรงเรียนเฉพาะได้
     */
    public function __construct(?int $schoolId = null)
    {
        // ถ้ามีการส่ง school ID มาให้ใช้ ID นั้น
        if ($schoolId !== null) {
            $this->schoolId = $schoolId;
            $school = SchoolModel::find($schoolId);
            if ($school) {
                $this->schoolName = $school->school_name_th;
            }
        } 
        else if (Auth::check()) {
            $user = Auth::user();
            // ถ้าเป็น SchoolAdmin หรือมี school_id ใน user
            if ($user->role === UserRole::SCHOOLADMIN && !empty($user->school_id)) {
                $this->schoolId = $user->school_id;
                $school = SchoolModel::find($user->school_id);
                if ($school) {
                    $this->schoolName = $school->school_name_th;
                }
            }
        }
        
        // ถ้าไม่มีชื่อโรงเรียนให้ใช้ค่าเริ่มต้น
        if ($this->schoolName === null) {
            $this->schoolName = 'โรงเรียนตัวอย่าง';
        }
    }

    public function array(): array
    {
        $currentThaiYear = (int) date('Y') + 543;
        
        return [
            [$currentThaiYear, $this->schoolName, 'อนุบาล 1', 0, 0],
            [$currentThaiYear, $this->schoolName, 'อนุบาล 2', 0, 0],
            [$currentThaiYear, $this->schoolName, 'อนุบาล 3', 0, 0],
            [$currentThaiYear, $this->schoolName, 'ประถมศึกษาปีที่ 1', 0, 0],
            [$currentThaiYear, $this->schoolName, 'ประถมศึกษาปีที่ 2', 0, 0],
            [$currentThaiYear, $this->schoolName, 'ประถมศึกษาปีที่ 3', 0, 0],
            [$currentThaiYear, $this->schoolName, 'ประถมศึกษาปีที่ 4', 0, 0],
            [$currentThaiYear, $this->schoolName, 'ประถมศึกษาปีที่ 5', 0, 0],
            [$currentThaiYear, $this->schoolName, 'ประถมศึกษาปีที่ 6', 0, 0],
            [$currentThaiYear, $this->schoolName, 'มัธยมศึกษาปีที่ 1', 0, 0],
            [$currentThaiYear, $this->schoolName, 'มัธยมศึกษาปีที่ 2', 0, 0],
            [$currentThaiYear, $this->schoolName, 'มัธยมศึกษาปีที่ 3', 0, 0],
            [$currentThaiYear, $this->schoolName, 'มัธยมศึกษาปีที่ 4', 0, 0],
            [$currentThaiYear, $this->schoolName, 'มัธยมศึกษาปีที่ 5', 0, 0],
            [$currentThaiYear, $this->schoolName, 'มัธยมศึกษาปีที่ 6', 0, 0],
        ];
    }

    public function headings(): array
    {
        return [
            'ปีการศึกษา (จำเป็น)',
            'โรงเรียน (จำเป็น)',
            'ระดับชั้น (จำเป็น)',
            'จำนวนนักเรียนชาย',
            'จำนวนนักเรียนหญิง'
        ];
    }

    public function title(): string
    {
        return 'ข้อมูลจำนวนนักเรียน';
    }

    public function styles(Worksheet $sheet)
    {
        // สร้าง style สำหรับหัวตาราง - โทนสีน้ำเงินเข้มที่ดูเป็นทางการ
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 13,
                'name' => 'TH SarabunPSK',
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $this->headerBackgroundColor]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => $this->borderColor],
                ]
            ]
        ];

        // กำหนดความสูงแถว
        $sheet->getRowDimension(1)->setRowHeight(30);
        // $sheet->getRowDimension(1)->setRowHeight(25);

        // กำหนดสไตล์ฟอนต์ทั้งเวิร์กชีท
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('TH SarabunPSK')->setSize(14);

        return [
            1 => $headerStyle,

            // จัดรูปแบบข้อมูลตัวอย่าง
            'A2:E' . (1 + count($this->array())) => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $this->exampleBackgroundColor]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $this->borderColor]
                    ]
                ]
            ],

            // จัดรูปแบบคอลัมน์ตัวเลข
            'D2:E' . (1 + count($this->array())) => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ],
                'numberFormat' => [
                    'formatCode' => NumberFormat::FORMAT_NUMBER
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'E'; // คอลัมน์สุดท้าย
                $lastRow = 2 + count($this->array()); // แถวข้อมูลสุดท้าย
    
                // เพิ่มหัวเรื่องที่ด้านบนของเอกสาร
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', "{$this->title} v{$this->version}");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F4C81');
                $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getRowDimension(1)->setRowHeight(25);

                // เพิ่มคำอธิบายการใช้งาน
                $instructions = [
                    '1. กรุณากรอกข้อมูลจำนวนนักเรียนในแต่ละระดับชั้น โดยระบุปีการศึกษา (พ.ศ.) โรงเรียน และระดับชั้นให้ครบถ้วน',
                    '2. กรุณาอย่าแก้ไขหรือลบแถวหัวตาราง (แถวที่ 1 และแถวที่ 2)',
                    '3. หากไม่มีนักเรียนในระดับชั้นใด ให้ระบุจำนวนเป็น 0 หรือเว้นว่างไว้',
                    '4. รูปแบบการใส่ระดับชั้น เช่น "อนุบาล 1", "ประถมศึกษาปีที่ 1", "มัธยมศึกษาปีที่ 1" หรือ "อ.1", "ป.1", "ม.1" เป็นต้น',
                    '5. หลังจากกรอกข้อมูลเสร็จสิ้น กรุณาตรวจสอบความถูกต้องก่อนบันทึกไฟล์',
                    '6. หากมีข้อสงสัยหรือข้อผิดพลาด กรุณาติดต่อผู้ดูแลระบบ'
                ];

                // สร้างส่วนหัวของคำแนะนำ
                $row = $lastRow + 2;
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                $sheet->setCellValue("A{$row}", 'คำแนะนำการกรอกข้อมูล:');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
                $row++;

                // เพิ่มคำแนะนำแต่ละข้อ
                foreach ($instructions as $instruction) {
                    $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                    $sheet->setCellValue("A{$row}", $instruction);
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $this->instructionBackgroundColor],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'DDDDDD'],
                            ],
                        ],
                    ]);
                    $row++;
                }

                // เพิ่มหมายเหตุเกี่ยวกับฟิลด์ที่จำเป็น
                $noteRow = $row + 1;
                $sheet->mergeCells("A{$noteRow}:{$lastColumn}{$noteRow}");
                $sheet->setCellValue("A{$noteRow}", 'หมายเหตุ: ฟิลด์ที่มีคำว่า "(จำเป็น)" เป็นข้อมูลที่ต้องกรอก');
                $sheet->getStyle("A{$noteRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$noteRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($this->instructionBackgroundColor);

                // ล็อคแถวหัวตาราง
                $sheet->freezePane('A2');

                // ปรับความกว้างคอลัมน์
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(20);

                $sheet->calculateColumnWidths();

                function applyDataValidation($sheet, $cell, $type, $min, $max, $errorTitle, $errorMessage, $promptTitle, $promptMessage)
                {
                    $objValidation = $sheet->getCell($cell)->getDataValidation() ?? new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
                    $objValidation->setType($type);
                    $objValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setErrorTitle($errorTitle);
                    $objValidation->setError($errorMessage);
                    $objValidation->setPromptTitle($promptTitle);
                    $objValidation->setPrompt($promptMessage);
                    $objValidation->setFormula1($min);
                    $objValidation->setFormula2($max);
                    $objValidation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_BETWEEN);

                    $sheet->getCell($cell)->setDataValidation($objValidation);
                }

                for ($i = 3; $i <= $lastRow; $i++) {
                    applyDataValidation(
                        $sheet,
                        "A{$i}",
                        \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_WHOLE,
                        2500,
                        2600,
                        'ข้อผิดพลาด',
                        'กรุณาใส่ปีการศึกษาเป็นตัวเลข 4 หลัก (พ.ศ.)',
                        'ปีการศึกษา',
                        'ใส่ปีการศึกษาเป็นตัวเลข 4 หลัก เช่น 2566, 2567'
                    );

                    applyDataValidation(
                        $sheet,
                        "B{$i}",
                        \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_TEXTLENGTH,
                        2,
                        150,
                        'ข้อผิดพลาด',
                        'กรุณาระบุชื่อโรงเรียน ความยาวระหว่าง 2-150 ตัวอักษร',
                        'ชื่อโรงเรียน',
                        'ระบุชื่อโรงเรียนให้ถูกต้อง เช่น "โรงเรียนสวนกุหลาบวิทยาลัย"'
                    );

                    applyDataValidation(
                        $sheet,
                        "C{$i}",
                        \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_TEXTLENGTH,
                        2,
                        50,
                        'ข้อผิดพลาด',
                        'กรุณาระบุระดับชั้น ความยาวระหว่าง 2-50 ตัวอักษร',
                        'ระดับชั้น',
                        'ระบุระดับชั้น เช่น "อนุบาล 1", "ประถมศึกษาปีที่ 1", "ม.1"'
                    );

                    applyDataValidation(
                        $sheet,
                        "D{$i}",
                        \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_WHOLE,
                        0,
                        20000,
                        'ข้อผิดพลาด',
                        'กรุณาใส่จำนวนนักเรียนเป็นตัวเลขจำนวนเต็มที่ไม่ติดลบ',
                        'จำนวนนักเรียน',
                        'ใส่จำนวนนักเรียนเป็นตัวเลขจำนวนเต็ม'
                    );

                    applyDataValidation(
                        $sheet,
                        "E{$i}",
                        \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_WHOLE,
                        0,
                        20000,
                        'ข้อผิดพลาด',
                        'กรุณาใส่จำนวนนักเรียนเป็นตัวเลขจำนวนเต็มที่ไม่ติดลบ',
                        'จำนวนนักเรียน',
                        'ใส่จำนวนนักเรียนเป็นตัวเลขจำนวนเต็ม'
                    );

                }

            }
        ];
    }
}