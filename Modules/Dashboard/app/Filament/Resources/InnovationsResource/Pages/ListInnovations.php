<?php

namespace Modules\Dashboard\Filament\Resources\InnovationsResource\Pages;

use App\Enums\UserRole;
use Modules\Dashboard\Filament\Resources\InnovationsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Auth;
use Modules\Sandbox\Models\InnovationTypesModel;

class ListInnovations extends ListRecords
{
    protected static string $resource = InnovationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => Auth::user()->role === UserRole::SUPERADMIN || Auth::user()->role === UserRole::SCHOOLADMIN),
        ];
    }

    public function getTabs(): array
    {
        $innovationTypes = InnovationTypesModel::all();

        $tabs = [
            'ทั้งหมด' => Tab::make(),
        ];

        foreach ($innovationTypes as $type) {
            $tabs[$type->name] = Tab::make()->query(function ($query) use ($type) {
                $query->whereHas('innovationType', function ($query) use ($type) {
                    $query->where('name', $type->name);
                });
            });
        }

        return $tabs;
    }
}
