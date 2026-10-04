<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GifUploadController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/routines', [RoutineController::class, 'store'])->name('routines.store');
    Route::post('/uploads/gif', [GifUploadController::class, 'store'])->name('uploads.gif');
    Route::put('/routines/{routine}', [RoutineController::class, 'update'])->name('routines.update');
    Route::delete('/routines/{routine}', [RoutineController::class, 'destroy'])->name('routines.destroy');
    Route::get('/routines/{routine}/json', [RoutineController::class, 'json'])->name('routines.json');

    Route::put('/settings/username', [SettingsController::class, 'updateUsername'])->name('settings.username');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
});
