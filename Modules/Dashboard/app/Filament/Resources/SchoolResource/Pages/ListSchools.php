<?php

namespace Modules\Dashboard\Filament\Resources\SchoolResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Modules\Sandbox\Models\SchoolModel;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
        ];
    }
    protected function paginateTableQuery(Builder $query): CursorPaginator
    {
        $perPage = $this->getTableRecordsPerPage();
        $totalRecords = $query->count();

        if ($perPage === 'all' || $perPage <= 0) {
            $perPage = $totalRecords > 0 ? $totalRecords : 1;
        }

        return $query->cursorPaginate($perPage);
    }

    public function getTabs(): array
    {
        $schoolCourses = SchoolModel::distinct()->pluck('school_course_type')->flatten()->unique();

        $tabs = [
            'ทั้งหมด' => Tab::make(),
        ];

        foreach ($schoolCourses as $course) {
            $tabs[$course] = Tab::make()->query(function ($query) use ($course) {
                $query->whereJsonContains('school_course_type', $course);
            });
        }

        return $tabs;
    }
}
