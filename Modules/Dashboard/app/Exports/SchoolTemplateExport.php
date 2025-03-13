<?php

namespace Modules\Dashboard\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class SchoolTemplateExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithColumnFormatting,
    WithTitle,
    WithEvents
{
    public function array(): array
    {
        // ตัวอย่างข้อมูล
        return [
            [
                '123456789', // รหัสโรงเรียน
                'โรงเรียนตัวอย่าง', // ชื่อภาษาไทย
                'Example School', // ชื่อภาษาอังกฤษ
                'กระทรวงศึกษาธิการ', // กระทรวง
                'สพฐ.', // หน่วยงานต้นสังกัด
                'ยะลา เขต 1', // พื้นที่
                'ขนาดกลาง', // ขนาดโรงเรียน
                '01-01-2568', // วันที่ก่อตั้ง (รูปแบบ DD-MM-YYYY)
                'หลักสูตรแกนกลางการศึกษาขั้นพื้นฐาน', // ประเภทหลักสูตร
                '', // เอกสารแนบหลักสูตร
                '', // ชื่อไฟล์เดิม
                'นาย', // คำนำหน้าผู้อำนวยการ
                'ทดสอบ', // ชื่อ
                '', // ชื่อกลาง
                'ระบบ', // นามสกุล
                'นาง', // คำนำหน้ารองผู้อำนวยการ
                'ทดลอง', // ชื่อ
                '', // ชื่อกลาง
                'ข้อมูล', // นามสกุล
                '123/45', // บ้านเลขที่
                '5', // หมู่ที่
                'ถนนตัวอย่าง', // ถนน
                'ท่าสาป', // ตำบล/แขวง
                'เมืองยะลา', // อำเภอ/เขต
                'ยะลา', // จังหวัด
                '95000', // รหัสไปรษณีย์
                '073-123456', // โทรศัพท์
                '073-123457', // โทรสาร
                'example@school.ac.th', // อีเมล
                'www.exampleschool.ac.th', // เว็บไซต์
                '6.551100', // ละติจูด
                '101.283500', // ลองจิจูด
                '500', // จำนวนนักเรียน
                '50', // จำนวนนักเรียนด้อยโอกาส
                '35' // จำนวนครู
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'รหัสโรงเรียน',
            'ชื่อโรงเรียนภาษาไทย',
            'ชื่อโรงเรียนภาษาอังกฤษ',
            'สังกัดกระทรวง',
            'สังกัดสำนักงาน/กรม',
            'เขตพื้นที่การศึกษา',
            'ขนาดโรงเรียน',
            'วันที่ก่อตั้ง (DD-MM-YYYY)',
            'ประเภทหลักสูตร',
            'เอกสารแนบหลักสูตร',
            'ชื่อไฟล์เดิม',
            'คำนำหน้าผู้อำนวยการ',
            'ชื่อผู้อำนวยการ',
            'ชื่อกลางผู้อำนวยการ',
            'นามสกุลผู้อำนวยการ',
            'คำนำหน้ารองผู้อำนวยการ',
            'ชื่อรองผู้อำนวยการ',
            'ชื่อกลางรองผู้อำนวยการ',
            'นามสกุลรองผู้อำนวยการ',
            'บ้านเลขที่',
            'หมู่ที่',
            'ถนน',
            'ตำบล/แขวง',
            'อำเภอ/เขต',
            'จังหวัด',
            'รหัสไปรษณีย์',
            'โทรศัพท์',
            'โทรสาร',
            'อีเมล',
            'เว็บไซต์',
            'ละติจูด',
            'ลองจิจูด',
            'จำนวนนักเรียนทั้งหมด',
            'จำนวนนักเรียนด้อยโอกาส',
            'จำนวนครู'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // รหัสโรงเรียน
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY, // วันที่ก่อตั้ง
            'Z' => NumberFormat::FORMAT_TEXT, // รหัสไปรษณีย์
            'AE' => NumberFormat::FORMAT_NUMBER_00, // ละติจูด
            'AF' => NumberFormat::FORMAT_NUMBER_00, // ลองจิจูด 
            'AG' => NumberFormat::FORMAT_NUMBER, // จำนวนนักเรียน
            'AH' => NumberFormat::FORMAT_NUMBER, // จำนวนนักเรียนด้อยโอกาส
            'AI' => NumberFormat::FORMAT_NUMBER, // จำนวนครู
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // สร้าง style สำหรับหัวตาราง - โทนสีน้ำเงินเข้มที่ดูเป็นทางการ
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'TH SarabunPSK',
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F4C81'] // สีน้ำเงินเข้มแบบทางการ
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ]
            ]
        ];

        // สร้าง style สำหรับแถวข้อมูล - สีพื้นหลังสว่าง เน้นความเรียบง่าย
        $dataStyle = [
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'], // สีเส้นขอบอ่อนลง
                ]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FAFAFA'] // สีเทาอ่อนมาก แทบจะขาว
            ]
        ];

        // กำหนดความสูงแถว
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(25);

        // กำหนดสไตล์ฟอนต์ทั้งเวิร์กชีท
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('TH SarabunPSK')->setSize(14);

        return [
            1 => $headerStyle,
            2 => $dataStyle,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColumn = 'AI';
                $totalColumns = 35; // จำนวนคอลัมน์ทั้งหมด

                // เพิ่มแถวสำหรับหัวข้อกลุ่มข้อมูล 
                $sheet->insertNewRowBefore(1, 1);

                // สร้างหัวข้อกลุ่มและรวมเซลล์ - ใช้กลุ่มที่เป็นทางการมากขึ้น
                $groupHeaders = [
                    'ข้อมูลทั่วไปของสถานศึกษา' => 'A1:K1',
                    'ข้อมูลผู้บริหารสถานศึกษา' => 'L1:S1',
                    'ข้อมูลที่ตั้ง' => 'T1:Z1',
                    'ข้อมูลการติดต่อ' => 'AA1:AD1',
                    'ข้อมูลพิกัดทางภูมิศาสตร์' => 'AE1:AF1',
                    'ข้อมูลจำนวนบุคลากรและนักเรียน' => 'AG1:AI1',
                ];

                // กำหนดโทนสีที่เป็นทางการมากขึ้น - ใช้โทนสีน้ำเงินเข้มที่ลดหลั่นกันเล็กน้อย
                $groupColors = [
                    '0F4C81', // น้ำเงินเข้ม
                    '1A5DAB', // น้ำเงินกลาง
                    '20699E', // น้ำเงินกลาง-อ่อน
                    '0F4C81', // น้ำเงินเข้ม
                    '1A5DAB', // น้ำเงินกลาง
                    '20699E', // น้ำเงินกลาง-อ่อน
                ];

                $i = 0;
                foreach ($groupHeaders as $title => $range) {
                    // รวมเซลล์และใส่หัวข้อ
                    $sheet->mergeCells($range);
                    $sheet->setCellValue(explode(':', $range)[0], $title);

                    // จัดรูปแบบหัวข้อกลุ่ม
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 14,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $groupColors[$i]],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    $i++;
                }

                // ปรับความสูงแถวหัวข้อกลุ่ม
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ปรับความสูงแถวหัวตารางและข้อมูล
                $sheet->getRowDimension(2)->setRowHeight(30);
                $sheet->getRowDimension(3)->setRowHeight(25);
                
                // เพิ่มคำอธิบายเพิ่มเติม
                $lastDataRow = 5; // แถวที่จะเริ่มใส่คำอธิบาย

                // สร้างแถวว่าง
                $sheet->insertNewRowBefore($lastDataRow, 1);

                // ใส่หัวข้อคำแนะนำ - ใช้สีน้ำเงินเข้มเพื่อความเป็นทางการ
                $sheet->setCellValue('A' . $lastDataRow, 'คำชี้แจงการกรอกข้อมูล');
                $sheet->mergeCells('A' . $lastDataRow . ':' . $lastColumn . $lastDataRow);
                
                // จัดรูปแบบหัวข้อคำแนะนำให้ดูเป็นทางการมากขึ้น
                $sheet->getStyle('A' . $lastDataRow . ':' . $lastColumn . $lastDataRow)->applyFromArray([
                    'font' => [
                        'bold' => true, 
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F4C81'], // สีน้ำเงินเข้มเดียวกับหัวตาราง
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // เพิ่มรายละเอียดคำแนะนำ - ใช้ภาษาที่เป็นทางการมากขึ้น
                $instructions = [
                    '1. วันที่ก่อตั้งกรุณาระบุในรูปแบบ วัน-เดือน-ปี (DD-MM-YYYY) เช่น 01-01-2568',
                    '2. กรณีมีหลักสูตรมากกว่าหนึ่งประเภท กรุณาคั่นด้วยเครื่องหมายจุลภาค (,) เช่น "หลักสูตรแกนกลางการศึกษาขั้นพื้นฐาน,หลักสูตรเน้นภาษาอังกฤษ"',
                    '3. กรุณาระบุรหัสไปรษณีย์เป็นตัวเลข 5 หลัก',
                    '4. กรุณาระบุพิกัดทางภูมิศาสตร์ (ละติจูด/ลองจิจูด) เป็นเลขทศนิยม',
                    '5. กรุณาระบุจำนวนนักเรียนและจำนวนครูเป็นตัวเลขเท่านั้น',
                    '6. กรุณาอย่าแก้ไขหรือลบแถวหัวตาราง (แถวที่ 1 และแถวที่ 2)',
                    '7. แถวที่ 3 คือข้อมูลตัวอย่าง ท่านสามารถแก้ไขหรือเพิ่มข้อมูลได้ตั้งแต่แถวที่ 3 เป็นต้นไป',
                    '8. หลังจากกรอกข้อมูลเสร็จสิ้น กรุณาตรวจสอบความถูกต้องก่อนบันทึกไฟล์'
                ];

                $row = $lastDataRow + 1;
                foreach ($instructions as $instruction) {
                    $sheet->setCellValue('A' . $row, $instruction);
                    $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
                    $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F8F8'], // สีพื้นหลังเทาอ่อนมากเพื่อความเป็นทางการ
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

                // เพิ่มสัญลักษณ์ที่จำเป็นต้องกรอก - ทำให้เป็นทางการมากขึ้นโดยใช้ "จำเป็น" แทน *
                $requiredFields = ['A2', 'B2', 'D2', 'G2', 'I2', 'L2', 'M2', 'O2', 'W2', 'X2', 'Y2', 'Z2'];
                foreach ($requiredFields as $cell) {
                    $currentValue = $sheet->getCell($cell)->getValue();
                    $sheet->setCellValue($cell, $currentValue . ' (จำเป็น)');
                    // ใช้น้ำเงินเข้มสำหรับคำว่า "จำเป็น" แทนสีแดง เพื่อความเป็นทางการ
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }
                
                // เพิ่มหมายเหตุเกี่ยวกับฟิลด์ที่จำเป็นต้องกรอก - ใช้คำที่เป็นทางการมากขึ้น
                $sheet->setCellValue('A' . ($row + 1), 'หมายเหตุ: ฟิลด์ที่มีคำว่า "(จำเป็น)" เป็นข้อมูลที่ต้องกรอก');
                $sheet->mergeCells('A' . ($row + 1) . ':' . $lastColumn . ($row + 1));
                $sheet->getStyle('A' . ($row + 1))->getFont()->setBold(true);
                
                // ล็อคแถวหัวตาราง - ช่วยให้ใช้งานสะดวกแต่ยังคงความเป็นทางการ
                $sheet->freezePane('A3');
                
                // ไม่ใช้ autofilter เพื่อความเป็นทางการมากขึ้น
                // $sheet->setAutoFilter('A2:' . $lastColumn . '2');
                
                // ปรับความกว้างคอลัมน์ให้เหมาะสม - ช่วยให้เอกสารเป็นระเบียบมากขึ้น
                $sheet->getColumnDimension('A')->setWidth(15); // รหัสโรงเรียน
                $sheet->getColumnDimension('B')->setWidth(25); // ชื่อโรงเรียนภาษาไทย
                $sheet->getColumnDimension('C')->setWidth(25); // ชื่อโรงเรียนภาษาอังกฤษ
                $sheet->getColumnDimension('D')->setWidth(15); // กระทรวง
                $sheet->getColumnDimension('E')->setWidth(15); // หน่วยงาน
                $sheet->getColumnDimension('I')->setWidth(30); // ประเภทหลักสูตร
                $sheet->getColumnDimension('L')->setWidth(20); // คำนำหน้าผู้อำนวยการ
                $sheet->getColumnDimension('M')->setWidth(20); // ชื่อ
                $sheet->getColumnDimension('O')->setWidth(20); // นามสกุล
                $sheet->getColumnDimension('Y')->setWidth(15); // จังหวัด
                
                // เพิ่มการตรวจสอบความถูกต้องของข้อมูล (Data validation)
                $dateValidation = $sheet->getCell('H3')->getDataValidation();
                $dateValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
                $dateValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $dateValidation->setAllowBlank(true);
                $dateValidation->setShowErrorMessage(true);
                $dateValidation->setErrorTitle('รูปแบบวันที่ไม่ถูกต้อง');
                $dateValidation->setError('กรุณาระบุวันที่ในรูปแบบ DD-MM-YYYY');
                $dateValidation->setPromptTitle('รูปแบบวันที่');
                $dateValidation->setPrompt('กรุณาระบุวันที่ในรูปแบบ DD-MM-YYYY เช่น 01-01-2568');
                $dateValidation->setShowInputMessage(true);

                // คอลัมน์สำหรับพิกัด
                for ($i = 3; $i <= 100; $i++) {
                    // คัดลอกการตรวจสอบไปยังเซลล์อื่นๆ ในคอลัมน์
                    $sheet->getCell('H' . $i)->setDataValidation(clone $dateValidation);
                }
                
                // เพิ่มชื่อและเลขที่เอกสารเพื่อความเป็นทางการ
                $sheet->setCellValue('A' . ($row + 3), 'แบบฟอร์มข้อมูลสถานศึกษา [เวอร์ชัน 1.0]');
                $sheet->mergeCells('A' . ($row + 3) . ':E' . ($row + 3));
                $sheet->setCellValue('F' . ($row + 3), date('d/m/Y')); // วันที่สร้างเอกสาร
                $sheet->mergeCells('F' . ($row + 3) . ':H' . ($row + 3));
                $sheet->getStyle('A' . ($row + 3) . ':H' . ($row + 3))->getFont()->setSize(10)->setBold(true);
                $sheet->getStyle('A' . ($row + 3) . ':H' . ($row + 3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function title(): string
    {
        return 'แบบฟอร์มข้อมูลสถานศึกษา';
    }
}