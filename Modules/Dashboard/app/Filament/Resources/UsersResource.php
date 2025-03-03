<?php

namespace Modules\Dashboard\Filament\Resources;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Modules\Dashboard\Filament\Resources\UsersResource\Pages;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
            Section::make('ข้อมูลผู้ใช้งาน')
                ->collapsible()
                ->schema([
                    FileUpload::make('avatar_url')
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
                    TextInput::make('name')->label(__('ชื่อ User'))
                        ->minLength(3)->maxLength(255)
                        ->required(),
                    TextInput::make('email')
                        ->label(__('messages.email'))
                        ->email()
                        ->unique()
                        ->disabledOn('edit'),
                    TextInput::make('password')->label(__('messages.password'))
                        ->password()
                        ->revealable()
                        ->visibleOn('create'),
                    Select::make('role')
                        ->label(__('Role'))
                        ->options(User::ROLES)
                        ->required()
                        ->reactive()
                        ->disabledOn('edit')
                        ->afterStateUpdated(fn(callable $set, $state) => $set('school_id', null)),
                    TextInput::make('school_id')->label(__('รหัสสถานศึกษา'))
                        ->minLength(3)->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->visible(fn($get) => $get('role') === User::ROLE_SCHOOLADMIN)
                        ->required(fn($get) => $get('role') === User::ROLE_SCHOOLADMIN)
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
                    ->badge()
                    ->color(fn(User $record) => match ($record->role) {
                        User::ROLE_SUPERADMIN => 'danger',
                        User::ROLE_SCHOOLADMIN => 'primary',
                        User::ROLE_OFFICER => 'info',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->searchable()
                    ->options(User::ROLES)
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
        ];
    }
}
