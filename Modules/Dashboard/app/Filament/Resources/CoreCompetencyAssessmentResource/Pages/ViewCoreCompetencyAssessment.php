<?php

namespace Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Widgets\CoreCompetencyComparisonChart;
use Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Widgets\CoreCompetencyRadarChart;

class ViewCoreCompetencyAssessment extends ViewRecord
{
    protected static string $resource = CoreCompetencyAssessmentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(function () {
                    $user = Auth::user();
                    $record = $this->getRecord();
                    return $user->role === UserRole::SUPERADMIN || 
                        ($user->role === UserRole::SCHOOLADMIN && $user->school_id === $record->school_id);
                }),
                
            Actions\DeleteAction::make()
                ->visible(function () {
                    $user = Auth::user();
                    $record = $this->getRecord();
                    return $user->role === UserRole::SUPERADMIN || 
                        ($user->role === UserRole::SCHOOLADMIN && $user->school_id === $record->school_id);
                }),
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            CoreCompetencyComparisonChart::class,
        ];
    }
}