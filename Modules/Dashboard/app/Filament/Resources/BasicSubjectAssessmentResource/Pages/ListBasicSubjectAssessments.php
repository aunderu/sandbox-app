<?php

namespace Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Pages;

use Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicSubjectAssessments extends ListRecords
{
    protected static string $resource = BasicSubjectAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
