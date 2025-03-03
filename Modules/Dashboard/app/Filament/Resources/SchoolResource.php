<?php

namespace Modules\Dashboard\Filament\Resources;

use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components as FilamentComponent;
use Illuminate\Support\Facades as IlluminateFacade;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

use Modules\Dashboard\Filament\Resources\SchoolResource\Pages;
use Modules\Dashboard\Filament\Imports\SchoolModelImporter;
use Modules\Dashboard\Filament\Resources\SchoolResource\RelationManagers\SchoolInnovationsRelationManager;
use Modules\Sandbox\Models\SchoolModel;

use Phattarachai\FilamentThaiDatePicker\ThaiDatePicker;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction as TablesExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                FilamentComponent\Wizard\Step::make('ข้อมูลพื้นฐาน')
                    ->description('กรอกข้อมูลพื้นฐานของสถานศึกษา')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        FilamentComponent\TextInput::make('school_id')->label(__('รหัสสถานศึกษา'))->minLength(3)->maxLength(255)->unique(ignoreRecord: true)->required(),
                        FilamentComponent\Grid::make(2)->schema([
                            FilamentComponent\TextInput::make('school_name_th')->label(__('ชื่อสถานศึกษา (ไทย)'))->maxLength(255)->required(),
                            FilamentComponent\TextInput::make('school_name_en')->label(__('ชื่อสถานศึกษา (อังกฤษ)'))->maxLength(255)->nullable()
                        ]),
                        FilamentComponent\Grid::make(3)->schema([
                            FilamentComponent\TextInput::make('ministry')->label(__('สังกัดกระทรวง'))->maxLength(255)->required(),
                            FilamentComponent\TextInput::make('department')->label(__('สังกัดสำนักงาน/กรม'))->maxLength(255)->nullable(),
                            FilamentComponent\TextInput::make('area')->label(__('เขตพื้นที่การศึกษา'))->maxLength(255)->nullable()
                        ]),
                        FilamentComponent\Grid::make(2)->schema([
                            FilamentComponent\Select::make('school_sizes')
                                ->label(__('ประเภทสถานศึกษา (ขนาด)'))
                                ->options([
                                    'ขนาดใหญ่' => 'ขนาดใหญ่',
                                    'ขนาดกลาง' => 'ขนาดกลาง',
                                    'ขนาดเล็ก' => 'ขนาดเล็ก',
                                ])
                                ->required(),
                            ThaiDatePicker::make('founding_date')->label(__('วันเดือนปีที่ก่อตั้ง'))->suffixIcon('heroicon-o-calendar')->nullable(),
                        ]),
                    ]),

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
                    ->query(fn(Builder $query): Builder => $query->where('school_id', IlluminateFacade\Auth::user()->school_id)),

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
                Tables\Actions\EditAction::make()
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(SchoolModelImporter::class)
                    ->label('Import')
                    ->visible(fn() => IlluminateFacade\Auth::user()->isSuperAdmin())
                    ->icon('heroicon-s-inbox-arrow-down'),
                ExportAction::make()->exports([
                    ExcelExport::make()->queue()
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                ]),
                TablesExportBulkAction::make()->exports([
                    ExcelExport::make()->fromModel()->except([
                        'student_amount',
                        'disadventaged_student_amount',
                        'teacher_amount',
                        'course_attachment',
                        'created_at',
                        'updated_at',
                    ]),
                    ExcelExport::make()->fromModel()->only([
                        'school_id',
                        'school_name_th',
                        'school_name_en',
                        'ministry',
                        'department',
                        'area',
                        'school_sizes',
                        'founding_date',
                        'school_course_type',
                        'house_id',
                        'village_no',
                        'road',
                        'province',
                        'district',
                        'sub_district',
                        'postal_code',
                        'phone',
                        'fax',
                        'email',
                        'website',
                        'latitude',
                        'longitude',
                    ])->withColumns([
                                Column::make('school_id')->heading('รหัสสถานศึกษา'),
                                Column::make('school_name_th')->heading('ชื่อสถานศึกษา (ไทย)'),
                                Column::make('school_name_en')->heading('ชื่อสถานศึกษา (อังกฤษ)'),
                                Column::make('ministry')->heading('สังกัดกระทรวง'),
                                Column::make('department')->heading('สังกัดสำนักงาน/กรม'),
                                Column::make('area')->heading('เขตพื้นที่การศึกษา'),
                                Column::make('school_sizes')->heading('ประเภทสถานศึกษา (ขนาด)'),
                                Column::make('founding_date')->heading('วันเดือนปีที่ก่อตั้ง'),
                                Column::make('house_id')->heading('เลขที่ ที่อยู่'),
                                Column::make('village_no')->heading('หมู่ที่'),
                                Column::make('road')->heading('ถนน'),
                                Column::make('sub_district')->heading('ตำบล'),
                                Column::make('district')->heading('อำเภอ'),
                                Column::make('province')->heading('จังหวัด'),
                                Column::make('postal_code')->heading('รหัสไปรษณีย์'),
                                Column::make('phone')->heading('โทรศัพท์'),
                                Column::make('fax')->heading('โทรสาร'),
                                Column::make('email')->heading('อีเมล์'),
                                Column::make('website')->heading('เว็บไซต์'),
                                Column::make('latitude')->heading('ละติจูด'),
                                Column::make('longitude')->heading('ลองจิจูด'),
                                Column::make('school_course_type')->heading('หลักสูตรที่ใช้'),
                            ])
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
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }
}
