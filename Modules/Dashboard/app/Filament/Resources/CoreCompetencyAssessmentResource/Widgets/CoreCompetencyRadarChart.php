<?php

namespace Modules\Dashboard\Filament\Resources\CoreCompetencyAssessmentResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class CoreCompetencyRadarChart extends Widget
{
    protected static string $view = 'dashboard::widgets.corecompetencyassessment.core-competency-radar-chart';
    
    public ?Model $record = null;
    
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
}
