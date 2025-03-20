<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Enums\UserRole;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components as FilamentComponent;
use Filament\Infolists\Components as InfolistComponent;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades as IlluminateFacade;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Dashboard\Filament\Resources\SchoolResource\Pages;
use Modules\Dashboard\Filament\Resources\SchoolResource\RelationManagers\SchoolInnovationsRelationManager;
use Modules\Sandbox\Models\SchoolModel;

use Phattarachai\FilamentThaiDatePicker\ThaiDatePicker;

class SchoolResource extends Resource
{
    protected static ?string $model = SchoolModel::class;
    protected static ?string $navigationIcon = 'heroicon-s-building-office-2';

    protected static ?string $modelLabel = 'ตารางโรงเรียน';

    protected static ?string $navigationGroup = "โรงเรียน";

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'จำนวนโรงเรียน';
    }

    public static function form(Form $form): Form
    {
        // โหลดข้อมูลจังหวัดและอำเภอจากไฟล์ JSON
        $cacheKey = 'provinces_and_districts:v1';
        $path = base_path('Modules/Dashboard/app/Filament/Resources/jsons/thailand_provinces_districts.json');

        $provincesAndDistricts = IlluminateFacade\Cache::remember($cacheKey, 86400, function () use ($path) {
            if (!file_exists($path)) {
                throw new \Exception("File not found: $path");
            }

            $jsonData = IlluminateFacade\File::get($path);
            $data = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON format in $path: " . json_last_error_msg());
            }

            return $data;
        });

        // ตรวจสอบว่าข้อมูลถูกโหลดมาหรือไม่
        if (is_null($provincesAndDistricts)) {
            throw new \Exception('Failed to load provinces and districts data.');
        }

        // สร้าง array ของจังหวัด อำเภอ และตำบล
        $provinces = array_column($provincesAndDistricts, 'name_th', 'name_th');

        $districts = [];
        $subDistricts = [];
        $postalCodes = [];

        foreach ($provincesAndDistricts as $province) {
            $districts[$province['name_th']] = array_column($province['amphure'], 'name_th', 'name_th');

            foreach ($province['amphure'] as $amphure) {
                $subDistricts[$province['name_th']][$amphure['name_th']] = array_column($amphure['tambon'], 'name_th', 'name_th');
                $postalCodes[$province['name_th']][$amphure['name_th']] = array_column($amphure['tambon'], 'zip_code', 'name_th');
            }
        }

        return $form->schema([

            FilamentComponent\Wizard::make([

                // STEP 1: ข้อมูลพื้นฐาน
                FilamentComponent\Wizard\Step::make('ข้อมูลพื้นฐาน')
                    ->description('กรอกข้อมูลพื้นฐานของสถานศึกษา')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        FilamentComponent\TextInput::make('school_id')
                            ->label(__('รหัสสถานศึกษา'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        FilamentComponent\Grid::make(2)
                            ->schema([
                                FilamentComponent\TextInput::make('school_name_th')
                                    ->label(__('ชื่อสถานศึกษา (ไทย)'))
                                    ->maxLength(255)
                                    ->required(),

                                FilamentComponent\TextInput::make('school_name_en')
                                    ->label(__('ชื่อสถานศึกษา (อังกฤษ)'))
                                    ->maxLength(255)
                                    ->nullable(),
                            ]),

                        FilamentComponent\Grid::make(3)
                            ->schema([
                                FilamentComponent\Select::make('ministry')
                                    ->label(__('สังกัดกระทรวง'))
                                    ->options([
                                        'กระทรวงศึกษาธิการ' => 'กระทรวงศึกษาธิการ',
                                        'กระทรวงมหาดไทย' => 'กระทรวงมหาดไทย',
                                        'สำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน' => 'สำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน',
                                    ])
                                    ->searchable()
                                    ->allowHtml()
                                    ->getSearchResultsUsing(function (string $search) {
                                        $options = [
                                            'กระทรวงศึกษาธิการ' => 'กระทรวงศึกษาธิการ',
                                            'กระทรวงมหาดไทย' => 'กระทรวงมหาดไทย',
                                            'สำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน' => 'สำนักงานคณะกรรมการส่งเสริมการศึกษาเอกชน',
                                        ];

                                        // กรองตัวเลือกตามคำค้นหา
                                        return collect($options)
                                            ->filter(fn($label) => str_contains(strtolower($label), strtolower($search)))
                                            ->toArray();
                                    })
                                    ->createOptionForm([
                                        FilamentComponent\TextInput::make('name')
                                            ->label('ชื่อกระทรวง')
                                            ->required()
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return $data['name'];
                                    })
                                    ->required(),

                                FilamentComponent\Select::make('department')
                                    ->label(__('สังกัดสำนักงาน/กรม'))
                                    ->options([
                                        'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)' => 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)',
                                        'สำนักงานการศึกษาเอกชนจังหวัดยะลา' => 'สำนักงานการศึกษาเอกชนจังหวัดยะลา',
                                        'กรมส่งเสริมการปกครองท้องถิ่น' => 'กรมส่งเสริมการปกครองท้องถิ่น',
                                        'องค์การบริหารส่วนจังหวัดยะลา' => 'องค์การบริหารส่วนจังหวัดยะลา',
                                    ])
                                    ->searchable()
                                    ->allowHtml()
                                    ->getSearchResultsUsing(function (string $search) {
                                        $options = [
                                            'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)' => 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)',
                                            'สำนักงานการศึกษาเอกชนจังหวัดยะลา' => 'สำนักงานการศึกษาเอกชนจังหวัดยะลา',
                                            'กรมส่งเสริมการปกครองท้องถิ่น' => 'กรมส่งเสริมการปกครองท้องถิ่น',
                                            'องค์การบริหารส่วนจังหวัดยะลา' => 'องค์การบริหารส่วนจังหวัดยะลา',
                                        ];

                                        return collect($options)
                                            ->filter(fn($label) => str_contains(strtolower($label), strtolower($search)))
                                            ->toArray();
                                    })
                                    ->createOptionForm([
                                        FilamentComponent\TextInput::make('name')
                                            ->label('ชื่อสำนักงาน/กรม')
                                            ->required()
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return $data['name'];
                                    }),

                                FilamentComponent\Select::make('area')
                                    ->label(__('เขตพื้นที่การศึกษา'))
                                    ->options([
                                        'เขต 1' => 'เขต 1',
                                        'เขต 2' => 'เขต 2',
                                        'เขต 3' => 'เขต 3',
                                    ])
                                    ->searchable()
                                    ->allowHtml()
                                    ->getSearchResultsUsing(function (string $search) {
                                        $options = [
                                            'เขต 1' => 'เขต 1',
                                            'เขต 2' => 'เขต 2',
                                            'เขต 3' => 'เขต 3',
                                        ];

                                        return collect($options)
                                            ->filter(fn($label) => str_contains(strtolower($label), strtolower($search)))
                                            ->toArray();
                                    })
                                    ->createOptionForm([
                                        FilamentComponent\TextInput::make('name')
                                            ->label('ชื่อเขตพื้นที่การศึกษา')
                                            ->required()
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return $data['name'];
                                    }),
                            ]),

                        FilamentComponent\Grid::make(2)
                            ->schema([
                                FilamentComponent\Select::make('school_sizes')
                                    ->label(__('ประเภทสถานศึกษา (ขนาด)'))
                                    ->options([
                                        'ขนาดใหญ่' => 'ขนาดใหญ่',
                                        'ขนาดกลาง' => 'ขนาดกลาง',
                                        'ขนาดเล็ก' => 'ขนาดเล็ก',
                                    ])
                                    ->required(),

                                ThaiDatePicker::make('founding_date')
                                    ->label(__('วันเดือนปีที่ก่อตั้ง'))
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->nullable(),
                            ]),
                    ]),

                // STEP 2: ข้อมูลบุคลากร
                FilamentComponent\Wizard\Step::make('ข้อมูลบุคลากร')
                    ->description('กรอกข้อมูลบุคลากรของสถานศึกษา')
                    ->icon('heroicon-s-users')
                    ->schema([
                        FilamentComponent\Fieldset::make('ผู้อำนวยการ')
                            ->label('ผู้อำนวยการ')
                            ->schema([
                                FilamentComponent\Grid::make(3)
                                    ->schema([
                                        FilamentComponent\Select::make('principal_prefix_code')
                                            ->label(__('คำนำหน้า'))
                                            ->options([
                                                'นาย' => 'นาย',
                                                'นาง' => 'นาง',
                                                'นางสาว' => 'นางสาว',
                                            ])
                                            ->required(),
                                    ]),

                                FilamentComponent\Grid::make(3)
                                    ->schema([
                                        FilamentComponent\TextInput::make('principal_name_thai')
                                            ->label(__('ชื่อจริง'))
                                            ->required(),

                                        FilamentComponent\TextInput::make('principal_middle_name_thai')
                                            ->label(__('ชื่อกลาง'))
                                            ->nullable(),

                                        FilamentComponent\TextInput::make('principal_lastname_thai')
                                            ->label(__('นามสกุล'))
                                            ->required(),
                                    ]),
                            ]),

                        FilamentComponent\Fieldset::make('รองผู้อำนวยการ')
                            ->label('รองผู้อำนวยการ')
                            ->schema([
                                FilamentComponent\Grid::make(3)
                                    ->schema([
                                        FilamentComponent\Select::make('deputy_principal_prefix_code')
                                            ->label(__('คำนำหน้า'))
                                            ->options([
                                                'นาย' => 'นาย',
                                                'นาง' => 'นาง',
                                                'นางสาว' => 'นางสาว',
                                            ])
                                            ->nullable(),
                                    ]),

                                FilamentComponent\Grid::make(3)
                                    ->schema([
                                        FilamentComponent\TextInput::make('deputy_principal_name_thai')
                                            ->label(__('ชื่อจริง'))
                                            ->nullable(),

                                        FilamentComponent\TextInput::make('deputy_principal_middle_name_thai')
                                            ->label(__('ชื่อกลาง'))
                                            ->nullable(),

                                        FilamentComponent\TextInput::make('deputy_principal_lastname_thai')
                                            ->label(__('นามสกุล'))
                                            ->nullable(),
                                    ]),
                            ]),
                    ]),

                // STEP 3: ข้อมูลที่อยู่
                FilamentComponent\Wizard\Step::make('ข้อมูลที่อยู่')
                    ->description('กรอกข้อมูลที่อยู่ของสถานศึกษา')
                    ->icon('heroicon-s-map-pin')
                    ->schema([
                        FilamentComponent\Grid::make(3)->schema([
                            FilamentComponent\TextInput::make('house_id')->label(__('เลขที่ ที่อยู่'))->nullable(),
                            FilamentComponent\TextInput::make('village_no')->label(__('หมู่ที่'))->nullable(),
                            FilamentComponent\TextInput::make('road')->label(__('ถนน'))->nullable(),
                            FilamentComponent\Select::make('province')
                                ->label(__('จังหวัด'))
                                ->options($provinces)
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set) => $set('district', null))
                                ->required(),
                            FilamentComponent\Select::make('district')
                                ->label(__('อำเภอ'))
                                ->options(function (callable $get) use ($districts) {
                                    $province = $get('province');
                                    return $districts[$province] ?? [];
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set) => $set('sub_district', null))
                                ->required(),
                            FilamentComponent\Select::make('sub_district')
                                ->label(__('ตำบล'))
                                ->options(function (callable $get) use ($subDistricts) {
                                    $province = $get('province');
                                    $district = $get('district');
                                    return $subDistricts[$province][$district] ?? [];
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, callable $get) use ($postalCodes) {
                                    $province = $get('province');
                                    $district = $get('district');
                                    $subDistrict = $get('sub_district');
                                    $set('postal_code', $postalCodes[$province][$district][$subDistrict] ?? null);
                                })
                                ->required(),
                            FilamentComponent\TextInput::make('postal_code')->label(__('รหัสไปรษณีย์'))->reactive(),
                        ]),
                        FilamentComponent\Grid::make(4)->schema([
                            FilamentComponent\TextInput::make('phone')
                                ->label(__('โทรศัพท์'))
                                ->suffixIcon('heroicon-o-phone')
                                ->tel()
                                ->nullable(),
                            FilamentComponent\TextInput::make('fax')
                                ->label(__('โทรสาร'))
                                ->suffixIcon('heroicon-o-phone')
                                ->tel()
                                ->nullable(),
                            FilamentComponent\TextInput::make('email')
                                ->label(__('อีเมล์'))
                                ->suffixIcon('heroicon-o-envelope')
                                ->email()
                                ->nullable(),
                            FilamentComponent\TextInput::make('website')
                                ->label(__('เว็บไซต์'))
                                ->suffixIcon('heroicon-o-globe-alt')
                                ->url()
                                ->nullable()
                        ]),
                        FilamentComponent\Grid::make(2)->schema([
                            FilamentComponent\TextInput::make('latitude')->label(__('ละติจูด'))->reactive(),
                            FilamentComponent\TextInput::make('longitude')->label(__('ลองจิจูด'))->reactive()
                        ]),
                        FilamentComponent\Grid::make(1)->schema([
                            Map::make('location')
                                ->defaultLocation(latitude: $form->getRecord()->latitude ?? 13.736717, longitude: $form->getRecord()->longitude ?? 100.523186)
                                ->showMarker(true)
                                ->clickable(true)
                                ->zoom(10)
                                ->draggable(true)
                                ->afterStateUpdated(function ($state, $set) {
                                    $set('latitude', $state['lat']);
                                    $set('longitude', $state['lng']);
                                }),
                        ]),
                    ]),

                // STEP 4: รายละเอียดจำนวนประชากรในสถานศึกษา
                FilamentComponent\Wizard\Step::make('รายละเอียดจำนวนประชากรในสถานศึกษา')
                    ->description('กรอกข้อมูลจำนวนนักเรียนและบุคลากรของสถานศึกษา')
                    ->icon('heroicon-s-user-group')
                    ->schema([
                        FilamentComponent\Grid::make(3)->schema([
                            FilamentComponent\TextInput::make('student_amount')
                                ->label('จำนวนนักเรียน')
                                ->numeric()
                                ->suffix('คน')
                                ->required()
                                ->minValue(0)
                                ->helperText('กรอกจำนวนนักเรียนทั้งหมดในสถานศึกษา')
                                ->validationAttribute('จำนวนนักเรียน'),

                            FilamentComponent\TextInput::make('disadvantaged_student_amount')
                                ->label('จำนวนนักเรียนด้อยโอกาส')
                                ->numeric()
                                ->suffix('คน')
                                ->required()
                                ->minValue(0)
                                ->helperText('กรอกจำนวนนักเรียนด้อยโอกาสทั้งหมด')
                                ->validationAttribute('จำนวนนักเรียนด้อยโอกาส'),

                            FilamentComponent\TextInput::make('teacher_amount')
                                ->label('จำนวนครู')
                                ->numeric()
                                ->suffix('คน')
                                ->required()
                                ->minValue(0)
                                ->maxValue(500)
                                ->helperText('กรอกจำนวนครูทั้งหมดในสถานศึกษา')
                                ->validationAttribute('จำนวนครู'),
                        ]),

                    ]),

                // STEP 5: หลักสูตรการศึกษา
                FilamentComponent\Wizard\Step::make('หลักสูตรการศึกษาของสถานศึกษา')
                    ->description('กรอกข้อมูลหลักสูตรการศึกษาของสถานศึกษา')
                    ->icon('heroicon-m-academic-cap')
                    ->schema([
                        FilamentComponent\Select::make('school_course_type')
                            ->label(__('หลักสูตรที่ใช้'))
                            ->multiple()
                            ->options([
                                'หลักสูตรแกนกลางขั้นพื้นฐาน' => 'ประเภทที่ 1 หลักสูตรแกนกลางการศึกษาขั้นพื้นฐานที่ได้รับการปรับ',
                                'หลักสูตรแกนกลาง ๕๑' => 'ประเภทที่ 2 หลักสูตรที่ปรับเพิ่มเติมจากหลักสูตรประเภทที่ 1',
                                'หลักสูตรฐานสมรรถนะ' => 'ประเภทที่ 3 หลักสูตรฐานสมรรถนะ',
                                'หลักสูตรต่างประเทศ' => 'ประเภทที่ 4 หลักสูตรต่างประเทศ',
                                'อื่นๆ' => 'อื่นๆ',
                            ])
                            ->required(),
                        FilamentComponent\FileUpload::make('course_attachment')
                            ->label(__('ไฟล์แนบหลักสูตร'))
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.ms-excel',
                            ])
                            ->disk('public')
                            ->directory('school_course_attachments')
                            ->visibility('public')
                            ->storeFileNamesIn('original_filename')
                            ->helperText(new \Illuminate\Support\HtmlString('รองรับประเภทไฟล์ <i><strong>word, excel, powerpoint</strong></i> และ <i><strong>pdf</strong></i>'))
                            ->deleteUploadedFileUsing(function ($file) {
                                // ลบไฟล์เก่าก่อนบันทึกไฟล์ใหม่
                                IlluminateFacade\Storage::disk('public')->delete($file);
                            }),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $path = base_path('Modules/Dashboard/app/Filament/Resources/jsons/thailand_provinces_districts.json');

        if (!file_exists($path)) {
            throw new \Exception("File not found: $path");
        }

        $jsonData = IlluminateFacade\File::get($path);
        $provincesAndDistricts = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON format: " . json_last_error_msg());
        }

        // ตรวจสอบข้อมูลที่โหลดมา
        if (is_null($provincesAndDistricts)) {
            throw new \Exception('Failed to load provinces and districts data.');
        }

        // ใช้ array_column เพื่อลดลูปซ้อน
        $provinces = array_column($provincesAndDistricts, 'name_th', 'name_th');

        $districts = [];
        $subDistricts = [];

        foreach ($provincesAndDistricts as $province) {
            $districts[$province['name_th']] = array_column($province['amphure'], 'name_th', 'name_th');

            foreach ($province['amphure'] as $amphure) {
                $subDistricts[$province['name_th']][$amphure['name_th']] = array_column($amphure['tambon'], 'name_th', 'name_th');
            }
        }

        return $table
            ->columns([
                TextColumn::make('school_id')->label('รหัสสถานศึกษา')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('school_name_th')->label('ชื่อสถานศึกษา (ไทย)')->formatStateUsing(fn($state) => 'โรงเรียน ' . $state)
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->toggleable(),
                TextColumn::make('school_name_en')->label('ชื่อสถานศึกษา (อังกฤษ)')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ministry')->label('สังกัดกระทรวง')
                    ->toggleable(),
                TextColumn::make('department')->label('สังกัดสำนักงาน/กรม')
                    ->toggleable(),
                TextColumn::make('area')->label('เขตพื้นที่การศึกษา')
                    ->toggleable(),
                TextColumn::make('school_sizes')->label('ประเภทสถานศึกษา (ขนาด)')
                    ->toggleable(),
                TextColumn::make('principal_name_thai')->label('ผู้อำนวยการ')
                    ->formatStateUsing(function ($record) {
                        // ตรวจสอบว่ามีค่าก่อนนำมาใช้
                        $prefix = $record->principal_prefix_code ?? '';
                        $name = $record->principal_name_thai ?? '';
                        $middleName = $record->principal_middle_name_thai
                            ? ' ' . $record->principal_middle_name_thai
                            : '';
                        $lastName = $record->principal_lastname_thai ?? '';

                        // รวมชื่อเฉพาะเมื่อมีข้อมูล
                        $fullName = trim("{$prefix}{$name} {$middleName} {$lastName}");

                        // คืนค่าว่างหากไม่มีข้อมูล
                        return $fullName ?: '-';
                    })
                    ->toggleable(),
                TextColumn::make('deputy_principal_name_thai')->label('รองผู้อำนวยการ')
                    ->formatStateUsing(function ($record) {
                        // ตรวจสอบว่ามีค่าก่อนนำมาใช้
                        $prefix = $record->deputy_principal_prefix_code ?? '';
                        $name = $record->deputy_principal_name_thai ?? '';
                        $middleName = $record->deputy_principal_middle_name_thai
                            ? ' ' . $record->deputy_principal_middle_name_thai
                            : '';
                        $lastName = $record->deputy_principal_lastname_thai ?? '';

                        // รวมชื่อเฉพาะเมื่อมีข้อมูล
                        $fullName = trim("{$prefix}{$name} {$middleName} {$lastName}");

                        // คืนค่าว่างหากไม่มีข้อมูล
                        return $fullName ?: '-';
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                TextColumn::make('student_amount')->label('จำนวนนักเรียน')
                    ->alignCenter()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                TextColumn::make('disadvantaged_student_amount')->label('จำนวนนักเรียนด้อยโอกาส')
                    ->alignCenter()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                TextColumn::make('teacher_amount')->label('จำนวนครู')
                    ->alignCenter()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                TextColumn::make('school_course_type')->label('หลักสูตรที่ใช้')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('original_filename')
                    ->label('ไฟล์แนบนวัตกรรม')
                    ->weight(\Filament\Support\Enums\FontWeight::ExtraBold)
                    ->color('info')
                    ->limit(length: 30)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition(\Filament\Support\Enums\IconPosition::After)
                    ->toggleable()
                    ->url(fn($record) => $record->course_attachment ? asset('storage/' . $record->course_attachment) : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Filter::make('my_school')
                    ->label('แสดงเฉพาะโรงเรียนของฉัน')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => ($query->where('school_id', IlluminateFacade\Auth::user()->school_id))),

                SelectFilter::make('school_sizes')
                    ->label('ขนาดสถานศึกษา')
                    ->multiple()
                    ->options(SchoolModel::query()->whereNotNull('school_sizes')->pluck('school_sizes', 'school_sizes')->toArray()),

                SelectFilter::make('province')
                    ->label('จังหวัด')
                    ->multiple()
                    ->options($provinces)
                    ->preload(),

                SelectFilter::make('district')
                    ->label('อำเภอ')
                    ->multiple()
                    ->options(collect($districts)->mapWithKeys(fn($items, $key) => [$key => $key])->toArray())
                    ->preload(),

                SelectFilter::make('sub_district')
                    ->label('ตำบล')
                    ->multiple()
                    ->options(
                        collect($subDistricts)
                            ->flatMap(fn($districts) => collect($districts)
                                ->flatMap(fn($subDistricts) => $subDistricts))
                            ->mapWithKeys(fn($name) => [$name => $name])
                            ->toArray()
                    )
                    ->preload(),

                SelectFilter::make('ministry')
                    ->label('สังกัดกระทรวง')
                    ->multiple()
                    ->options(SchoolModel::query()->whereNotNull('ministry')->pluck('ministry', 'ministry')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('ดาวห์โหลดตาราง')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        FilamentComponent\TextInput::make('school_id')
                            ->label('รหัสสถานศึกษา')
                            ->placeholder('ทั้งหมด'),
                        FilamentComponent\TextInput::make('school_name')
                            ->label('ชื่อสถานศึกษา')
                            ->placeholder('ทั้งหมด'),
                        FilamentComponent\Select::make('province')
                            ->label('จังหวัด')
                            ->options($provinces)
                            ->searchable()
                            ->placeholder('ทั้งหมด'),
                        FilamentComponent\Select::make('district')
                            ->label('อำเภอ')
                            ->options(
                                collect($districts)
                                    ->flatMap(fn($items) => $items)
                                    ->unique()
                                    ->mapWithKeys(fn($item) => [$item => $item])
                                    ->toArray()
                            )
                            ->searchable()
                            ->placeholder('ทั้งหมด'),
                        FilamentComponent\Select::make('ministry')
                            ->label('สังกัดกระทรวง')
                            ->options(SchoolModel::query()->whereNotNull('ministry')->pluck('ministry', 'ministry')->toArray())
                            ->searchable()
                            ->placeholder('ทั้งหมด'),
                        FilamentComponent\Select::make('school_sizes')
                            ->label('ขนาดสถานศึกษา')
                            ->options([
                                'ขนาดใหญ่' => 'ขนาดใหญ่',
                                'ขนาดกลาง' => 'ขนาดกลาง',
                                'ขนาดเล็ก' => 'ขนาดเล็ก',
                            ])
                            ->placeholder('ทั้งหมด'),
                    ])
                    ->action(function (array $data) {
                        return Excel::download(
                            new \Modules\Dashboard\Exports\SchoolExport($data),
                            'yalapeo_' . now()->format('Y-m-d') . '_schools' . '.xlsx'
                        );
                    })
                    ->visible(fn() =>
                        IlluminateFacade\Auth::user()->role === UserRole::SUPERADMIN
                        || IlluminateFacade\Auth::user()->role === UserRole::SCHOOLADMIN
                        || IlluminateFacade\Auth::user()->role === UserRole::OFFICER),

                Tables\Actions\Action::make('import')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->form([
                        FilamentComponent\Section::make()
                            ->schema([
                                FilamentComponent\Placeholder::make('template_info')
                                    ->content(new HtmlString('
                    <div class="flex justify-start">
                        <a href="' . route('school.download-template') . '" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2rem] px-3 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>ดาวน์โหลดแม่แบบ</span>
                        </a>
                    </div>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">ดาวน์โหลดแม่แบบไฟล์ Excel สำหรับใช้นำเข้าข้อมูลโรงเรียน</p>
                    </div>
                ')),
                            ])
                            ->columnSpanFull(),
                        FilamentComponent\FileUpload::make('file')
                            ->label('ไฟล์ Excel')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->helperText('รองรับเฉพาะไฟล์ .xlsx และ .xls เท่านั้น')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            // เพิ่มการล็อก
                            Log::info('Starting import process');

                            // ตรวจสอบว่าได้รับไฟล์หรือไม่
                            if (!isset($data['file']) || empty($data['file'])) {
                                Log::error('No file received for import');
                                Notification::make()
                                    ->title('ไม่พบไฟล์')
                                    ->body('ไม่สามารถอ่านไฟล์ที่อัปโหลดได้')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $import = new \Modules\Dashboard\Imports\SchoolImport();
                            Excel::import($import, $data['file']);

                            Log::info('Import completed', ['rows' => $import->getRowCount()]);

                            // แสดงข้อมูลสรุป
                            $importCount = $import->getRowCount();

                            if ($importCount > 0) {
                                Notification::make()
                                    ->title('นำเข้าข้อมูลสำเร็จ')
                                    ->body('นำเข้าข้อมูลจำนวน ' . $importCount . ' รายการสำเร็จ')
                                    ->success()
                                    ->send();
                            } else {
                                // แสดงข้อผิดพลาด (ถ้ามี)
                                $errors = $import->getErrors();

                                if (!empty($errors)) {
                                    Log::warning('Import errors:', $errors);
                                    Notification::make()
                                        ->title('นำเข้าข้อมูลไม่สำเร็จ')
                                        ->body('พบข้อผิดพลาด: ' . implode(', ', array_slice($errors, 0, 3)) .
                                            (count($errors) > 3 ? ' และอื่นๆ อีก ' . (count($errors) - 3) . ' รายการ' : ''))
                                        ->danger()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('ไม่พบข้อมูลที่สามารถนำเข้าได้')
                                        ->body('กรุณาตรวจสอบไฟล์แม่แบบและข้อมูลที่ต้องการนำเข้า โดยเฉพาะชื่อคอลัมน์ในแถวแรก')
                                        ->warning()
                                        ->send();
                                }
                            }
                        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                            Log::error('Excel validation exception:', [
                                'message' => $e->getMessage(),
                                'failures' => $e->failures()
                            ]);

                            $failures = $e->failures();
                            $errorMessages = [];

                            foreach ($failures as $failure) {
                                $errorMessages[] = 'แถวที่ ' . $failure->row() . ': ' . implode(', ', $failure->errors());
                            }

                            Notification::make()
                                ->title('ข้อมูลไม่ถูกต้อง')
                                ->body(implode('<br>', array_slice($errorMessages, 0, 3)) .
                                    (count($errorMessages) > 3 ? '<br>และอื่นๆ อีก ' . (count($errorMessages) - 3) . ' รายการ' : ''))
                                ->danger()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Import exception:', [
                                'message' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);

                            Notification::make()
                                ->title('เกิดข้อผิดพลาด')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn() =>
                        IlluminateFacade\Auth::user()->role === UserRole::SUPERADMIN),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                ]),

            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistComponent\Section::make('ข้อมูลพื้นฐาน')
                    ->icon('heroicon-o-home')
                    ->collapsible()
                    ->description('ข้อมูลทั่วไปของสถานศึกษา')
                    ->schema([
                        InfolistComponent\Grid::make(2)
                            ->schema([
                                InfolistComponent\TextEntry::make('school_id')
                                    ->label('รหัสสถานศึกษา')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->color('primary')
                                    ->copyable()
                                    ->copyMessage('คัดลอกรหัสสถานศึกษาแล้ว')
                                    ->icon('heroicon-m-identification'),
                                InfolistComponent\TextEntry::make('school_name_th')
                                    ->label('ชื่อสถานศึกษา (ไทย)')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->formatStateUsing(fn($state) => 'โรงเรียน ' . $state)
                                    ->color('success')
                                    ->icon('heroicon-m-building-library'),
                                InfolistComponent\TextEntry::make('school_name_en')
                                    ->label('ชื่อสถานศึกษา (อังกฤษ)')
                                    ->visible(fn($record) => !empty($record->school_name_en))
                                    ->color('gray')
                                    ->icon('heroicon-m-language'),
                            ]),
                        InfolistComponent\Grid::make(3)
                            ->schema([
                                InfolistComponent\TextEntry::make('ministry')
                                    ->label('สังกัดกระทรวง')
                                    ->icon('heroicon-m-building-office-2'),
                                InfolistComponent\TextEntry::make('department')
                                    ->label('สังกัดสำนักงาน/กรม')
                                    ->icon('heroicon-m-building-office'),
                                InfolistComponent\TextEntry::make('area')
                                    ->label('เขตพื้นที่การศึกษา')
                                    ->icon('heroicon-m-map'),
                                InfolistComponent\TextEntry::make('school_sizes')
                                    ->label('ขนาดสถานศึกษา')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'เล็ก' => 'info',
                                        'กลาง' => 'warning',
                                        'ใหญ่' => 'success',
                                        'ใหญ่พิเศษ' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon('heroicon-m-building-office'),
                                InfolistComponent\TextEntry::make('founding_date')
                                    ->label('วันที่ก่อตั้ง')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar'),
                            ]),
                    ]),

                InfolistComponent\Section::make('ข้อมูลบุคลากร')
                    ->icon('heroicon-o-users')
                    ->collapsible()
                    ->description('ข้อมูลผู้บริหารสถานศึกษา')
                    ->schema([
                        InfolistComponent\Grid::make(2)
                            ->schema([
                                InfolistComponent\TextEntry::make('principal_name_thai')
                                    ->label('ผู้อำนวยการสถานศึกษา')
                                    ->formatStateUsing(function ($record) {
                                        $prefix = $record->principal_prefix_code ?? '';
                                        $name = $record->principal_name_thai ?? '';
                                        $middleName = $record->principal_middle_name_thai
                                            ? ' ' . $record->principal_middle_name_thai
                                            : '';
                                        $lastName = $record->principal_lastname_thai ?? '';
                                        $fullName = trim("{$prefix}{$name}{$middleName} {$lastName}");
                                        return $fullName ?: '-';
                                    })
                                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                                    ->icon('heroicon-m-user')
                                    ->default('-')
                                    ->color('primary'),
                                InfolistComponent\TextEntry::make('deputy_principal_name_thai')
                                    ->label('รองผู้อำนวยการสถานศึกษา')
                                    ->formatStateUsing(function ($record) {
                                        $prefix = $record->deputy_principal_prefix_code ?? '';
                                        $name = $record->deputy_principal_name_thai ?? '';
                                        $middleName = $record->deputy_principal_middle_name_thai
                                            ? ' ' . $record->deputy_principal_middle_name_thai
                                            : '';
                                        $lastName = $record->deputy_principal_lastname_thai ?? '';
                                        $fullName = trim("{$prefix}{$name}{$middleName} {$lastName}");
                                        return $fullName ?: '-';
                                    })
                                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                                    ->icon('heroicon-m-user')
                                    ->default('-')
                                    ->color('info'),
                            ]),
                    ]),

                InfolistComponent\Section::make('ข้อมูลที่อยู่')
                    ->icon('heroicon-o-map-pin')
                    ->collapsible()
                    ->description('ที่ตั้งและช่องทางการติดต่อสถานศึกษา')
                    ->schema([
                        InfolistComponent\Section::make('ที่อยู่')
                            ->compact()
                            ->schema([
                                InfolistComponent\Grid::make(3)
                                    ->schema([
                                        InfolistComponent\TextEntry::make('house_id')
                                            ->label('เลขที่')
                                            ->default('-'),
                                        InfolistComponent\TextEntry::make('village_no')
                                            ->label('หมู่ที่')
                                            ->default('-'),
                                        InfolistComponent\TextEntry::make('road')
                                            ->label('ถนน')
                                            ->default('-'),
                                        InfolistComponent\TextEntry::make('sub_district')
                                            ->label('ตำบล')
                                            ->default('-'),
                                        InfolistComponent\TextEntry::make('district')
                                            ->label('อำเภอ')
                                            ->default('-'),
                                        InfolistComponent\TextEntry::make('province')
                                            ->label('จังหวัด')
                                            ->default('-'),
                                        InfolistComponent\TextEntry::make('postal_code')
                                            ->label('รหัสไปรษณีย์')
                                            ->default('-'),
                                    ]),
                            ]),
                        InfolistComponent\Grid::make(2)
                            ->schema([
                                InfolistComponent\TextEntry::make('phone')
                                    ->label('โทรศัพท์')
                                    ->icon('heroicon-m-phone')
                                    ->url(fn($state) => $state ? "tel:{$state}" : null)
                                    ->color('success')
                                    ->default('-')
                                    ->copyable()
                                    ->copyMessage('คัดลอกเบอร์โทรศัพท์แล้ว'),
                                InfolistComponent\TextEntry::make('fax')
                                    ->label('โทรสาร')
                                    ->icon('heroicon-m-phone')
                                    ->color('gray')
                                    ->default('-')
                                    ->copyable()
                                    ->copyMessage('คัดลอกเบอร์โทรสารแล้ว'),
                                InfolistComponent\TextEntry::make('email')
                                    ->label('อีเมล')
                                    ->icon('heroicon-m-envelope')
                                    ->color('primary')
                                    ->url(fn($state) => $state ? "mailto:{$state}" : null)
                                    ->openUrlInNewTab()
                                    ->default('-')
                                    ->copyable()
                                    ->copyMessage('คัดลอกอีเมลแล้ว'),
                                InfolistComponent\TextEntry::make('website')
                                    ->label('เว็บไซต์')
                                    ->icon('heroicon-m-globe-alt')
                                    ->color('info')
                                    ->url(fn($state) => $state)
                                    ->openUrlInNewTab()
                                    ->default('-')
                                    ->copyable()
                                    ->copyMessage('คัดลอกเว็บไซต์แล้ว'),
                            ]),
                        InfolistComponent\Grid::make(1)
                            ->schema([
                                InfolistComponent\TextEntry::make('location')
                                    ->label('พิกัดตำแหน่งที่ตั้ง')
                                    ->state(function ($record) {
                                        if (!$record->latitude || !$record->longitude) {
                                            return null;
                                        }
                                        return "https://www.google.com/maps?q={$record->latitude},{$record->longitude}";
                                    })
                                    ->visible(fn($state) => !empty($state))
                                    ->formatStateUsing(fn($state, $record) => "ละติจูด: {$record->latitude}, ลองจิจูด: {$record->longitude}")
                                    ->url(fn($state) => $state)
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-m-map')
                                    ->color('success')
                                    ->extraAttributes([
                                        'class' => 'border border-green-300 p-2 rounded-lg',
                                    ])
                                    ->hint('คลิกเพื่อดูแผนที่ Google Maps'),
                            ]),
                    ]),

                InfolistComponent\Section::make('จำนวนนักเรียนและครู')
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->description('ข้อมูลจำนวนนักเรียนและบุคลากร')
                    ->schema([
                        InfolistComponent\Grid::make(4)
                            ->schema([
                                InfolistComponent\TextEntry::make('student_amount')
                                    ->label('จำนวนนักเรียน')
                                    ->formatStateUsing(fn($state) => number_format($state) . ' คน')
                                    ->icon('heroicon-m-academic-cap')
                                    ->color('success')
                                    ->size('xl')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->extraAttributes([
                                        'class' => 'bg-green-50 p-3 rounded-lg border border-green-200',
                                    ]),
                                InfolistComponent\TextEntry::make('disadvantaged_student_amount')
                                    ->label('นักเรียนด้อยโอกาส')
                                    ->formatStateUsing(fn($state) => number_format($state) . ' คน')
                                    ->icon('heroicon-m-academic-cap')
                                    ->color('danger')
                                    ->size('xl')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->extraAttributes([
                                        'class' => 'bg-amber-50 p-3 rounded-lg border border-amber-200',
                                    ]),
                                InfolistComponent\TextEntry::make('teacher_amount')
                                    ->label('จำนวนครู')
                                    ->formatStateUsing(fn($state) => number_format($state) . ' คน')
                                    ->icon('heroicon-m-user-group')
                                    ->color('primary')
                                    ->size('xl')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-50 p-3 rounded-lg border border-blue-200',
                                    ]),
                                InfolistComponent\TextEntry::make('ratio')
                                    ->label('อัตราส่วนครู:นักเรียน')
                                    ->state(function ($record) {
                                        if (!$record->teacher_amount || !$record->student_amount)
                                            return '-';
                                        $ratio = round($record->student_amount / $record->teacher_amount, 1);
                                        return "1:{$ratio}";
                                    })
                                    ->icon('heroicon-m-calculator')
                                    ->color('info')
                                    ->size('xl')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->extraAttributes([
                                        'class' => 'bg-cyan-50 p-3 rounded-lg border border-cyan-200',
                                    ]),
                            ]),
                    ]),

                InfolistComponent\Section::make('หลักสูตรการศึกษา')
                    ->icon('heroicon-o-book-open')
                    ->collapsible()
                    ->description('ข้อมูลหลักสูตรที่ใช้ในการเรียนการสอน')
                    ->schema([
                        InfolistComponent\TextEntry::make('school_course_type')
                            ->label('หลักสูตรที่ใช้')
                            ->default('-')
                            ->badge()
                            ->color('primary')
                            ->listWithLineBreaks(),
                        InfolistComponent\TextEntry::make('course_attachment')
                            ->label('เอกสารแนบหลักสูตร')
                            ->formatStateUsing(fn($state, $record) => $record->original_filename ?? $state)
                            ->color('primary')
                            ->icon('heroicon-m-document')
                            ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                            ->openUrlInNewTab()
                            ->visible(fn($state) => !empty($state))
                            ->badge()
                            ->extraAttributes([
                                'class' => 'font-medium',
                            ]),
                    ]),
            ]);
    }

    public function deleteCourseAttachment($record)
    {
        if ($record->course_attachment) {
            IlluminateFacade\Storage::disk('public')->delete($record->course_attachment);
            $record->course_attachment = null;
            $record->save();
        }
    }

    public static function getRelations(): array
    {
        return [
            'schoolInnovations' => SchoolInnovationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'view' => Pages\ViewSchool::route('/{record}'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // ถ้าเป็น SchoolAdmin ให้แสดงเฉพาะข้อมูลของโรงเรียนตนเอง
        if (IlluminateFacade\Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', IlluminateFacade\Auth::user()->school_id);
        }

        return $query;
    }
}
