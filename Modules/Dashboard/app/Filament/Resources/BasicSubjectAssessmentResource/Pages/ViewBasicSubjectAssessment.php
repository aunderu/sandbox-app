<?php

namespace Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Widgets\BasicSubjectRadarChart;
use Modules\Dashboard\Filament\Widgets\SubjectComparisonChart;

class ViewBasicSubjectAssessment extends ViewRecord
{
    protected static string $resource = BasicSubjectAssessmentResource::class;

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

            // Actions\Action::make('print')
            //     ->label('พิมพ์รายงาน')
            //     ->color('success')
            //     ->icon('heroicon-o-printer')
            //     ->url(fn() => route('print.basic-subject', ['record' => $this->getRecord()->id]))
            //     ->openUrlInNewTab(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SubjectComparisonChart::class,
        ];
    }
}