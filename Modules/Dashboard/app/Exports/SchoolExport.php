<?php

namespace Modules\Dashboard\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Sandbox\Models\SchoolModel;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Log;

class SchoolExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    protected $filters = [];
    protected $rowsExported = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = SchoolModel::query();

        // กรองตามบทบาทผู้ใช้
        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        // กรองตามเงื่อนไขที่ส่งมา
        if (isset($this->filters['school_id']) && !empty($this->filters['school_id'])) {
            $query->where('school_id', 'like', '%' . $this->filters['school_id'] . '%');
        }

        if (isset($this->filters['school_name']) && !empty($this->filters['school_name'])) {
            $query->where('school_name_th', 'like', '%' . $this->filters['school_name'] . '%');
        }

        if (isset($this->filters['province']) && !empty($this->filters['province'])) {
            $query->where('province', $this->filters['province']);
        }

        if (isset($this->filters['district']) && !empty($this->filters['district'])) {
            $query->where('district', $this->filters['district']);
        }

        if (isset($this->filters['ministry']) && !empty($this->filters['ministry'])) {
            $query->where('ministry', $this->filters['ministry']);
        }

        if (isset($this->filters['school_sizes']) && !empty($this->filters['school_sizes'])) {
            $query->where('school_sizes', $this->filters['school_sizes']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'รหัสสถานศึกษา',
            'ชื่อสถานศึกษา (ไทย)',
            'ชื่อสถานศึกษา (อังกฤษ)',
            'สังกัดกระทรวง',
            'สังกัดสำนักงาน/กรม',
            'เขตพื้นที่การศึกษา',
            'ประเภทสถานศึกษา',
            'วันเดือนปีที่ก่อตั้ง',
            'ผู้อำนวยการ',
            'รองผู้อำนวยการ',
            'บ้านเลขที่',
            'หมู่ที่',
            'ถนน',
            'ตำบล',
            'อำเภอ',
            'จังหวัด',
            'รหัสไปรษณีย์',
            'โทรศัพท์',
            'โทรสาร',
            'อีเมล',
            'เว็บไซต์',
            'ละติจูด',
            'ลองจิจูด',
            'จำนวนนักเรียน',
            'จำนวนนักเรียนด้อยโอกาส',
            'จำนวนครู',
            'หลักสูตร'
        ];
    }

    public function map($row): array
    {
        $this->rowsExported++;

        $principalName = trim(
            ($row->principal_prefix_code ?? '') .
            ($row->principal_name_thai ?? '') .
            ((!empty($row->principal_middle_name_thai)) ? ' ' . $row->principal_middle_name_thai : '') .
            ' ' . ($row->principal_lastname_thai ?? '')
        );

        $deputyName = trim(
            ($row->deputy_principal_prefix_code ?? '') .
            ($row->deputy_principal_name_thai ?? '') .
            ((!empty($row->deputy_principal_middle_name_thai)) ? ' ' . $row->deputy_principal_middle_name_thai : '') .
            ' ' . ($row->deputy_principal_lastname_thai ?? '')
        );

        // แปลงค่า school_course_type จาก JSON เป็น string
        $courseTypes = '';
        if (!empty($row->school_course_type)) {
            if (is_array($row->school_course_type)) {
                $courseTypes = implode(', ', $row->school_course_type);
            } elseif (is_string($row->school_course_type)) {
                if (strpos($row->school_course_type, '[') === 0) {
                    try {
                        $decoded = json_decode($row->school_course_type, true);
                        if (is_array($decoded)) {
                            $courseTypes = implode(', ', $decoded);
                        } else {
                            $courseTypes = $row->school_course_type;
                        }
                    } catch (\Exception $e) {
                        $courseTypes = $row->school_course_type;
                    }
                } else {
                    $courseTypes = $row->school_course_type;
                }
            }
        }

        return [
            $row->school_id ?? '',
            $row->school_name_th ?? '',
            $row->school_name_en ?? '',
            $row->ministry ?? '',
            $row->department ?? '',
            $row->area ?? '',
            $row->school_sizes ?? '',
            $row->founding_date ?? '',
            $principalName ?: '-',
            $deputyName ?: '-',
            $row->house_id ?? '',
            $row->village_no ?? '',
            $row->road ?? '',
            $row->sub_district ?? '',
            $row->district ?? '',
            $row->province ?? '',
            $row->postal_code ?? '',
            $row->phone ?? '',
            $row->fax ?? '',
            $row->email ?? '',
            $row->website ?? '',
            $row->latitude ?? '',
            $row->longitude ?? '',
            $row->student_amount ?? 0,
            $row->disadvantaged_student_amount ?? 0,
            $row->teacher_amount ?? 0,
            $courseTypes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16
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
        return 'ข้อมูลสถานศึกษา';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // เพิ่มแถวด้านบน 3 แถวเพื่อใส่หัวข้อ
                $sheet->insertNewRowBefore(1, 2);

                // กำหนดหัวข้อในแถวแรก
                $sheet->setCellValue('A1', 'ข้อมูลสถานศึกษา');
                $sheet->setCellValue('A2', 'สำนักงานศึกษาธิการจังหวัดยะลา ปีการศึกษา 2568');

                // การรวมเซลล์สำหรับหัวข้อ
                $lastColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");

                // กำหนดรูปแบบตัวอักษรสำหรับหัวข้อ
                $sheet->getStyle("A1:{$lastColumn}2")->getFont()
                    ->setName('TH SarabunPSK')
                    ->setSize(20)
                    ->setBold(true);

                // จัดกึ่งกลางสำหรับหัวข้อ
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
                $sheet->getRowDimension(4)->setRowHeight(40);

                // ตั้งค่าขนาดตัวอักษรเป็น 16 สำหรับทุกเซลล์ในตาราง
                $lastRow = $sheet->getHighestRow();

                // กำหนดฟอนต์ TH SarabunPSK ขนาด 16 สำหรับทุกเซลล์ในตาราง
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getFont()
                    ->setName('TH SarabunPSK')
                    ->setSize(16);

                // กำหนดความสูงของแถวข้อมูลให้พอดีกับขนาดตัวอักษร
                for ($i = 4; $i <= $lastRow; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(20); // ปรับความสูงของแถวข้อมูล
                }

                // ปรับความกว้างของทุกคอลัมน์ให้มากขึ้น
                foreach (range('A', $lastColumn) as $column) {
                    // ตั้งค่าความกว้างเป็นค่าตายตัว เพื่อรองรับตัวอักษรขนาดใหญ่
                    $sheet->getColumnDimension($column)->setWidth(25);
                }

                // กำหนดสไตล์สำหรับทั้งตาราง
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];

                // ใส่เส้นขอบสำหรับทุกเซลล์ในตาราง
                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->applyFromArray($styleArray);

                // ตั้งค่า wrap text ให้กับทุกเซลล์ เพื่อให้ข้อความที่ยาวแสดงหลายบรรทัดได้
                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);

                // ปรับการจัดวางข้อความในแถวข้อมูล (ไม่ใช่หัวตาราง) เป็นชิดซ้ายและอยู่กึ่งกลางแนวตั้ง
                $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // ล็อกจำนวนข้อมูลที่ export
                Log::info('Exported ' . $this->rowsExported . ' school records to Excel.');
            },
        ];
    }
}