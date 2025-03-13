<?php

namespace Modules\Dashboard\Filament\Resources\StudentNumberResource\Pages;

use Modules\Dashboard\Filament\Resources\StudentNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentNumber extends EditRecord
{
    protected static string $resource = StudentNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin()),
        ];
    }
}
