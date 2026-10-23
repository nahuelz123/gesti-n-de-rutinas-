<?php

use App\Http\Controllers\Auth\LoginSessionController;
use App\Http\Controllers\Client\AccountController;
use App\Http\Controllers\Client\ActivityController;
use App\Http\Controllers\Client\AiChatController;
use App\Http\Controllers\Client\ChatController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\ExperienceController;
use App\Http\Controllers\Client\FreeMealLogController;
use App\Http\Controllers\Client\LogController;
use App\Http\Controllers\Client\MealLogController;
use App\Http\Controllers\Client\MealPhotoController;
use App\Http\Controllers\Client\NotificationsController;
use App\Http\Controllers\Client\NutritionController;
use App\Http\Controllers\Client\ProgressController;
use App\Http\Controllers\Client\RecipeCatalogController;
use App\Http\Controllers\Client\RestAlertController;
use App\Http\Controllers\Client\RoutineController;
use App\Http\Controllers\CommercialAdminController;
use App\Http\Controllers\CommercialExportController;
use App\Http\Controllers\CommercialInquiryController;
use App\Http\Controllers\CommercialManagementController;
use App\Http\Controllers\CommercialManualPaymentsController;
use App\Http\Controllers\CommercialManualPlansController;
use App\Http\Controllers\CommercialReceiptController;
use App\Http\Controllers\CommercialReminderController;
use App\Http\Controllers\CommercialSettingsController;
use App\Http\Controllers\CommercialSubscriptionController;
use App\Http\Controllers\CommercialWebhookController;
use App\Http\Controllers\GymJoinController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\RoutinePhotoController;
use App\Http\Middleware\CommercialMembershipAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class, 'show'])->name('home');
foreach (['asesorias-online' => 'personal', 'software-entrenadores-personales' => 'coach', 'software-gimnasios' => 'gym', 'planes' => 'plans', 'preguntas-frecuentes' => 'faq', 'recursos' => 'resources', 'contacto' => 'contact'] as $path => $page) {
    Route::get('/'.$path, [MarketingController::class, 'show'])->defaults('page', $page)->name('marketing.'.$page);
}
Route::get('/recursos/{slug}', [MarketingController::class, 'guide'])->name('marketing.guide');
Route::get('/sitemap.xml', [MarketingController::class, 'sitemap']);
Route::get('/robots.txt', [MarketingController::class, 'robots']);
Route::post('/contacto', [CommercialInquiryController::class, 'store'])->middleware('throttle:6,1');
Route::get('/contacto/recibido', [CommercialInquiryController::class, 'received']);
foreach (['baja', 'arrepentimiento', 'privacidad/solicitud'] as $type) {
    Route::get('/'.$type, [CommercialInquiryController::class, 'form'])->defaults('type', $type);
    Route::post('/'.$type, [CommercialInquiryController::class, 'store'])->defaults('type', $type)->middleware('throttle:6,1');
}
Route::get('/gestion-comercial', [CommercialAdminController::class, 'index'])->middleware('auth');
Route::get('/gestion-comercial/configuracion', [CommercialSettingsController::class, 'index'])->middleware('auth');
Route::post('/gestion-comercial/configuracion', [CommercialSettingsController::class, 'update'])->middleware(['auth', 'throttle:10,1']);
Route::post('/gestion-comercial/membresias/{subscription}/cobros', [CommercialManualPaymentsController::class, 'store'])->middleware(['auth', 'throttle:20,1']);
Route::post('/gestion-comercial/pagos/{payment}/reintegros', [CommercialManualPaymentsController::class, 'refund'])->middleware(['auth', 'throttle:20,1']);
Route::post('/gestion-comercial/membresias', [CommercialManualPlansController::class, 'store'])->middleware(['auth', 'throttle:20,1']);
Route::post('/gestion-comercial/membresias/{subscription}/plan', [CommercialManualPlansController::class, 'schedule'])->middleware(['auth', 'throttle:20,1']);
Route::post('/gestion-comercial/membresias/{subscription}/baja', [CommercialManualPlansController::class, 'cancel'])->middleware(['auth', 'throttle:20,1']);
Route::post('/gestion-comercial/membresias/{subscription}/reanudar', [CommercialManualPlansController::class, 'resume'])->middleware(['auth', 'throttle:20,1']);
Route::get('/gestion-comercial/recordatorios', [CommercialReminderController::class, 'index'])->middleware('auth');
Route::post('/gestion-comercial/recordatorios/configuracion', [CommercialReminderController::class, 'configure'])->middleware(['auth', 'throttle:10,1']);
Route::get('/gestion-comercial/membresias', [CommercialManagementController::class, 'index'])->middleware('auth');
Route::get('/gestion-comercial/membresias/{subscription}', [CommercialManagementController::class, 'show'])->middleware('auth');
Route::get('/gestion-comercial/movimientos.csv', CommercialExportController::class)->middleware(['auth', 'throttle:10,1']);
Route::post('/gestion-comercial/pagos/{payment}/referencia-fiscal', [CommercialManualPaymentsController::class, 'fiscal'])->middleware(['auth', 'throttle:20,1']);
Route::post('/gestion-comercial/solicitudes/{inquiry}', [CommercialAdminController::class, 'update'])->middleware('auth');
Route::post('/gestion-comercial/asesorias', [CommercialAdminController::class, 'coaching'])->middleware('auth');
Route::get('/empezar/{plan}', [CommercialSubscriptionController::class, 'start']);
Route::post('/empezar/{plan}', [CommercialSubscriptionController::class, 'enroll'])->middleware('throttle:5,1');
Route::get('/mi-suscripcion', [CommercialSubscriptionController::class, 'index'])->middleware('auth');
Route::get('/mi-suscripcion/comprobantes/{payment:public_id}', CommercialReceiptController::class)->middleware('auth');
Route::post('/mi-suscripcion/checkout', [CommercialSubscriptionController::class, 'checkout'])->middleware(['auth', 'throttle:5,1']);
Route::post('/mi-suscripcion/cancelar', [CommercialSubscriptionController::class, 'cancel'])->middleware(['auth', 'throttle:5,1']);
Route::post('/mi-suscripcion/actualizar', [CommercialSubscriptionController::class, 'reconcile'])->middleware(['auth', 'throttle:5,1']);
Route::post('/webhooks/mercadopago', CommercialWebhookController::class)->middleware('throttle:120,1');

