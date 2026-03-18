<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\RoutineController;
use App\Http\Controllers\Client\LogController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware(['auth', 'client'])
    ->prefix('app')
    ->name('client.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/routines/active', [RoutineController::class, 'active'])->name('routines.active');
        Route::get('/routines/history', [RoutineController::class, 'history'])->name('routines.history');
        Route::get('/routines/{assignment}', [RoutineController::class, 'show'])->name('routines.show');
        Route::get('/progress/exercise/{exercise}', [RoutineController::class, 'exerciseProgress'])
            ->name('progress.exercise');
            
        Route::post('/logs', [LogController::class, 'store'])->name('logs.store');
    });





require __DIR__ . '/settings.php';
