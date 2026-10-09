<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GifUploadController;
use App\Http\Controllers\LibraryExerciseController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

Route::get('/recuperar', [PasswordRecoveryController::class, 'showForgot'])->name('password.forgot');
Route::post('/recuperar', [PasswordRecoveryController::class, 'askQuestion'])->name('password.ask');
Route::post('/recuperar/verificar', [PasswordRecoveryController::class, 'verifyAnswer'])
    ->middleware('throttle:10,1')->name('password.verify');
Route::get('/recuperar/nueva', [PasswordRecoveryController::class, 'showReset'])->name('password.reset.form');
Route::post('/recuperar/nueva', [PasswordRecoveryController::class, 'reset'])->name('password.reset.save');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/routines/{routine}/json', [RoutineController::class, 'json'])->name('routines.json');

    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::put('/settings/timer-mode', [SettingsController::class, 'updateTimerMode'])->name('settings.timer_mode');

    // Solo el profesor administra rutinas, biblioteca y alumnos
    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::post('/routines', [RoutineController::class, 'store'])->name('routines.store');
        Route::put('/routines/{routine}', [RoutineController::class, 'update'])->name('routines.update');
        Route::delete('/routines/{routine}', [RoutineController::class, 'destroy'])->name('routines.destroy');

        Route::post('/uploads/gif', [GifUploadController::class, 'store'])->name('uploads.gif');
        Route::post('/library', [LibraryExerciseController::class, 'store'])->name('library.store');
        Route::put('/library/{libraryExercise}', [LibraryExerciseController::class, 'update'])->name('library.update');
        Route::delete('/library/{libraryExercise}', [LibraryExerciseController::class, 'destroy'])->name('library.destroy');

        Route::put('/settings/username', [SettingsController::class, 'updateUsername'])->name('settings.username');

        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{student}/password', [StudentController::class, 'resetPassword'])->name('students.password');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    });
});
