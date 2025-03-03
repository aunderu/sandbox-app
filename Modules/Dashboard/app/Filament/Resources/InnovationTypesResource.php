<?php

namespace Modules\Dashboard\Filament\Resources;

use Filament\Forms\Components\Textarea;
use Modules\Dashboard\Filament\Resources\InnovationTypesResource\Pages;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Dashboard\Filament\Resources\InnovationTypesResource\RelationManagers\InnovationsRelationManager;
use Modules\Sandbox\Models\InnovationTypesModel;

class InnovationTypesResource extends Resource
{
    protected static ?string $model = InnovationTypesModel::class;

    protected static ?string $navigationIcon = 'heroicon-s-folder';

    protected static ?string $modelLabel = 'ประเภทนวัตกรรม';

    protected static ?string $navigationGroup = "นวัตกรรม";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('ข้อมูลประเภทนวัตกรรม')
                    ->schema([
                        TextInput::make('name')->label('ชื่อประเภทนวัตกรรม')
                            ->required()
                            ->minLength(3)->maxLength(255)
                            ->placeholder('กรอกชื่อประเภทนวัตกรรม'),
                        Textarea::make('description')->label('รายละเอียด')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('กรอกรายละเอียดของประเภทนวัตกรรม'),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('รหัสประเภทนวัตกรรม')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('ชื่อประเภทนวัตกรรม')
                    ->sortable()
                    ->searchable()
                    ->description(fn($record) => \Illuminate\Support\Str::limit($record->description, 100))
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InnovationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInnovationTypes::route('/'),
            'create' => Pages\CreateInnovationTypes::route('/create'),
            'edit' => Pages\EditInnovationTypes::route('/{record}/edit'),
        ];
    }
}
