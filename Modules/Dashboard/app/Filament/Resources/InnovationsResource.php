<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Enums\UserRole;
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
use Filament\Infolists\Components as InfolistComponent;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Log;
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
                            ->disabled(fn() => Auth::user()->role === UserRole::SCHOOLADMIN)
                            ->relationship('school', 'school_name_th')
                            ->prefix('โรงเรียน')
                            ->preload()
                            ->searchable()
                            ->columnSpanFull()
                            ->required()
                            ->default(fn() => Auth::user()->school_id)
                            ->searchPrompt('เพิ่มชื่อโรงเรียนเพื่อค้นหา...')
                            ->helperText(new HtmlString('กรณีที่ไม่มีชื่อโรงเรียนของท่านแสดง กรุณาติดต่อ<i><strong>ผู้ดูแลระบบ</strong></i>')),
                        Component\Select::make('semester')->label('ภาคเรียน')
                            ->options([
                                1 => 'ตลอดปีการศึกษา',
                                2 => 'ภาคเรียนที่ 1',
                                3 => 'ภาคเรียนที่ 2',
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
            ->defaultSort('created_at', 'desc')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // profile Section
                InfolistComponent\Section::make()
                    ->schema([
                        InfolistComponent\Grid::make()
                            ->schema([
                                // Profile Section (Left)
                                InfolistComponent\TextEntry::make('profile_info')
                                    ->state(function ($record) {
                                        $schoolName = $record->school->school_name_th ?? 'ไม่ระบุสถานศึกษา';
                                        $createdDate = $record->created_at ? $record->created_at->locale('th')->translatedFormat('j M Y') : '-';
                                        $username = $record->user->name ?? 'ไม่ระบุ';

                                        return <<<HTML
                                    <div class="flex flex-col sm:flex-row items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center text-primary-700 dark:text-primary-300 overflow-hidden">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-base font-medium text-gray-900 dark:text-white">โรงเรียน{$schoolName}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-1">
                                                <span>โดย {$username}</span>
                                                <span class="hidden sm:inline px-1">•</span> 
                                                <span>{$createdDate}</span>
                                                <span class="hidden sm:inline px-1">•</span>
                                                <span class="block sm:inline mt-1 sm:mt-0">สร้างเมื่อ {$record->created_at->locale('th')->diffForHumans()}</span>
                                            </div>
                                        </div>
                                    </div>
                                    HTML;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 3,
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-800 rounded-t-xl shadow-sm border-b border-gray-200 dark:border-gray-700 p-3 sm:p-4',
                    ]),

                // innovation content
                InfolistComponent\Section::make()
                    ->schema([
                        // Title & Badge
                        InfolistComponent\TextEntry::make('รายละเอียดนวัตกรรม')
                            ->state(function ($record) {
                                $badge = $record->innovationType->name ?? 'ไม่ระบุประเภท';
                                $badgeHtml = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300 mr-2 mb-2">' . $badge . '</span>';

                                // ดึงข้อมูล tag
                                $tags = is_array($record->tags) ? $record->tags : [];
                                $tagsHtml = '';
                                if (count($tags) > 0) {
                                    foreach ($tags as $tag) {
                                        $tagsHtml .= '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 mr-1 mb-1">#' . htmlspecialchars($tag) . '</span>';
                                    }
                                }

                                return <<<HTML
                            <div class="mb-2 mt-2">
                                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white break-words">
                                    {$record->inno_name}
                                </h1>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    {$badgeHtml}
                                    {$tagsHtml}
                                </div>
                            </div>
                            HTML;
                            })
                            ->html()
                            ->columnSpanFull(),

                        // Content
                        InfolistComponent\TextEntry::make('inno_description')
                            ->label(false)
                            ->html()
                            ->extraAttributes([
                                'class' => 'prose prose-sm sm:prose dark:prose-invert max-w-full text-gray-800 dark:text-gray-200 break-words',
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'overflow-auto bg-white dark:bg-gray-800 rounded-t-xl shadow-sm border-b border-gray-200 dark:border-gray-700 p-3 sm:p-4',
                    ]),

                // file & video content
                InfolistComponent\Section::make()
                    ->schema([
                        InfolistComponent\TextEntry::make('media_content')
                            ->label(false)
                            ->state(function ($record) {
                                $output = '';

                                // Video Section
                                if (!empty($record->video_url)) {
                                    $url = $record->video_url;

                                    $output .= <<<HTML
                                        <div class="overflow-hidden rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 mb-4 sm:mb-6">
                                            <div class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center">
                                                <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-lg bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 mb-3 sm:mb-0 mx-auto sm:mx-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="sm:ml-4 flex-1 mb-3 sm:mb-0 text-start sm:text-left">
                                                    <h3 class="font-medium text-gray-900 dark:text-white truncate">วิดีโอประกอบนวัตกรรม</h3>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-full break-words">{$url}</p>
                                                </div>
                                                <div class="sm:ml-4 flex justify-center sm:justify-start">
                                                    <a href="{$url}" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 max-w-full overflow-hidden text-ellipsis">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                        <span class="truncate">ดูวิดีโอ</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        HTML;
                                }

                                // Attachment Section
                                if (!empty($record->attachments)) {
                                    $url = asset('storage/' . $record->attachments);
                                    $fileName = $record->original_filename ?? basename($record->attachments);
                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                                    $output .= <<<HTML
                                        <div class="w-full overflow-hidden rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 mb-4 sm:mb-6">
                                            <div class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center">
                                                <div class="sm:ml-4 flex-1 mb-3 sm:mb-0 text-start sm:text-left">
                                                    <h3 class="font-medium text-gray-900 dark:text-white truncate max-w-full break-all">
                                                        {$fileName}
                                                    </h3>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">ไฟล์แนบนวัตกรรม <span class="capitalize">{$fileExt}</span></p>
                                                </div>
                                                <div class="sm:ml-4 flex justify-center sm:justify-start">
                                                    <a href="{$url}" target="_blank" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 max-w-full overflow-hidden">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        <span class="truncate">ดาวน์โหลด</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        HTML;
                                }

                                // กรณีไม่มีทั้งวิดีโอและไฟล์แนบ
                                if (empty($output)) {
                                    $output = <<<HTML
                                    <div class="p-4 sm:p-6 text-center text-gray-500 dark:text-gray-400 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 mb-4 sm:mb-6">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium">ไม่มีสื่อเพิ่มเติม</h3>
                                        <p class="mt-1 text-sm">ไม่มีวิดีโอหรือไฟล์แนบสำหรับนวัตกรรมนี้</p>
                                    </div>
                                    HTML;
                                }

                                return $output;
                            })
                            ->html(),
                    ])
                    ->extraAttributes([
                        'class' => 'w-full overflow-auto bg-white dark:bg-gray-800 rounded-t-xl shadow-sm border-b border-gray-200 dark:border-gray-700 p-3 sm:p-4',
                    ]),

                // additional Info
                InfolistComponent\Section::make('รายละเอียดเพิ่มเติม')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->schema([
                        InfolistComponent\Grid::make(3)
                            ->schema([
                                InfolistComponent\TextEntry::make('innovationID')
                                    ->label('รหัสนวัตกรรม')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->copyable()
                                    ->badge()
                                    ->copyMessage('คัดลอกเรียบร้อย!'),

                                InfolistComponent\TextEntry::make('user.name')
                                    ->label('ผู้สร้าง')
                                    ->icon('heroicon-m-user'),

                                InfolistComponent\TextEntry::make('updated_at')
                                    ->label('แก้ไขล่าสุด')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-m-pencil'),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 3,
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-800 rounded-t-xl shadow-sm border-b border-gray-200 dark:border-gray-700 p-3 sm:p-4',
                    ]),
            ]);
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
            'view' => Pages\ViewInnovations::route('/{record}'),
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
        if (Auth::user()->role === UserRole::SCHOOLADMIN) {
            $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }
}
