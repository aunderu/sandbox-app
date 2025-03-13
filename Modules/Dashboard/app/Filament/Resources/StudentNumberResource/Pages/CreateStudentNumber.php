<?php

namespace Modules\Dashboard\Filament\Resources\StudentNumberResource\Pages;

use Modules\Dashboard\Filament\Resources\StudentNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentNumber extends CreateRecord
{
    protected static string $resource = StudentNumberResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.student-numbers.index');
    }
}
