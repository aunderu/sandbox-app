<?php

namespace Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Modules\Sandbox\Models\CoreCompetencyAssessmentModel;

class CoreCompetencyComparisonChart extends Widget
{
    protected static string $view = 'dashboard::widgets.corecompetencyassessment.competency-comparison-chart';
    
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function getAverageScores()
    {
        // ดึงค่าเฉลี่ยของทุกโรงเรียนในปีการศึกษาเดียวกัน
        $averages = CoreCompetencyAssessmentModel::where('education_year', $this->record->education_year)
            ->select(
                \Illuminate\Support\Facades\DB::raw('AVG(self_management_score) as avg_self'),
                \Illuminate\Support\Facades\DB::raw('AVG(teamwork_score) as avg_teamwork'),
                \Illuminate\Support\Facades\DB::raw('AVG(high_thinking_score) as avg_thinking'),
                \Illuminate\Support\Facades\DB::raw('AVG(communication_score) as avg_communication'),
                \Illuminate\Support\Facades\DB::raw('AVG(active_citizen_score) as avg_citizen'),
                \Illuminate\Support\Facades\DB::raw('AVG(sustainable_coexistence_score) as avg_nature')
            )
            ->first();

        return [
            'self' => round($averages->avg_self ?? 0, 2),
            'teamwork' => round($averages->avg_teamwork ?? 0, 2),
            'thinking' => round($averages->avg_thinking ?? 0, 2),
            'communication' => round($averages->avg_communication ?? 0, 2),
            'citizen' => round($averages->avg_citizen ?? 0, 2),
            'nature' => round($averages->avg_nature ?? 0, 2),
        ];
    }
}
