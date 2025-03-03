<?php

namespace Modules\Dashboard\Filament\Resources;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Filters\SelectFilter;
use Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;
use Filament\Forms\Components\TextInput;
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
                Section::make('ข้อมูลนวัตกรรม')
                    ->schema([
                        Select::make('school_id')->label(__('สถานศึกษา'))
                            ->disabled(fn() => Auth::user()->isSchoolAdmin())
                            ->relationship('school', 'school_name_th')
                            ->prefix('โรงเรียน')
                            ->searchable()
                            ->columnSpanFull()
                            ->required()
                            ->default(fn() => Auth::user()->school_id)
                            ->helperText(new HtmlString('กรณีที่ไม่มีชื่อโรงเรียนของท่านแสดง กรุณาติดต่อ<i><strong>ผู้ดูแลระบบ</strong></i>')),
                        TextInput::make('inno_name')->label(__('ชื่อนวัตกรรม'))
                            ->minLength(3)->maxLength(255)
                            ->required(),
                        Select::make('inno_type_id')->label('ประเภทนวัตกรรม')
                            ->relationship('innovationType', 'name')
                            ->required(),
                        Textarea::make('inno_description')->label(__('รายละเอียด'))
                            ->minLength(3)->maxLength(255)
                            ->columnSpanFull()
                            ->required(),
                        TagsInput::make('tags')->label(__('แท็ก'))
                            ->nullable()
                            ->columnSpanFull()
                            ->suggestions([
                                'นวัตกรรม',
                                'สิ่งประดิษฐ์',
                                'สื่อการเรียนรู้',
                                'พื้นที่นวัตกรรม',
                                'สร้างสรรค์',
                            ]),
                        Hidden::make('user_id')
                            ->default(fn() => Auth::id())
                            ->required(),
                    ])->columnSpan(2)->columns(2),

                Group::make()->schema([
                    Section::make('วีดีโอและไฟล์แนบ')
                        ->schema([
                            TextInput::make('video_url')->label(__('ลิงค์วีดีโอ'))
                                ->nullable()
                                ->placeholder('https://www.youtube.com/watch?v=...')
                                ->suffixIcon('heroicon-m-globe-alt')
                                ->url(),
                            FileUpload::make('attachments')
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
                TextColumn::make('innovationType.name')->label('ประเภทนวัตกรรม')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('inno_name')->label('ชื่อนวัตกรรม')
                    ->sortable()
                    ->color('primary')
                    ->searchable()
                    ->description(fn($record) => \Illuminate\Support\Str::limit($record->inno_description, 50))
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
}
