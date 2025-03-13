<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Dashboard\Exports\StudentNumberTemplateExport;
use Modules\Dashboard\Exports\StudentNumbersExport;
use Modules\Dashboard\Imports\StudentNumbersImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Enums\UserRole;

class StudentNumberExportImportController extends Controller
{
    public function downloadTemplate()
    {
        return Excel::download(new StudentNumberTemplateExport, 'student_numbers_template.xlsx');
    }
    
    public function export(Request $request)
    {
        $filters = [
            'school_id' => $request->input('school_id'),
            'year_id' => $request->input('year_id'),
            'education_year' => $request->input('education_year'),
        ];
        
        return Excel::download(new StudentNumbersExport($filters), 'student_numbers_export_' . now()->format('Y-m-d') . '.xlsx');
    }
    
    public function import(Request $request)
    {
        if (!Auth::user()->role === UserRole::SUPERADMIN && !Auth::user()->role === UserRole::SCHOOLADMIN) {
            Notification::make()
                ->title('ไม่มีสิทธิ์การนำเข้าข้อมูล')
                ->danger()
                ->send();
                
            return redirect()->back();
        }
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        
        try {
            $import = new StudentNumbersImport;
            Excel::import($import, $request->file('file'));
            
            Notification::make()
                ->title('นำเข้าข้อมูลสำเร็จ')
                ->body('นำเข้าข้อมูลจำนวน ' . $import->getRowCount() . ' รายการสำเร็จ')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('เกิดข้อผิดพลาด')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
        
        return redirect()->back();
    }
}