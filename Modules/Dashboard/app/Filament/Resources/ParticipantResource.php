<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Enums\UserRole;
use Illuminate\Support\HtmlString;
use Modules\Dashboard\Filament\Resources\ParticipantResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Sandbox\Models\ParticipantModel;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components as InfoComponents;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class ParticipantResource extends Resource
{
    protected static ?string $model = ParticipantModel::class;

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?string $navigationGroup = "โรงเรียน";

    protected static ?string $modelLabel = 'หน่วยงานที่เข้ามามีส่วนร่วม';

    protected static ?int $navigationSort = 3;

    // protected static ?string $recordTitleAttribute = 'participant_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('participant_id')
                    ->label('รหัสผู้เข้าร่วม')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn($record) => $record !== null),

                Forms\Components\Section::make('ข้อมูลหลัก')
                    ->schema([
                        Forms\Components\Select::make('sector_type')
                            ->options(ParticipantModel::SECTOR_TYPES)
                            ->required()
                            ->default('1')
                            ->live()
                            ->label('ประเภทหน่วยงาน'),

                        Forms\Components\Select::make('participant_type_code')
                            ->options(function (Forms\Get $get) {
                                $sectorType = $get('sector_type') ?? '1';

                                // กรองประเภทตามภาครัฐหรือเอกชน
                                if ($sectorType === '1') {
                                    // ภาครัฐ
                                    return [
                                        '01' => 'บุคคล',
                                        '02' => 'หน่วยงานรัฐ/รัฐวิสาหกิจ',
                                        '06' => 'องค์กรต่างประเทศ',
                                    ];
                                } else {
                                    // ภาคเอกชน
                                    return [
                                        '01' => 'บุคคล',
                                        '03' => 'บริษัทเอกชน',
                                        '04' => 'มูลนิธิ',
                                        '05' => 'สมาคม',
                                        '06' => 'องค์กรต่างประเทศ',
                                    ];
                                }
                            })
                            ->required()
                            ->default(fn(Forms\Get $get) => $get('sector_type') === '1' ? '01' : '03')
                            ->label('ประเภทผู้เข้ามามีส่วนร่วม'),

                        // ปรับให้ schooladmin เห็นเฉพาะโรงเรียนตนเอง
                        Forms\Components\Select::make('cooperation_school_id')->label('สถานศึกษาที่เข้าไปมีส่วนร่วม')
                            ->disabled(fn() => Auth::user()->role === UserRole::SCHOOLADMIN)
                            ->relationship('school', 'school_name_th')
                            ->prefix('โรงเรียน')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => Auth::user()->school_id)
                            ->searchPrompt('เพิ่มชื่อโรงเรียนเพื่อค้นหา...')
                            ->helperText(new HtmlString('กรณีที่ไม่มีชื่อโรงเรียนของท่านแสดง กรุณาติดต่อ<i><strong>ผู้ดูแลระบบ</strong></i>')),

                        Forms\Components\TextInput::make('participant_name')
                            ->required()
                            ->maxLength(255)
                            ->label('ชื่อภาครัฐหรือเอกชนที่เข้ามามีส่วนร่วม'),

                        Forms\Components\Select::make('area_code')
                            ->options(ParticipantModel::INNOVATION_AREAS)
                            ->required()
                            ->default('95')
                            ->label('พื้นที่นวัตกรรมการศึกษา'),
                    ])->columns(2),

                Forms\Components\Section::make('ข้อมูลการติดต่อ')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->required()
                            ->maxLength(255)
                            ->label('ชื่อผู้ติดต่อ'),

                        Forms\Components\TextInput::make('contact_organization_position')
                            ->maxLength(255)
                            ->label('ตำแหน่งในองค์กร'),

                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('เบอร์โทรศัพท์'),

                        Forms\Components\TextInput::make('contact_mobile_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('เบอร์โทรศัพท์เคลื่อนที่'),

                        Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255)
                            ->label('อีเมล'),
                    ])->columns(2),

                Forms\Components\Section::make('ข้อมูลการมีส่วนร่วม')
                    ->schema([
                        Forms\Components\DatePicker::make('cooperation_start_date')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->label('วันที่เริ่มมีส่วนร่วม'),

                        Forms\Components\DatePicker::make('cooperation_end_date')
                            ->displayFormat('d/m/Y')
                            ->label('วันที่สิ้นสุดการมีส่วนร่วม'),

                        Forms\Components\Select::make('cooperation_status_code')
                            ->options(ParticipantModel::COOPERATION_STATUS)
                            ->default('01')
                            ->required()
                            ->label('สถานะการมีส่วนร่วม'),

                        Forms\Components\Select::make('cooperation_level_code')
                            ->options(ParticipantModel::COOPERATION_LEVELS)
                            ->required()
                            ->label('ระดับการมีส่วนร่วม'),

                        Forms\Components\Textarea::make('cooperation_activity')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->label('กิจกรรมที่มีส่วนร่วม'),
                    ])->columns(2),

                Forms\Components\Section::make('เอกสารแนบ')
                    ->schema([
                        Forms\Components\FileUpload::make('cooperation_attachment_url')
                            ->multiple()
                            ->directory('participants')
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->label('ไฟล์เอกสารแนบ'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('participant_id')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('รหัส'),

                Tables\Columns\TextColumn::make('sector_type_name')
                    ->badge()
                    ->colors([
                        'primary' => fn($state): bool => $state === 'ภาครัฐ',
                        'warning' => fn($state): bool => $state === 'ภาคเอกชน',
                    ])
                    ->toggleable()
                    ->label('ประเภท'),

                Tables\Columns\TextColumn::make('participant_name')
                    ->searchable()
                    ->toggleable()
                    ->limit(30)
                    ->label('ชื่อผู้เข้าร่วม'),

                Tables\Columns\TextColumn::make('participant_type_name')
                    ->toggleable()
                    ->label('ประเภทผู้เข้าร่วม'),

                Tables\Columns\TextColumn::make('school.school_name_th')
                    ->searchable()
                    ->limit(30)
                    ->toggleable()
                    ->label('สถานศึกษา'),

                Tables\Columns\TextColumn::make('innovation_area_name')
                    ->toggleable()
                    ->label('พื้นที่นวัตกรรม'),

                Tables\Columns\TextColumn::make('formatted_start_date')
                    ->sortable(query: fn(Builder $query): Builder => $query->orderBy('cooperation_start_date'))
                    ->toggleable()
                    ->label('วันที่เริ่ม'),

                Tables\Columns\TextColumn::make('formatted_end_date')
                    ->sortable(query: fn(Builder $query): Builder => $query->orderBy('cooperation_end_date'))
                    ->toggleable()
                    ->label('วันที่สิ้นสุด'),

                Tables\Columns\TextColumn::make('cooperation_status_name')
                    ->badge()
                    ->colors([
                        'success' => fn($state): bool => $state === 'ยังมีส่วนร่วม',
                        'danger' => fn($state): bool => $state === 'สิ้นสุดการมีส่วนร่วม',
                        'gray' => fn($state): bool => $state === 'ไม่มีการเข้ามามีส่วนร่วม',
                    ])
                    ->toggleable()
                    ->label('สถานะ'),

                Tables\Columns\TextColumn::make('cooperation_level_name')
                    ->label('ระดับการมีส่วนร่วม')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('contact_name')
                    ->searchable()
                    ->label('ผู้ติดต่อ')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('วันที่สร้าง'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sector_type')
                    ->options(ParticipantModel::SECTOR_TYPES)
                    ->label('ประเภทภาค')
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('participant_id', 'like', $data['value'] . '%');
                    }),

                Tables\Filters\SelectFilter::make('area_code')
                    ->options(ParticipantModel::INNOVATION_AREAS)
                    ->label('พื้นที่นวัตกรรมการศึกษา')
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('participant_id', 'like', '_' . $data['value'] . '%');
                    }),

                Tables\Filters\SelectFilter::make('participant_type_code')
                    ->options(ParticipantModel::PARTICIPANT_TYPES)
                    ->multiple()
                    ->label('ประเภทผู้เข้ามามีส่วนร่วม'),

                Tables\Filters\SelectFilter::make('cooperation_school_id')
                    ->relationship('school', 'school_name_th')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('สถานศึกษา'),

                Tables\Filters\SelectFilter::make('cooperation_status_code')
                    ->options(ParticipantModel::COOPERATION_STATUS)
                    ->multiple()
                    ->label('สถานะการมีส่วนร่วม'),

                Tables\Filters\SelectFilter::make('cooperation_level_code')
                    ->options(ParticipantModel::COOPERATION_LEVELS)
                    ->multiple()
                    ->label('ระดับการมีส่วนร่วม'),

                Tables\Filters\Filter::make('cooperation_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('จากวันที่'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('ถึงวันที่'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('cooperation_start_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('cooperation_start_date', '<=', $date),
                            );
                    })
                    ->label('ช่วงวันที่เริ่มมีส่วนร่วม'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Row 1: ข้อมูลหลักและข้อมูลเบื้องต้น
                InfoComponents\Grid::make(4)
                    ->schema([
                        InfoComponents\Section::make('ข้อมูลผู้เข้าร่วม')
                            ->icon('heroicon-o-user')
                            ->schema([
                                InfoComponents\TextEntry::make('participant_id')
                                    ->label('รหัสผู้เข้าร่วม')
                                    ->icon('heroicon-o-identification')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->copyable()
                                    ->copyMessage('คัดลอกรหัสแล้ว!')
                                    ->copyMessageDuration(1500),

                                InfoComponents\TextEntry::make('participant_name')
                                    ->label('ชื่อผู้เข้าร่วม')
                                    ->icon('heroicon-o-user')
                                    ->color('success'),

                                InfoComponents\TextEntry::make('sector_type_name')
                                    ->label('ประเภทหน่วยงาน')
                                    ->icon('heroicon-o-building-office')
                                    ->badge()
                                    ->color(fn($state) => $state === 'ภาครัฐ' ? 'primary' : 'warning'),

                                InfoComponents\TextEntry::make('participant_type_name')
                                    ->label('ประเภทผู้เข้าร่วม')
                                    ->icon('heroicon-o-user-group')
                                    ->badge()
                                    ->color('info'),
                            ])
                            ->columns(2)
                            ->columnSpan(3),

                        InfoComponents\Section::make('ข้อมูลการบันทึก')
                            ->icon('heroicon-o-pencil')
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
                            ->columnSpan(1),
                    ]),

                // Row 2: ข้อมูลการมีส่วนร่วมและสถานศึกษา
                InfoComponents\Grid::make(4)
                    ->schema([
                        InfoComponents\Section::make('ข้อมูลสถานศึกษา')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                InfoComponents\TextEntry::make('school.school_name_th')
                                    ->label('สถานศึกษาที่เข้าไปมีส่วนร่วม')
                                    ->icon('heroicon-o-academic-cap')
                                    ->color('primary'),

                                InfoComponents\TextEntry::make('innovation_area_name')
                                    ->label('พื้นที่นวัตกรรมการศึกษา')
                                    ->icon('heroicon-o-map')
                                    ->color('info'),

                                InfoComponents\TextEntry::make('user.name')
                                    ->label('ผู้บันทึกข้อมูล')
                                    ->icon('heroicon-o-user-circle')
                                    ->color('gray'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),

                        InfoComponents\Section::make('ข้อมูลการมีส่วนร่วม')
                            ->schema([
                                InfoComponents\TextEntry::make('formatted_start_date')
                                    ->label('วันที่เริ่มมีส่วนร่วม')
                                    ->icon('heroicon-o-calendar')
                                    ->color('success'),

                                InfoComponents\TextEntry::make('formatted_end_date')
                                    ->label('วันที่สิ้นสุด')
                                    ->icon('heroicon-o-calendar')
                                    ->color('warning')
                                    ->default('ไม่กำหนด'),

                                InfoComponents\TextEntry::make('cooperation_status_name')
                                    ->label('สถานะการมีส่วนร่วม')
                                    ->icon('heroicon-o-check-circle')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'ยังมีส่วนร่วม' => 'success',
                                        'สิ้นสุดการมีส่วนร่วม' => 'danger',
                                        default => 'gray',
                                    }),

                                InfoComponents\TextEntry::make('cooperation_level_name')
                                    ->label('ระดับการมีส่วนร่วม')
                                    ->icon('heroicon-o-chart-bar')
                                    ->badge()
                                    ->color('primary'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                    ]),

                // Row 3: ข้อมูลการติดต่อและกิจกรรม
                InfoComponents\Grid::make(3)
                    ->schema([
                        InfoComponents\Section::make('ข้อมูลผู้ติดต่อ')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                InfoComponents\TextEntry::make('contact_name')
                                    ->label('ชื่อผู้ติดต่อ')
                                    ->icon('heroicon-o-user')
                                    ->color('primary'),

                                InfoComponents\TextEntry::make('contact_organization_position')
                                    ->label('ตำแหน่งในองค์กร')
                                    ->icon('heroicon-o-briefcase')
                                    ->color('gray')
                                    ->default('ไม่ระบุ'),

                                InfoComponents\TextEntry::make('contact_phone')
                                    ->label('เบอร์โทรศัพท์')
                                    ->icon('heroicon-o-phone')
                                    ->default('ไม่ระบุ'),

                                InfoComponents\TextEntry::make('contact_mobile_phone')
                                    ->label('เบอร์โทรศัพท์มือถือ')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->default('ไม่ระบุ'),

                                InfoComponents\TextEntry::make('contact_email')
                                    ->label('อีเมล')
                                    ->icon('heroicon-o-envelope')
                                    ->default('ไม่ระบุ'),
                            ])
                            ->columns(2)
                            ->columnSpan(1),

                        InfoComponents\Section::make('กิจกรรมที่มีส่วนร่วม')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                InfoComponents\TextEntry::make('cooperation_activity')
                                    ->label('รายละเอียดกิจกรรม')
                                    ->markdown()
                                    ->columnSpanFull()
                                    ->default('ไม่มีรายละเอียดกิจกรรม'),
                            ])
                            ->columnSpan(2),
                    ]),

                // Row 4: เอกสารแนบ
                InfoComponents\Section::make('เอกสารแนบ')
                    ->icon('heroicon-o-paper-clip')
                    ->collapsed(false)
                    ->collapsible()
                    ->schema([
                        InfoComponents\TextEntry::make('cooperation_attachment_urls')
                            ->label(false)
                            ->visible(fn($record) => !empty($record->cooperation_attachment_url))
                            ->state(function ($record) {
                                $attachments = is_array($record->cooperation_attachment_url) ? $record->cooperation_attachment_url : [];
                                $links = [];

                                foreach ($attachments as $attachment) {
                                    $url = asset('storage/' . $attachment);
                                    $fileName = basename($attachment);
                                    $links[] = "[{$fileName}]({$url})";
                                }

                                return implode("\n\n", $links);
                            })
                            ->markdown(),

                        InfoComponents\TextEntry::make('no_attachments')
                            ->label(false)
                            ->visible(fn($record) => empty($record->cooperation_attachment_url))
                            ->state('ไม่มีเอกสารแนบสำหรับรายการนี้')
                            ->color('gray')
                            ->icon('heroicon-o-document'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ถ้าเป็น SchoolAdmin ให้แสดงเฉพาะข้อมูลของโรงเรียนตนเอง
        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('cooperation_school_id', Auth::user()->school_id);
        }

        return $query;
    }

    public static function generateParticipantId(string $sectorType = '1', string $areaCode = '95'): string
    {
        // ตรวจสอบความถูกต้องของ sectorType
        if (!in_array($sectorType, array_keys(ParticipantModel::SECTOR_TYPES))) {
            $sectorType = '1'; // ค่าเริ่มต้นเป็นภาครัฐ
        }

        // ตรวจสอบความถูกต้องของ areaCode
        if (!in_array($areaCode, array_keys(ParticipantModel::INNOVATION_AREAS))) {
            $areaCode = '95'; // ค่าเริ่มต้นเป็นยะลา
        }

        // ปี พ.ศ. 2 หลักสุดท้าย (ปีปัจจุบัน + 543)
        $thaiYear = (int) Carbon::now()->format('Y') + 543;
        $yearCode = substr((string) $thaiYear, -2);

        // สร้างรหัสนำหน้า (prefix)
        $prefix = $sectorType . $areaCode . $yearCode;

        // ค้นหารหัสล่าสุดในปีนี้
        $latestParticipant = ParticipantModel::where('participant_id', 'like', $prefix . '%')
            ->orderBy('participant_id', 'desc')
            ->first();

        // กำหนดลำดับถัดไป
        if ($latestParticipant) {
            // ถ้ามีรหัสในปีนี้แล้ว ให้เพิ่มลำดับ
            $sequence = (int) substr($latestParticipant->participant_id, 5) + 1;
        } else {
            // ถ้ายังไม่มีรหัสในปีนี้ ให้เริ่มที่ 1
            $sequence = 1;
        }

        // สร้างรหัสที่สมบูรณ์
        $participantId = $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);

        return $participantId;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParticipants::route('/'),
            'create' => Pages\CreateParticipant::route('/create'),
            'view' => Pages\ViewParticipant::route('/{record}'),
            'edit' => Pages\EditParticipant::route('/{record}/edit'),
        ];
    }
}
