<?php

namespace Modules\Dashboard\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms\Components as Component;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

use Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;
use Modules\Sandbox\Models\InnovationsModel;

class InnovationsResource extends Resource
{
    protected static ?string $model = InnovationsModel::class;

    protected static ?string $navigationIcon = 'heroicon-s-light-bulb';

    protected static ?string $modelLabel = 'ตารางนวัตกรรม';

    protected static ?string $navigationGroup = "นวัตกรรม";

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'จำนวนนวัตกรรม';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Component\Section::make('ข้อมูลนวัตกรรม')
                    ->schema([
                        Component\Select::make('school_id')->label(__('สถานศึกษา'))
                            ->disabled(fn() => Auth::user()->isSchoolAdmin())
                            ->relationship('school', 'school_name_th')
                            ->prefix('โรงเรียน')
                            ->searchable()
                            ->columnSpanFull()
                            ->required()
                            ->default(fn() => Auth::user()->school_id)
                            ->searchPrompt('เพิ่มชื่อโรงเรียนเพื่อค้นหา...')
                            ->helperText(new HtmlString('กรณีที่ไม่มีชื่อโรงเรียนของท่านแสดง กรุณาติดต่อ<i><strong>ผู้ดูแลระบบ</strong></i>')),
                        Component\Select::make('semester')->label('ภาคเรียน')
                            ->options([
                                0 => 'ตลอดปีการศึกษา',
                                1 => 'ภาคเรียนที่ 1',
                                2 => 'ภาคเรียนที่ 2',
                            ])
                            ->default(0)
                            ->selectablePlaceholder(false)
                            ->required(),
                        Component\TextInput::make('inno_name')->label(__('ชื่อนวัตกรรม'))
                            ->minLength(3)->maxLength(255)
                            ->required(),
                        Component\Select::make('inno_type_id')->label('ประเภทนวัตกรรม')
                            ->relationship('innovationType', 'name')
                            ->required(),
                        Component\RichEditor::make('inno_description')->label(__('รายละเอียด'))
                            ->minLength(3)->maxLength(65535)
                            ->columnSpanFull()
                            ->disableToolbarButtons([
                                'attachFiles',
                            ])
                            ->required(),
                        Component\TagsInput::make('tags')->label(__('แท็ก'))
                            ->nullable()
                            ->columnSpanFull()
                            ->suggestions([
                                'นวัตกรรม',
                                'สิ่งประดิษฐ์',
                                'สื่อการเรียนรู้',
                                'พื้นที่นวัตกรรม',
                                'สร้างสรรค์',
                            ]),
                        Component\Hidden::make('user_id')
                            ->default(fn() => Auth::id())
                            ->required(),
                        Component\Hidden::make('education_year')
                            ->default(fn() => Carbon::now()->year + 543)
                            ->required(),
                    ])->columnSpan(2)->columns(2),

                Component\Group::make()->schema([
                    Component\Section::make('วีดีโอและไฟล์แนบ')
                        ->schema([
                            Component\TextInput::make('video_url')->label(__('ลิงค์วีดีโอ'))
                                ->nullable()
                                ->placeholder('https://www.youtube.com/watch?v=...')
                                ->suffixIcon('heroicon-m-globe-alt')
                                ->url(),
                            Component\FileUpload::make('attachments')
                                ->label(__('ไฟล์แนบนวัตกรรม'))
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.ms-powerpoint',
                                    'application/vnd.ms-excel',
                                ])
                                ->disk('public')
                                ->directory('inno-attachments')
                                ->visibility('public')
                                ->storeFileNamesIn('original_filename')
                                ->helperText(new HtmlString('รองรับประเภทไฟล์ <i><strong>word, excel, powerpoint</strong></i> และ <i><strong>pdf</strong></i>'))
                                ->deleteUploadedFileUsing(function ($file) {
                                    // ลบไฟล์เก่าก่อนบันทึกไฟล์ใหม่
                                    Storage::disk('public')->delete($file);
                                }),
                        ])->columnSpan(1),
                ]),


            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('school.school_name_th')->label('สถานศึกษา')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => 'โรงเรียน ' . $state),
                TextColumn::make('semester')->label('ภาคเรียน')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            0 => 'ตลอดปีการศึกษา',
                            1 => 'ภาคเรียนที่ 1',
                            2 => 'ภาคเรียนที่ 2',
                            default => 'ไม่ทราบ',
                        };
                    })
                    ->toggleable(),
                TextColumn::make('innovationType.name')->label('ประเภทนวัตกรรม')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('inno_name')->label('ชื่อนวัตกรรม')
                    ->sortable()
                    ->color('primary')
                    ->searchable()
                    ->description(fn($record) => \Illuminate\Support\Str::limit(strip_tags($record->inno_description), 50))
                    ->toggleable(),
                TextColumn::make('tags')->label('แท็ก')
                    // ->alignCenter()
                    ->searchable()
                    ->icon('heroicon-m-hashtag')
                    ->iconColor('primary')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('video_url')->label('ลิงค์วีดีโอ')
                    ->limit(length: 30)
                    ->toggleable(),
                TextColumn::make('original_filename')
                    ->label('ไฟล์แนบนวัตกรรม')
                    ->weight(FontWeight::ExtraBold)
                    ->color('info')
                    ->limit(length: 30)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    // ->badge()
                    ->toggleable()
                    ->url(fn($record) => $record->attachments ? asset('storage/' . $record->attachments) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')->label('วันที่สร้าง')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-calendar')
                    ->iconColor('primary')
                    ->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('สถานศึกษา')
                    ->multiple()
                    ->searchable()
                    ->relationship('school', 'school_name_th')
                    ->preload(),
                Filter::make('my_innovations')
                    ->label('แสดงเฉพาะนวัตกรรมของฉัน')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('user_id', Auth::id()))
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInnovations::route('/'),
            'create' => Pages\CreateInnovations::route('/create'),
            'edit' => Pages\EditInnovations::route('/{record}/edit'),
        ];
    }

    /**
     * Generate a new innovation ID.
     */
    public static function generateInnovationId($schoolId)
    {
        $year = Carbon::now()->format('y') + 43;
        $latestInnovation = InnovationsModel::where('innovationID', 'like', $schoolId . $year . '%')
            ->orderBy('innovationID', 'desc')
            ->first();

        if ($latestInnovation) {
            $lastSequence = (int) substr($latestInnovation->innovationID, -4);
            $newSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '0001';
        }

        return $schoolId . $year . $newSequence;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // ถ้าเป็น SchoolAdmin ให้แสดงเฉพาะข้อมูลของโรงเรียนตนเอง
        if (auth()->user()->isSchoolAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        return $query;
    }
}
