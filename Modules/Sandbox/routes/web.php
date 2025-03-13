<?php

use Illuminate\Support\Facades\Route;
use Modules\Sandbox\Http\Controllers\InnovationController;
use Modules\Sandbox\Http\Controllers\SandboxController;

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
    Route::resource('sandbox', SandboxController::class)->names('sandbox');
    Route::get('/', [SandboxController::class, 'index']);
    Route::get('/load-more-innovations', [InnovationController::class, 'loadMore'])->name('innovations.load_more');
});