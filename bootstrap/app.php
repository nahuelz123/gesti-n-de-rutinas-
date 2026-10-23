<?php

use App\Http\Middleware\CommercialConfiguration;
use App\Http\Middleware\CommercialIndexing;
use App\Http\Middleware\CommercialMembershipAccess;
use App\Http\Middleware\EnsureClientRole;
use App\Http\Middleware\EnsureNotClientForFilament;
use App\Http\Middleware\PreventBackNavigation;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['webhooks/mercadopago']);
        $middleware->append(CommercialIndexing::class);
        $middleware->appendToGroup('web', [CommercialConfiguration::class, CommercialMembershipAccess::class]);
        $middleware->alias([
            'filament.access' => EnsureNotClientForFilament::class,
            'client' => EnsureClientRole::class,
            'no-back' => PreventBackNavigation::class,
            'guest' => RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response) {
            $request = request();

            if ($response->getStatusCode() === 419 && $request->routeIs('login.store') && ! $request->expectsJson()) {
                return redirect()->route('login')
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => 'La sesión del formulario venció. Volvé a ingresar tu contraseña para continuar.']);
            }

            return $response;
        });
    })->create();
