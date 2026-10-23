<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CommercialIndexing
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (! config('commerce.indexable') || ! $request->routeIs('home', 'marketing.*', 'legal.*') || $request->has('gym')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ($request->is('gestion-comercial*', 'mi-suscripcion*', 'contacto/recibido')) $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
