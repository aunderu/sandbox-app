<?php

namespace Modules\Dashboard\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Modules\Sandbox\Models\SchoolModel;

class SchoolModelExporter extends Exporter
{
    protected static ?string $model = SchoolModel::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('school_id'),
            ExportColumn::make('school_name_th'),
            ExportColumn::make('school_name_en'),
            ExportColumn::make('ministry'),
            ExportColumn::make('department'),
            ExportColumn::make('area'),
            ExportColumn::make('school_sizes'),
            ExportColumn::make('founding_date'),
            ExportColumn::make('school_course_type'),
            ExportColumn::make('course_attachment'),
            ExportColumn::make('house_id'),
            ExportColumn::make('vallage_no'),
            ExportColumn::make('road'),
            ExportColumn::make('sub_district'),
            ExportColumn::make('district'),
            ExportColumn::make('province'),
            ExportColumn::make('postal_code'),
            ExportColumn::make('phone'),
            ExportColumn::make('fax'),
            ExportColumn::make('email'),
            ExportColumn::make('website'),
            ExportColumn::make('student_amount'),
            ExportColumn::make('disadventaged_student_amount'),
            ExportColumn::make('teacher_amount'),
            ExportColumn::make('latitude'),
            ExportColumn::make('longitude'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'การส่งออกข้อมูลโรงเรียนเสร็จสมบูรณ์แล้ว และมี ' . number_format($export->successful_rows) . ' ' . str('แถว')->plural($export->successful_rows) . ' ที่ถูกส่งออก.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' มี ' . number_format($failedRowsCount) . ' ' . str('แถว')->plural($failedRowsCount) . ' ที่ส่งออกไม่สำเร็จ.';
        }

        return $body;
    }
}
