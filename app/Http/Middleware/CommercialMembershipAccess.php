<?php

namespace App\Http\Middleware;

use App\Models\CommercialSubscription;
use Closure;
use Illuminate\Http\Request;

class CommercialMembershipAccess
{
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        if ($scope !== 'required' && ! $request->is('app', 'app/*', 'admin', 'admin/*', 'livewire/*')) {
            return $next($request);
        }
        $user = $request->user();
        if (! $user || $user->role === 'super_admin') {
            return $next($request);
        }
        if ($user->role === 'client') {
            $individual = CommercialSubscription::where('user_id', $user->id)->where('kind', 'coaching')->first();
            if ($individual && ! $individual->hasAccess()) {
                if ($request->expectsJson()) {
                    abort(403, 'Revisá el estado de tu suscripción.');
                }

                return redirect('/mi-suscripcion');
            }
        }
        if (! $user->gym?->billing_managed) {
            return $next($request);
        }
        $subscription = CommercialSubscription::where('gym_id', $user->gym_id)->first();
        if ($subscription?->hasAccess() && $user->commercial_access_enabled !== false) {
            return $next($request);
        }
        if ($request->expectsJson()) {
            abort(403, 'El acceso a este espacio no está habilitado.');
        }
        if ($subscription?->user_id === $user->id) {
            return redirect('/mi-suscripcion');
        }

        return response()->view('marketing.access-unavailable', ['meta' => ['title' => 'Acceso al espacio | VisionFit', 'description' => 'Consultá el estado de tu acceso con tu profesional.', 'path' => '/app']], 403);
    }
}
