<?php

use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Dashboard\Http\Controllers\ModuleDashboardController;
use Modules\Dashboard\Http\Controllers\SchoolExportImportController;
use Modules\Dashboard\Http\Controllers\StudentNumberExportImportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    Route::resource('dashboard', ModuleDashboardController::class)->names('dashboard');
    Route::get('/dashboard', [ModuleDashboardController::class, 'index']);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('student-numbers/download-template', [StudentNumberExportImportController::class, 'downloadTemplate'])
        ->name('student-numbers.download-template');
    Route::get('school/download-template', [SchoolExportImportController::class, 'downloadTemplate'])
        ->name('school.download-template');
});