<?php

namespace Modules\Dashboard\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Modules\Sandbox\Models\BasicSubjectAssessmentModel;

class SubjectComparisonChart extends Widget
{
    protected static string $view = 'dashboard::widgets.basicsubjectassessment.subject-comparison-chart';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function getAverageScores()
    {
        // ดึงค่าเฉลี่ยของทุกโรงเรียนในปีการศึกษาเดียวกัน
        $averages = BasicSubjectAssessmentModel::where('education_year', $this->record->education_year)
            ->select(
                \Illuminate\Support\Facades\DB::raw('AVG(thai_score) as avg_thai'),
                \Illuminate\Support\Facades\DB::raw('AVG(math_score) as avg_math'),
                \Illuminate\Support\Facades\DB::raw('AVG(science_score) as avg_science'),
                \Illuminate\Support\Facades\DB::raw('AVG(english_score) as avg_english')
            )
            ->first();

        return [
            'thai' => round($averages->avg_thai ?? 0, 2),
            'math' => round($averages->avg_math ?? 0, 2),
            'science' => round($averages->avg_science ?? 0, 2),
            'english' => round($averages->avg_english ?? 0, 2),
        ];
    }
}
