<?php

namespace Modules\Dashboard\Filament\Resources\BasicSubjectAssessmentResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class BasicSubjectRadarChart extends Widget
{
    protected static string $view = 'dashboard::widgets.basicsubjectassessment.basic-subject-radar-chart';
    
    public ?Model $record = null;

    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
}