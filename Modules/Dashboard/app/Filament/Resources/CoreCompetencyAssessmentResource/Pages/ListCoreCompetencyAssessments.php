<?php

namespace Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Pages;

use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoreCompetencyAssessments extends ListRecords
{
    protected static string $resource = CoreCompetencyAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
