<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| WordPress-style installer (blocked automatically once installed)
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->middleware('install.installed')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'store'])->name('store');
    Route::post('/test-connection', [InstallController::class, 'testConnection'])->name('test');
    Route::get('/run', [InstallController::class, 'run'])->name('run');
    Route::post('/run', [InstallController::class, 'execute'])->name('execute');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete')->withoutMiddleware('install.installed');
});

Route::get('/', function () {
    return view('welcome');
});
