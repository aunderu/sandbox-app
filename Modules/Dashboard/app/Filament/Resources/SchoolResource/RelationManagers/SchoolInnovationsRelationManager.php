<?php

namespace Modules\Dashboard\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolInnovationsRelationManager extends RelationManager
{
    protected static string $relationship = 'schoolInnovations';

    protected static ?string $modelLabel = 'นวัตกรรม';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('ข้อมูลนวัตกรรม')
                    ->schema([
                        Select::make('school_id')->label(__('สถานศึกษา'))
                            ->relationship('school', 'school_name_th')
                            ->searchable()
                            ->columnSpanFull()
                            ->required(),
                        TextInput::make('inno_name')->label(__('ชื่อนวัตกรรม'))
                            ->minLength(3)->maxLength(255)
                            ->required(),
                        // Select::make('inno_type_id')->label('ประเภทนวัตกรรม')
                        //     ->relationship('innovationType', 'name')
                        //     ->required(),
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
                    ])->columnSpan(2)->columns(2),

                Group::make()->schema([
                    Section::make('วีดีโอและไฟล์แนบ')
                        ->schema([
                            TextInput::make('video_url')->label(__('ลิงค์วีดีโอ'))
                                ->nullable()
                                ->url(),
                            FileUpload::make('attachment')
                                ->disk('public')
                                ->directory('attachments')
                                ->storeFiles(false)
                                ->label(__('ไฟล์แนบนวัตกรรม')),
                        ])->columnSpan(1),
                ]),


            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('inno_type_id')
            ->columns([

                Tables\Columns\TextColumn::make('inno_name')
                    ->sortable()
                    ->color('primary')
                    ->searchable()
                    ->description(fn($record) => \Illuminate\Support\Str::limit($record->inno_description, 50))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tags')->label('แท็ก')
                    // ->alignCenter()
                    ->searchable()
                    ->icon('heroicon-m-hashtag')
                    ->iconColor('primary')
                    ->listWithLineBreaks()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('วันที่สร้าง')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-calendar')
                    ->iconColor('primary')
                    ->date('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
