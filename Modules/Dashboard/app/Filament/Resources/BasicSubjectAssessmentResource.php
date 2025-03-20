<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Enums\UserRole;
use Filament\Forms\Components as Components;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Tables\Columns as Columns;
use Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Pages;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Sandbox\Models\BasicSubjectAssessmentModel;
use Modules\Sandbox\Models\SchoolModel;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components as InfoComponents;

class BasicSubjectAssessmentResource extends Resource
{
    protected static ?string $model = BasicSubjectAssessmentModel::class;

    protected static ?string $navigationIcon = 'heroicon-s-book-open';

    protected static ?string $modelLabel = 'ผลการประเมินสมรรถนะวิชาพื้นฐาน';

    protected static ?string $navigationGroup = "การประเมินผล";

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = true;

    public static function shouldRegisterNavigation(): bool
    {
        // ซ่อนเมนูเฉพาะกรณี School Admin
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $schoolId = Auth::user()->school_id;
            $currentYear = date('Y') + 543;

            $exists = static::getModel()::where('school_id', $schoolId)
                ->where('education_year', $currentYear)
                ->exists();

            if (!$exists) {
                return false; // ซ่อนเมนูนี้ เพื่อให้ Provider แสดงแทน
            }
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Select::make('school_id')
                    ->label('โรงเรียน')
                    ->disabled(fn() => Auth::user()->role === UserRole::SCHOOLADMIN)
                    ->relationship('school', 'school_name_th')
                    ->prefix('โรงเรียน')
                    ->searchable()
                    ->required()
                    ->default(fn() => Auth::user()->school_id)
                    ->searchPrompt('เพิ่มชื่อโรงเรียนเพื่อค้นหา...')
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // เมื่อมีการเปลี่ยนโรงเรียน ให้ตรวจสอบข้อมูลซ้ำ
                        $educationYear = $get('education_year');
                        if ($state && $educationYear) {
                            $duplicateCheck = self::hasDuplicate($state, $educationYear);
                            if ($duplicateCheck) {
                                Notification::make()
                                    ->warning()
                                    ->title('ข้อควรระวัง')
                                    ->body("มีข้อมูลการประเมินของโรงเรียนและปีการศึกษานี้อยู่แล้ว โปรดเลือกโรงเรียนหรือปีการศึกษาอื่น")
                                    ->persistent()
                                    ->send();
                            }
                        }
                    }),

                Components\TextInput::make('education_year')
                    ->label('ปีการศึกษา')
                    ->numeric()
                    ->required()
                    ->default(date('Y') + 543)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // เมื่อมีการเปลี่ยนปีการศึกษา ให้ตรวจสอบข้อมูลซ้ำ
                        $schoolId = $get('school_id');
                        if ($state && $schoolId) {
                            $duplicateCheck = self::hasDuplicate($schoolId, $state);
                            if ($duplicateCheck) {
                                Notification::make()
                                    ->warning()
                                    ->title('ข้อควรระวัง')
                                    ->body("มีข้อมูลการประเมินของโรงเรียนและปีการศึกษานี้อยู่แล้ว โปรดเลือกโรงเรียนหรือปีการศึกษาอื่น")
                                    ->persistent()
                                    ->send();
                            }
                        }
                    }),

                Components\TextInput::make('id')
                    ->label('รหัสประเมิน')
                    ->disabled()
                    ->hiddenOn('create')
                    ->dehydrated(fn($state) => filled($state))
                    ->afterStateHydrated(function ($component, $state, $record) {
                        // ถ้ามีการโหลดข้อมูลเดิม จะแสดงค่า ID
                        if ($record && !$state) {
                            $component->state($record->id);
                        }
                    }),

                Components\Section::make('คะแนนวิชาพื้นฐาน')
                    ->description('กรอกคะแนนวิชาพื้นฐานในแต่ละวิชา')
                    ->schema([
                        Components\TextInput::make('thai_score')
                            ->label('คะแนนวิชาภาษาไทย')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-book-open'),

                        Components\TextInput::make('math_score')
                            ->label('คะแนนวิชาคณิตศาสตร์')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-variable'),

                        Components\TextInput::make('science_score')
                            ->label('คะแนนวิชาวิทยาศาสตร์')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-beaker'),

                        Components\TextInput::make('english_score')
                            ->label('คะแนนวิชาอังกฤษ')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-language'),
                    ])->columns(2),
            ]);
    }

    /**
     * ตรวจสอบว่ามีการประเมินของโรงเรียนในปีการศึกษานี้อยู่แล้วหรือไม่
     * 
     * @param string $schoolId รหัสโรงเรียน
     * @param string $educationYear ปีการศึกษา
     * @param string|null $excludeId ID ที่จะไม่นำมาพิจารณา (สำหรับกรณีแก้ไข)
     * @return bool มีข้อมูลซ้ำหรือไม่
     */
    public static function hasDuplicate($schoolId, $educationYear, $excludeId = null): bool
    {
        $query = BasicSubjectAssessmentModel::where('school_id', $schoolId)
            ->where('education_year', $educationYear);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * สร้าง ID อัตโนมัติสำหรับการประเมินวิชาพื้นฐาน
     * 
     * @param string $schoolId รหัสโรงเรียน
     * @param string|null $educationYear ปีการศึกษาที่ใช้ (ถ้าไม่ระบุจะใช้ปีปัจจุบัน)
     * @return string รหัสที่สร้าง
     */
    public static function generateSubjectId($schoolId, $educationYear = null)
    {
        $prefix = "SUBJ{$schoolId}";
        $year = $educationYear ?: (date('Y') + 543);
        $randomString = strtoupper(substr(md5(uniqid()), 0, 6));
        return "{$prefix}{$year}{$randomString}";
    }

    // เพิ่ม hooks สำหรับการสร้างและแก้ไขข้อมูล
    public static function getModelEventHandlers(): array
    {
        return [
            'creating' => [static::class, 'handleCreating'],
        ];
    }

    // จัดการเหตุการณ์ creating โมเดล
    public static function handleCreating(BasicSubjectAssessmentModel $record): void
    {
        // ตรวจสอบว่ามีข้อมูลซ้ำหรือไม่
        $duplicateCheck = self::hasDuplicate($record->school_id, $record->education_year);

        if ($duplicateCheck) {
            throw new \Exception("ไม่สามารถสร้างรายการได้ เนื่องจากมีข้อมูลการประเมินของโรงเรียนและปีการศึกษานี้อยู่แล้ว");
        }

        // สร้าง ID
        if (!$record->id) {
            $record->id = self::generateSubjectId($record->school_id, $record->education_year);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('education_year')
                    ->label('ปีการศึกษา')
                    ->sortable()
                    ->searchable(),

                Columns\TextColumn::make('school.school_name_th')
                    ->label('โรงเรียน')
                    ->sortable()
                    ->searchable(),

                Columns\TextColumn::make('thai_score')
                    ->label('ภาษาไทย')
                    ->numeric()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state))
                    ->sortable(),

                Columns\TextColumn::make('math_score')
                    ->label('คณิตศาสตร์')
                    ->numeric()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state))
                    ->sortable(),

                Columns\TextColumn::make('science_score')
                    ->label('วิทยาศาสตร์')
                    ->numeric()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state))
                    ->sortable(),

                Columns\TextColumn::make('english_score')
                    ->label('อังกฤษ')
                    ->numeric()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('โรงเรียน')
                    ->options(SchoolModel::pluck('school_name_th', 'school_id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('education_year')
                    ->label('ปีการศึกษา')
                    ->default(date('Y') + 543)
                    ->options(fn() => self::getEducationYears()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        return $user->role === UserRole::SUPERADMIN ||
                            ($user->role === UserRole::SCHOOLADMIN && $user->school_id === $record->school_id);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        return $user->role === UserRole::SUPERADMIN ||
                            ($user->role === UserRole::SCHOOLADMIN && $user->school_id === $record->school_id);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN),
                ]),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100]);
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
            'index' => Pages\ListBasicSubjectAssessments::route('/'),
            'create' => Pages\CreateBasicSubjectAssessment::route('/create'),
            'view' => Pages\ViewBasicSubjectAssessment::route('/{record}'),
            'edit' => Pages\EditBasicSubjectAssessment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ถ้าเป็น School Admin ให้แสดงเฉพาะข้อมูลของโรงเรียนตนเอง
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Row 1: ข้อมูลทั่วไป & การประเมิน
                InfoComponents\Grid::make(4)
                    ->schema([
                        InfoComponents\Section::make('ข้อมูลทั่วไป')
                            ->schema([
                                InfoComponents\TextEntry::make('id')
                                    ->label('รหัสประเมิน')
                                    ->icon('heroicon-o-identification')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->copyable()
                                    ->copyMessage('Copied!')
                                    ->copyMessageDuration(1500),
                                InfoComponents\TextEntry::make('school.school_name_th')
                                    ->label('โรงเรียน')
                                    ->icon('heroicon-o-academic-cap')
                                    ->color('success'),
                                InfoComponents\TextEntry::make('education_year')
                                    ->label('ปีการศึกษา')
                                    ->icon('heroicon-o-calendar')
                                    ->color('info'),
                            ])
                            ->columns(3)
                            ->columnSpan(3)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),

                        InfoComponents\Section::make('ข้อมูลวันที่')
                            ->schema([
                                InfoComponents\TextEntry::make('created_at')
                                    ->label('สร้างเมื่อ')
                                    ->icon('heroicon-o-clock')
                                    ->dateTime('d/m/Y H:i')
                                    ->color('gray'),
                                InfoComponents\TextEntry::make('updated_at')
                                    ->label('แก้ไขล่าสุด')
                                    ->icon('heroicon-o-pencil')
                                    ->dateTime('d/m/Y H:i')
                                    ->color('gray'),
                            ])
                            ->columns(1)
                            ->columnSpan(1)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),
                    ]),

                // Row 2: คะแนนวิชาพื้นฐาน & กราฟ
                InfoComponents\Grid::make(4)
                    ->schema([

                        InfoComponents\Section::make('คะแนนวิชาพื้นฐาน')
                            ->description('คะแนนเต็ม 100')
                            ->schema([
                                InfoComponents\TextEntry::make('thai_score')
                                    ->label('ภาษาไทย')
                                    ->icon('heroicon-o-book-open')
                                    ->badge()
                                    ->color(fn($state) => static::getScoreColor($state))
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),
                                InfoComponents\TextEntry::make('math_score')
                                    ->label('คณิตศาสตร์')
                                    ->icon('heroicon-o-variable')
                                    ->badge()
                                    ->color(fn($state) => static::getScoreColor($state))
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),
                                InfoComponents\TextEntry::make('science_score')
                                    ->label('วิทยาศาสตร์')
                                    ->icon('heroicon-o-beaker')
                                    ->badge()
                                    ->color(fn($state) => static::getScoreColor($state))
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),
                                InfoComponents\TextEntry::make('english_score')
                                    ->label('ภาษาอังกฤษ')
                                    ->icon('heroicon-o-language')
                                    ->badge()
                                    ->color(fn($state) => static::getScoreColor($state))
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('overall_summary')
                                    ->label('คะแนนเฉลี่ย')
                                    ->state(function ($record) {
                                        $scores = array_filter([
                                            $record->thai_score ?? 0,
                                            $record->math_score ?? 0,
                                            $record->science_score ?? 0,
                                            $record->english_score ?? 0,
                                        ]);
                                        $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                                        return number_format($average, 2) . ' / 100';
                                    })
                                    ->color(fn($state) => static::getAverageScoreColor($state))
                                    ->size(TextEntrySize::Large)
                                    ->weight('bold'),

                                InfoComponents\TextEntry::make('achievement_level')
                                    ->label('ระดับผลสัมฤทธิ์')
                                    ->state(function ($record) {
                                        $scores = array_filter([
                                            $record->thai_score ?? 0,
                                            $record->math_score ?? 0,
                                            $record->science_score ?? 0,
                                            $record->english_score ?? 0,
                                        ]);
                                        $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

                                        return match (true) {
                                            $average >= 80 => 'ดีเยี่ยม',
                                            $average >= 70 => 'ดีมาก',
                                            $average >= 60 => 'ดี',
                                            $average >= 50 => 'ผ่าน',
                                            default => 'ควรปรับปรุง'
                                        };
                                    })
                                    ->icon('heroicon-o-trophy')
                                    ->color(fn($state) => match ($state) {
                                        'ดีเยี่ยม' => 'success',
                                        'ดีมาก' => 'success',
                                        'ดี' => 'info',
                                        'ผ่าน' => 'warning',
                                        default => 'danger'
                                    })
                                    ->weight('bold')
                                    ->size(TextEntrySize::Large)
                                    ->badge(),
                            ])
                            ->columns(2)
                            ->columnSpan(2)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),

                        InfoComponents\View::make('dashboard::widgets.basicsubjectassessment.infolist-radar-chart')
                            ->columns(2)
                            ->columnSpan(2)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),
                    ]),
            ]);
    }

    protected static function getScoreColor($score): string
    {
        if ($score === null)
            return 'gray';

        if ($score >= 80) {
            return 'success';
        } elseif ($score >= 70) {
            return 'info';
        } elseif ($score >= 50) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected static function getAverageScoreColor($text): string
    {
        // แยกตัวเลขจากข้อความ
        preg_match('/[\d\.]+/', $text, $matches);
        $average = $matches[0] ?? 0;

        return self::getScoreColor((float) $average);
    }
}