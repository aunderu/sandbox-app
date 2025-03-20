<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Enums\UserRole;
use Filament\Forms\Components as Components;
use Filament\Forms\Form;
use Filament\Infolists\Components as InfoComponents;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns as Columns;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Pages;
use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Widgets\CoreCompetencyRadarChart;
use Modules\Sandbox\Models\CoreCompetencyAssessmentModel;
use Modules\Sandbox\Models\SchoolModel;

class CoreCompetencyAssessmentResource extends Resource
{
    protected static ?string $model = CoreCompetencyAssessmentModel::class;

    protected static ?string $navigationIcon = 'heroicon-s-chart-bar';

    protected static ?string $modelLabel = 'ผลการประเมินสมรรถนะหลัก';

    protected static ?string $navigationGroup = "การประเมินผล";

    protected static ?int $navigationSort = 4;

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
                    ->label('รหัสประเมินสมรรถนะ')
                    ->disabled()
                    ->hiddenOn('create')
                    ->afterStateHydrated(function ($component, $state, $record) {
                        // ถ้ามีการโหลดข้อมูลเดิม จะแสดงค่า ID
                        if ($record && !$state) {
                            $component->state($record->id);
                        }
                    }),

                Components\Section::make('คะแนนสมรรถนะ')
                    ->description('กรอกคะแนนสมรรถนะหลักในแต่ละด้าน (คะแนนเต็ม 100)')
                    ->schema([
                        Components\TextInput::make('self_management_score')
                            ->label('คะแนนสมรรถนะการจัดการตนเอง')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-user'),

                        Components\TextInput::make('teamwork_score')
                            ->label('คะแนนสมรรถนะการรวมพลังทำงานเป็นทีม')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-user-group'),

                        Components\TextInput::make('high_thinking_score')
                            ->label('คะแนนสมรรถนะการคิดขั้นสูง')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-light-bulb'),

                        Components\TextInput::make('communication_score')
                            ->label('คะแนนสมรรถนะการสื่อสาร')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-chat-bubble-left-right'),

                        Components\TextInput::make('active_citizen_score')
                            ->label('คะแนนสมรรถนะการเป็นพลเมืองที่เข้มแข็ง')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-flag'),

                        Components\TextInput::make('sustainable_coexistence_score')
                            ->label('คะแนนสมรรถนะการอยู่ร่วมกับธรรมชาติอย่างยั่งยืน')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/ 100')
                            ->hint('กรอกคะแนนระหว่าง 0-100')
                            ->prefixIcon('heroicon-o-globe-alt'),
                    ])->columns(2),
            ]);
    }

    // เพิ่ม infolist method เพื่อแสดงข้อมูลในหน้า view
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
                                    ->label('รหัสประเมินสมรรถนะ')
                                    ->icon('heroicon-o-identification')
                                    ->weight('bold')
                                    ->copyable()
                                    ->copyMessage('คัดลอกรหัสแล้ว'),

                                InfoComponents\TextEntry::make('school.school_name_th')
                                    ->label('โรงเรียน')
                                    ->icon('heroicon-o-academic-cap'),

                                InfoComponents\TextEntry::make('education_year')
                                    ->label('ปีการศึกษา')
                                    ->icon('heroicon-o-calendar'),
                            ])
                            ->columns(3)
                            ->columnSpan(3)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),

                        InfoComponents\Section::make('ข้อมูลการประเมิน')
                            ->schema([
                                InfoComponents\TextEntry::make('created_at')
                                    ->label('สร้างเมื่อ')
                                    ->icon('heroicon-o-clock')
                                    ->dateTime('d/m/Y H:i'),

                                InfoComponents\TextEntry::make('updated_at')
                                    ->label('แก้ไขล่าสุด')
                                    ->icon('heroicon-o-pencil')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(1)
                            ->columnSpan(1)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),
                    ]),

                // Row 2: คะแนนสมรรถนะหลัก & กราฟ
                InfoComponents\Grid::make(4)
                    ->schema([
                        // ส่วนคะแนน
                        InfoComponents\Section::make('คะแนนสมรรถนะหลัก')
                            ->description('คะแนนสมรรถนะหลัก 6 ด้านตามหลักสูตร (คะแนนเต็ม 100)')
                            ->schema([
                                InfoComponents\TextEntry::make('self_management_score')
                                    ->label('การจัดการตนเอง')
                                    ->icon('heroicon-o-user')
                                    ->color(fn($state): string => static::getScoreColor($state))
                                    ->badge()
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('teamwork_score')
                                    ->label('การรวมพลังทำงานเป็นทีม')
                                    ->icon('heroicon-o-user-group')
                                    ->color(fn($state): string => static::getScoreColor($state))
                                    ->badge()
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('high_thinking_score')
                                    ->label('การคิดขั้นสูง')
                                    ->icon('heroicon-o-light-bulb')
                                    ->color(fn($state): string => static::getScoreColor($state))
                                    ->badge()
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('communication_score')
                                    ->label('การสื่อสาร')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->color(fn($state): string => static::getScoreColor($state))
                                    ->badge()
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('active_citizen_score')
                                    ->label('การเป็นพลเมืองที่เข้มแข็ง')
                                    ->icon('heroicon-o-flag')
                                    ->color(fn($state): string => static::getScoreColor($state))
                                    ->badge()
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('sustainable_coexistence_score')
                                    ->label('การอยู่ร่วมกับธรรมชาติอย่างยั่งยืน')
                                    ->icon('heroicon-o-globe-alt')
                                    ->color(fn($state): string => static::getScoreColor($state))
                                    ->badge()
                                    ->formatStateUsing(fn($state) => "{$state} / 100"),

                                InfoComponents\TextEntry::make('overall_summary')
                                    ->label('คะแนนเฉลี่ย')
                                    ->state(function ($record) {
                                        $scores = [
                                            $record->self_management_score ?? 0,
                                            $record->teamwork_score ?? 0,
                                            $record->high_thinking_score ?? 0,
                                            $record->communication_score ?? 0,
                                            $record->active_citizen_score ?? 0,
                                            $record->sustainable_coexistence_score ?? 0,
                                        ];

                                        $average = array_sum($scores) / count(array_filter($scores, fn($s) => $s !== null));
                                        $formattedAverage = number_format($average, 2);

                                        return "{$formattedAverage} / 100";
                                    })
                                    ->color(fn($state) => static::getAverageScoreColor($state))
                                    ->size(TextEntrySize::Large)
                                    ->weight('bold'),

                                InfoComponents\TextEntry::make('achievement_level')
                                    ->label('ระดับผลสัมฤทธิ์')
                                    ->state(function ($record) {
                                        $scores = [
                                            $record->self_management_score ?? 0,
                                            $record->teamwork_score ?? 0,
                                            $record->high_thinking_score ?? 0,
                                            $record->communication_score ?? 0,
                                            $record->active_citizen_score ?? 0,
                                            $record->sustainable_coexistence_score ?? 0,
                                        ];

                                        $average = array_sum($scores) / count(array_filter($scores, fn($s) => $s !== null));

                                        if ($average >= 80) {
                                            return 'ดีเยี่ยม';
                                        } elseif ($average >= 70) {
                                            return 'ดีมาก';
                                        } elseif ($average >= 60) {
                                            return 'ดี';
                                        } elseif ($average >= 50) {
                                            return 'ผ่าน';
                                        } else {
                                            return 'ควรปรับปรุง';
                                        }
                                    })
                                    ->icon('heroicon-o-trophy')
                                    ->color(fn($state): string => match ($state) {
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

                        // กราฟแสดงคะแนน
                        InfoComponents\View::make('dashboard::widgets.corecompetencyassessment.infolist-competency-chart')
                            ->columns(2)
                            ->columnSpan(2)
                            ->extraAttributes([
                                'class' => 'h-full',
                            ]),
                    ]),
            ]);
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

                Columns\TextColumn::make('self_management_score')
                    ->label('การจัดการตนเอง')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state)),

                Columns\TextColumn::make('teamwork_score')
                    ->label('ทำงานเป็นทีม')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state)),

                Columns\TextColumn::make('high_thinking_score')
                    ->label('คิดขั้นสูง')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state)),

                Columns\TextColumn::make('communication_score')
                    ->label('การสื่อสาร')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state)),

                Columns\TextColumn::make('active_citizen_score')
                    ->label('พลเมืองเข้มแข็ง')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state)),

                Columns\TextColumn::make('sustainable_coexistence_score')
                    ->label('อยู่กับธรรมชาติอย่างยั่งยืน')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => static::getScoreColor($state)),
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

    public static function hasDuplicate($schoolId, $educationYear, $excludeId = null): bool
    {
        $query = CoreCompetencyAssessmentModel::where('school_id', $schoolId)
            ->where('education_year', $educationYear);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public static function generateCompetencyId($schoolId, $educationYear = null): string
    {
        $prefix = "COMP{$schoolId}";
        $year = $educationYear ?: (date('Y') + 543);
        $randomString = strtoupper(substr(md5(uniqid()), 0, 6));
        return "{$prefix}{$year}{$randomString}";
    }

    // เพิ่มเมทอด helper สำหรับการแสดงสี
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

    // เพิ่มเมทอดสำหรับการหาสีคะแนนเฉลี่ย
    protected static function getAverageScoreColor($text): string
    {
        // แยกตัวเลขจากข้อความ
        preg_match('/[\d\.]+/', $text, $matches);
        $average = $matches[0] ?? 0;

        return self::getScoreColor((float) $average);
    }

    public static function getModelEventHandlers(): array
    {
        return [
            'creating' => [static::class, 'handleCreating'],
        ];
    }

    public static function handleCreating(CoreCompetencyAssessmentModel $record): void
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
            'index' => Pages\ListCoreCompetencyAssessments::route('/'),
            'create' => Pages\CreateCoreCompetencyAssessment::route('/create'),
            'view' => Pages\ViewCoreCompetencyAssessment::route('/{record}'),
            'edit' => Pages\EditCoreCompetencyAssessment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ถ้าเป็น SchoolAdmin ให้แสดงเฉพาะข้อมูลของโรงเรียนตนเอง
        if (Auth::check() && Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }
}
