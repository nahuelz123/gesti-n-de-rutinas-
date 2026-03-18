<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotClientForFilament
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // solo pueden entrar al panel: super_admin, admin, coach
        if (! $user || ! in_array($user->role, ['super_admin', 'admin', 'coach'])) {
            abort(403);
        }

        return $next($request);
    }
}
