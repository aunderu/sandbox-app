<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Enums\UserRole;
use Filament\Forms\Components as Components;
use Filament\Tables\Columns as Columns;
use Modules\Dashboard\Filament\Resources\StudentNumberResource\Pages;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Modules\Sandbox\Models\StudentNumberModel;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Sandbox\Models\GradeLevelsModel;
use Modules\Sandbox\Models\SchoolModel;

class StudentNumberResource extends Resource
{
    protected static ?string $model = StudentNumberModel::class;

    protected static ?string $navigationIcon = 'heroicon-s-user-group';

    protected static ?string $modelLabel = 'ตารางจำนวนนักเรียน';

    protected static ?string $navigationGroup = "โรงเรียน";

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        // สถิติจำนวนนักเรียนชาย
        $totalMale = StudentNumberModel::sum('male_count');

        // สถิติจำนวนนักเรียนหญิง
        $totalFemale = StudentNumberModel::sum('female_count');

        // สถิติจำนวนนักเรียนทั้งหมด
        $totalStudents = $totalMale + $totalFemale;

        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
            $totalMale = StudentNumberModel::where('school_id', Auth::user()->school_id)->sum('male_count');
            $totalFemale = StudentNumberModel::where('school_id', Auth::user()->school_id)->sum('female_count');
            $totalStudents = $totalMale + $totalFemale;
        }

        return $totalStudents;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'จำนวนนักเรียนทั้งหมด';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Select::make('school_id')->label(__('สถานศึกษา'))
                    ->disabled(fn() => Auth::user()->role === UserRole::SCHOOLADMIN)
                    ->relationship('school', 'school_name_th')
                    ->prefix('โรงเรียน')
                    ->searchable()
                    ->columnSpanFull()
                    ->required()
                    ->default(fn() => Auth::user()->school_id)
                    ->searchPrompt('เพิ่มชื่อโรงเรียนเพื่อค้นหา...')
                    ->helperText(new HtmlString('กรณีที่ไม่มีชื่อโรงเรียนของท่านแสดง กรุณาติดต่อ<i><strong>ผู้ดูแลระบบ</strong></i>')),
                Components\Select::make('year_id')
                    ->label(__('ระดับชั้น'))
                    ->relationship('grade', 'grade_name')
                    ->required()
                    ->validationAttribute('ระดับชั้น')
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // ตรวจสอบว่ามีข้อมูลซ้ำหรือไม่
                        $schoolId = $get('school_id');
                        $educationYear = $get('education_year');

                        if (!$schoolId || !$educationYear || !$state) {
                            return;
                        }

                        $recordId = request()->route('record');
                        $query = StudentNumberModel::query()
                            ->where('school_id', $schoolId)
                            ->where('year_id', $state)
                            ->where('education_year', $educationYear);

                        if ($recordId) {
                            $query->where('id', '!=', $recordId);
                        }

                        $exists = $query->exists();

                        if ($exists) {
                            $set('year_id', null);
                            Notification::make()
                                ->title('ระดับชั้นนี้มีข้อมูลอยู่แล้วในปีการศึกษานี้')
                                ->danger()
                                ->send();
                        }
                    }),
                Components\TextInput::make('education_year')
                    ->label('ปีการศึกษา')
                    ->numeric()
                    ->required(),
                Components\TextInput::make('male_count')
                    ->label('จำนวนนักเรียนชาย')
                    ->numeric()
                    ->required(),
                Components\TextInput::make('female_count')
                    ->label('จำนวนนักเรียนหญิง')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('education_year')->label('ปีการศึกษา')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('school.school_name_th')->label('โรงเรียน')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('grade.grade_name')->label('ระดับชั้น')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('male_count')->label('จำนวนนักเรียนชาย')
                    ->sortable()
                    ->alignCenter()
                    ->searchable(),
                Columns\TextColumn::make('female_count')->label('จำนวนนักเรียนหญิง')
                    ->sortable()
                    ->alignCenter()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('โรงเรียน')
                    ->options(SchoolModel::pluck('school_name_th', 'school_id'))
                    ->searchable()
                    ->preload()
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }

                        $school = SchoolModel::where('school_id', $data['value'])->first();
                        return $school ? "โรงเรียน: {$school->school_name_th}" : null;
                    }),

                Tables\Filters\SelectFilter::make('grade_id')
                    ->label('ระดับชั้น')
                    ->multiple() 
                    ->options(GradeLevelsModel::pluck('grade_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['values']) || $data['values'] === []) {
                            return null;
                        }

                        if (count($data['values']) === 1) {
                            $grade = GradeLevelsModel::find($data['values'][0]);
                            return $grade ? "ระดับชั้น: {$grade->grade_name}" : null;
                        }

                        return "ระดับชั้น: " . count($data['values']) . " รายการ";
                    }),

                Tables\Filters\SelectFilter::make('education_year')
                    ->label('ปีการศึกษา')
                    ->options(fn() => static::getEducationYears())
                    // ->default(date('Y') + 543)
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }

                        return "ปีการศึกษา: {$data['value']}";
                    }),
            ])
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('ดาวห์โหลดตาราง')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form(function () {
                        $formComponents = [];

                        if (Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::OFFICER) {
                            $formComponents[] = Components\Select::make('school_id')
                                ->label('โรงเรียน')
                                ->relationship('school', 'school_name_th')
                                ->searchable()
                                ->preload()
                                ->placeholder('ทั้งหมด');
                        }

                        $formComponents[] = Components\Select::make('year_id')
                            ->label('ระดับชั้น')
                            ->relationship('grade', 'grade_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('ทั้งหมด');

                        $formComponents[] = Components\TextInput::make('education_year')
                            ->label('ปีการศึกษา')
                            ->numeric()
                            ->placeholder('ทั้งหมด');

                        return $formComponents;
                    })
                    ->action(function (array $data) {
                        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
                            $data['school_id'] = Auth::user()->school_id;
                        }

                        return Excel::download(
                            new \Modules\Dashboard\Exports\StudentNumbersExport($data),
                            'student_numbers_' . now()->format('Y-m-d') . '.xlsx'
                        );
                    }),

                Tables\Actions\Action::make('import')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->form([
                        Components\Section::make()
                            ->schema([
                                Components\Placeholder::make('template_info')
                                    ->content(new HtmlString('
                                    <div class="flex justify-start">
                                        <a href="' . route('student-numbers.download-template') . '" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2rem] px-3 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>ดาวน์โหลดแม่แบบ</span>
                                        </a>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">ดาวน์โหลดแม่แบบไฟล์ Excel สำหรับใช้นำเข้าข้อมูลจำนวนนักเรียน (จำเป็น)</p>
                                    </div>
                                ')),
                            ])
                            ->columnSpanFull(),
                        Components\FileUpload::make('file')
                            ->label('ไฟล์ Excel')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->helperText('รองรับเฉพาะไฟล์ .xlsx และ .xls เท่านั้น')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new \Modules\Dashboard\Imports\StudentNumbersImport();
                            Excel::import($import, $data['file']);

                            Notification::make()
                                ->title('นำเข้าข้อมูลสำเร็จ')
                                ->body('นำเข้าข้อมูลจำนวน ' . $import->getRowCount() . ' รายการสำเร็จ')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('เกิดข้อผิดพลาด')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
                ]),
            ]);
    }

    public static function getEducationYears(): array
    {
        $currentThaiYear = (int) date('Y') + 543;
        $startYear = $currentThaiYear - 10;

        $years = [];
        for ($i = $startYear; $i <= $currentThaiYear; $i++) {
            $years[$i] = (string) $i;
        }

        return $years;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentNumbers::route('/'),
            'create' => Pages\CreateStudentNumber::route('/create'),
            'edit' => Pages\EditStudentNumber::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ถ้าเป็น SchoolAdmin ให้แสดงเฉพาะข้อมูลของโรงเรียนตนเอง
        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }
}
