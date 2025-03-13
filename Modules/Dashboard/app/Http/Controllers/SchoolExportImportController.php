<?php
// Modules/Dashboard/app/Http/Controllers/SchoolExportImportController.php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Dashboard\Exports\SchoolTemplateExport;

class SchoolExportImportController extends Controller
{
    public function downloadTemplate()
    {
        return Excel::download(new SchoolTemplateExport(), 'school_template.xlsx');
    }
}