Route::get('/privacidad', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/condiciones', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/cookies', [LegalController::class, 'cookies'])->name('legal.cookies');
Route::get('/auth/session', LoginSessionController::class)->name('login.session');
Route::get('/experiencia', [ExperienceController::class, 'demo'])->name('experience.demo');
Route::get('/experiencia/personaje', [ExperienceController::class, 'demoCharacter'])->name('experience.demo.character');
Route::get('/experiencia/actividad', [ActivityController::class, 'demo'])->name('experience.demo.activity');

// iOS may request this legacy icon path automatically.
Route::redirect('/apple-touch-icon-precomposed.png', '/apple-touch-icon.png', 302);

// Alta por QR: el alumno escanea el código de SU gimnasio, elige iniciar sesión
// (si ya tiene cuenta) o registrarse (entra siempre como cliente, ya con el
// gym_id correcto). Público, sin auth.
Route::get('/unirse/{inviteCode}', [GymJoinController::class, 'show'])->name('gym-join.show');
Route::post('/unirse/{inviteCode}', [GymJoinController::class, 'register'])->middleware('guest')->name('gym-join.register');

Route::get('dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'client') {
        return redirect()->route('client.dashboard');
    }

    return redirect('/admin');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', CommercialMembershipAccess::class.':required'])->group(function () {
    Route::get('/rutinas/desde-foto', [RoutinePhotoController::class, 'show'])->name('routines.photo.show');
    Route::post('/rutinas/desde-foto', [RoutinePhotoController::class, 'store'])->name('routines.photo.store');
});

Route::middleware(['auth', 'client', 'no-back'])
    ->prefix('app')
    ->name('client.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/personaje', [ExperienceController::class, 'character'])->name('character');
        Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
        Route::post('/activities', [ActivityController::class, 'store'])->middleware('throttle:60,1')->name('activities.store');
        Route::get('/activities/{activityLog}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/activities/{activityLog}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activityLog}', [ActivityController::class, 'destroy'])->name('activities.destroy');
        Route::get('/routines/active', [RoutineController::class, 'active'])->name('routines.active');
        Route::get('/routines/history', [RoutineController::class, 'history'])->name('routines.history');
        Route::get('/routines/{assignment}', [RoutineController::class, 'show'])->name('routines.show');
        Route::post('/routines/{assignment}/replay', [RoutineController::class, 'replay'])->name('routines.replay');
        Route::get('/routines/session/{assignment}', [RoutineController::class, 'session'])->name('routines.session');
        Route::get('/progress/exercise/{exercise}', [RoutineController::class, 'exerciseProgress'])
            ->name('progress.exercise');
        Route::post('/logs', [LogController::class, 'store'])->name('logs.store');
        Route::put('/logs/{log}', [LogController::class, 'update'])->name('logs.update');
        Route::delete('/logs/{log}', [LogController::class, 'destroy'])->name('logs.destroy');

        Route::get('/nutrition', [NutritionController::class, 'index'])->name('nutrition.index');
        Route::post('/nutrition/logs', [MealLogController::class, 'store'])->name('nutrition.logs.store');

        Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/account', [AccountController::class, 'updateProfile'])->name('account.update');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

        Route::get('/nutrition/photo', [MealPhotoController::class, 'show'])->name('nutrition.photo.show');
        Route::post('/nutrition/photo/analyze', [MealPhotoController::class, 'analyze'])->middleware('throttle:10,1')->name('nutrition.photo.analyze');
        Route::post('/nutrition/photo/confirm', [MealPhotoController::class, 'confirm'])->name('nutrition.photo.confirm');

        Route::get('/nutrition/foods/search', [FreeMealLogController::class, 'searchFoods'])->name('nutrition.foods.search');
        Route::post('/nutrition/free-logs', [FreeMealLogController::class, 'store'])->name('nutrition.free-logs.store');
        Route::delete('/nutrition/free-logs/{freeMealLog}', [FreeMealLogController::class, 'destroy'])->name('nutrition.free-logs.destroy');

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/fetch', [ChatController::class, 'fetch'])->name('chat.fetch');
        Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

        Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
        Route::get('/ai-chat/history', [AiChatController::class, 'history'])->name('ai-chat.history');
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

require __DIR__.'/settings.php';

Route::middleware(['auth', 'client', 'throttle:120,1'])->prefix('app')->group(function () {
    Route::get('/push/key', [RestAlertController::class, 'key']);
    Route::post('/push/subscriptions', [RestAlertController::class, 'subscribe']);
    Route::delete('/push/subscriptions', [RestAlertController::class, 'unsubscribe']);
    Route::put('/rest-alerts/{assignment}', [RestAlertController::class, 'schedule']);
});
