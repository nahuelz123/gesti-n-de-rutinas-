<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\RoutineController;
use App\Http\Controllers\Client\LogController;
use App\Http\Controllers\Client\NutritionController;
use App\Http\Controllers\Client\MealLogController;
use App\Http\Controllers\Client\ChatController;
use App\Http\Controllers\Client\AiChatController;
use App\Http\Controllers\Client\ProgressController;
use App\Http\Controllers\Client\RecipeCatalogController;
use App\Http\Controllers\Client\NotificationsController;
use App\Http\Controllers\Client\FreeMealLogController;
use App\Http\Controllers\GymJoinController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Alta por QR: el alumno escanea el código de SU gimnasio, elige iniciar sesión
// (si ya tiene cuenta) o registrarse (entra siempre como cliente, ya con el
// gym_id correcto). Público, sin auth.
Route::get('/unirse/{inviteCode}', [GymJoinController::class, 'show'])->name('gym-join.show');
Route::post('/unirse/{inviteCode}', [GymJoinController::class, 'register'])->name('gym-join.register');

Route::get('dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'client') {
        return redirect()->route('client.dashboard');
    }
    return redirect('/admin');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'client', 'no-back'])
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

        Route::get('/nutrition', [NutritionController::class, 'index'])->name('nutrition.index');
        Route::post('/nutrition/logs', [MealLogController::class, 'store'])->name('nutrition.logs.store');

        Route::get('/nutrition/foods/search', [FreeMealLogController::class, 'searchFoods'])->name('nutrition.foods.search');
        Route::post('/nutrition/free-logs', [FreeMealLogController::class, 'store'])->name('nutrition.free-logs.store');
        Route::delete('/nutrition/free-logs/{freeMealLog}', [FreeMealLogController::class, 'destroy'])->name('nutrition.free-logs.destroy');

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/fetch', [ChatController::class, 'fetch'])->name('chat.fetch');
        Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

        Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
        Route::post('/ai-chat/send', [AiChatController::class, 'send'])->name('ai-chat.send');
        Route::post('/ai-chat/reset', [AiChatController::class, 'reset'])->name('ai-chat.reset');

        Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
        Route::post('/progress/profile', [ProgressController::class, 'updateProfile'])->name('progress.profile');
        Route::post('/progress', [ProgressController::class, 'store'])->name('progress.store');
        Route::delete('/progress/{measurement}', [ProgressController::class, 'destroy'])->name('progress.destroy');

        Route::get('/recipes', [RecipeCatalogController::class, 'index'])->name('recipes.index');
        Route::get('/recipes/{recipe}', [RecipeCatalogController::class, 'show'])->name('recipes.show');

        Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    });

require __DIR__ . '/settings.php';
