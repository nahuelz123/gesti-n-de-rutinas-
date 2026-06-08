<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotClientForFilament
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (! $user) {
            return $next($request);
        }
        
        if (! in_array($user->role, ['super_admin', 'admin', 'coach'])) {
            abort(403);
        }
        
        return $next($request);
    }
}
