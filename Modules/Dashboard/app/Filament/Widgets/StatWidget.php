<?php

namespace Modules\Dashboard\Filament\Widgets;

use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Sandbox\Models\InnovationsModel;
use Modules\Sandbox\Models\SchoolModel;

class StatWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('ผู้ใช้งาน', User::count())
                ->description('จำนวนผู้ใช้งานทั้งหมด')
                ->descriptionIcon('heroicon-s-user-group', IconPosition::Before)
                ->color('success'),
            Stat::make('นวัตกรรม', InnovationsModel::count())
                ->description('จำนวนนวัตกรรมทั้งหมด')
                ->descriptionIcon('heroicon-s-cube', IconPosition::Before)
                ->color('primary'),
            Stat::make('สถานศึกษา', SchoolModel::count())
                ->description('จำนวนสถานศึกษาทั้งหมด')
                ->descriptionIcon('heroicon-s-building-office-2', IconPosition::Before)
                ->color('info'),
        ];
    }
}
