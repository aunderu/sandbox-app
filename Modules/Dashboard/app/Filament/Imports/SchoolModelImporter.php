<?php

namespace Modules\Dashboard\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;
use Modules\Sandbox\Models\SchoolModel;

class SchoolModelImporter extends Importer
{
    protected static ?string $model = SchoolModel::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('school_id')
                ->label('รหัสโรงเรียน')
                ->rules(['required', 'max:20']),
            ImportColumn::make('school_name_th')
                ->label('ชื่อโรงเรียน (ภาษาไทย)')
                ->rules(['required', 'max:255']),
            ImportColumn::make('school_name_en')
                ->label('ชื่อโรงเรียน (ภาษาอังกฤษ)')
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?SchoolModel
    {
        // return SchoolModel::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new SchoolModel();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'การนำเข้าข้อมูลโรงเรียนเสร็จสมบูรณ์ และมีการนำเข้าข้อมูลจำนวน ' . number_format($import->successful_rows) . ' ' . str('แถว')->plural($import->successful_rows) . ' สำเร็จ.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' มี ' . number_format($failedRowsCount) . ' ' . str('แถว')->plural($failedRowsCount) . ' ที่นำเข้าไม่สำเร็จ.';
        }

        return $body;
    }
}
