<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Models\User;
use App\Enums\UserRole;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Modules\Dashboard\Filament\Resources\UsersResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Forms\Components as FormComponents;

class UsersResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-users';

    protected static ?string $modelLabel = 'ผู้ใช้งาน';

    protected static ?string $navigationGroup = "ผู้ใช้งาน";

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'จำนวนผู้ใช้งาน';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            FormComponents\Section::make('ข้อมูลผู้ใช้งาน')
                ->collapsible()
                ->schema([
                    FormComponents\FileUpload::make('avatar_url')
                        ->label('Avatar')
                        ->avatar()
                        ->imageEditor()
                        ->circleCropper()
                        ->storeFiles(false)
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public')
                        ->deleteUploadedFileUsing(function ($file) {
                            // ลบไฟล์เก่าก่อนบันทึกไฟล์ใหม่
                            Storage::disk('public')->delete($file);
                        }),
                    FormComponents\TextInput::make('name')->label(__('ชื่อ User'))
                        ->minLength(3)->maxLength(255)
                        ->required(),
                    FormComponents\TextInput::make('email')
                        ->label(__('messages.email'))
                        ->email()
                        ->unique()
                        ->disabledOn('edit'),
                    FormComponents\TextInput::make('password')->label(__('messages.password'))
                        ->password()
                        ->revealable()
                        ->visibleOn('create'),
                    FormComponents\Select::make('role')
                        ->label(__('Role'))
                        ->options(UserRole::getSelectOptions())
                        ->required()
                        ->reactive()
                        ->disabledOn('edit')
                        ->afterStateUpdated(fn(callable $set, $state) => $set('school_id', null)),
                    FormComponents\TextInput::make('school_id')->label(__('รหัสสถานศึกษา'))
                        ->minLength(3)->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->visible(fn($get) => $get('role') === UserRole::SCHOOLADMIN->value)
                        ->required(fn($get) => $get('role') === UserRole::SCHOOLADMIN->value)
                        ->disabledOn('edit')
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->default(fn(User $record)
                        => 'https://ui-avatars.com/api/?name=' .
                        urlencode($record->name) .
                        '&color=FFFFFF&background=000000')
                    ->circular(),
                TextColumn::make('name'),
                TextColumn::make('email')->icon('heroicon-m-envelope'),
                TextColumn::make('role')
                    ->formatStateUsing(fn(UserRole $state) => $state->getLabel())
                    ->badge()
                    ->color(fn(User $record) => match ($record->role) {
                        UserRole::SUPERADMIN => 'danger',
                        UserRole::SCHOOLADMIN => 'primary',
                        UserRole::OFFICER => 'info',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->searchable()
                    ->options(UserRole::getSelectOptions())
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                ])
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header Section with Profile
                InfolistSection::make()
                    ->schema([
                        Components\Grid::make()
                            ->schema([
                                
                                // Profile Section (Left)
                                Components\TextEntry::make('profile_info')
                                    ->label(false)
                                    ->state(function (User $record) {
                                        $roleBadgeColor = match ($record->role) {
                                            UserRole::SUPERADMIN => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            UserRole::SCHOOLADMIN => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            UserRole::OFFICER => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                            default => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        };

                                        $avatarUrl = $record->avatar_url 
                                            ? Storage::disk('public')->url($record->avatar_url) 
                                            : 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=000000';
                                        $roleLabel = $record->role->getLabel();
                                        $createdDate = $record->created_at ? $record->created_at->locale('th')->translatedFormat('j M Y') : '-';

                                        return <<<HTML
                                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                            <div class="flex-shrink-0">
                                                <img src="{$avatarUrl}" alt="{$record->name}" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700">
                                            </div>
                                            <div class="flex flex-col items-center sm:items-start">
                                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{$record->name}</h2>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$roleBadgeColor}">
                                                        {$roleLabel}
                                                    </span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">•</span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        สมัครเมื่อ {$createdDate}
                                                    </span>
                                                </div>
                                                <div class="flex items-center mt-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">{$record->email}</span>
                                                </div>
                                            </div>
                                        </div>
                                        HTML;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-gray-800 rounded-t-xl shadow-sm border-b border-gray-200 dark:border-gray-700 p-6',
                    ]),

                // Main Information
                InfolistSection::make('ข้อมูลทั่วไป')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('รหัสผู้ใช้งาน')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->copyable()
                                    ->badge(),

                                TextEntry::make('created_at')
                                    ->label('สร้างเมื่อ')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('แก้ไขล่าสุด')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-m-pencil'),
                            ]),
                    ]),

                // School Admin ข้อมูลเพิ่มเติม (แสดงเฉพาะเมื่อบทบาทเป็น SCHOOLADMIN)
                InfolistSection::make('ข้อมูลสถานศึกษา')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('school_id')
                                    ->label('รหัสสถานศึกษา')
                                    ->copyable()
                                    ->icon('heroicon-m-academic-cap'),

                                TextEntry::make('school.school_name_th')
                                    ->label('ชื่อสถานศึกษา')
                                    ->columnSpanFull(),

                                TextEntry::make('school.address')
                                    ->label('ที่อยู่')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn(User $record) => $record->role === UserRole::SCHOOLADMIN),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUsers::route('/create'),
            'edit' => Pages\EditUsers::route('/{record}/edit'),
            'view' => Pages\ViewUsers::route('/{record}'),
        ];
    }
}