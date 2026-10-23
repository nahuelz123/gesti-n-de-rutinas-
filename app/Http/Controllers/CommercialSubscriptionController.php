<?php

namespace App\Http\Controllers;

use App\Models\CommercialSubscription;
use App\Models\Gym;
use App\Models\User;
use App\Services\CommercialBilling;
use App\Services\MercadoPagoSubscriptions;
use App\Support\CommercialCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CommercialSubscriptionController extends Controller
{
    public function start(string $plan)
    {
        $selected = CommercialCatalog::plan($plan);
        abort_unless($selected['kind'] === 'software', 404);

        return view('marketing.start', ['plan' => $selected, 'meta' => ['title' => 'Empezá tu prueba | VisionFit', 'description' => 'Creá tu espacio y probá VisionFit sin tarjeta.', 'path' => '/empezar/'.$plan]]);
    }

    public function enroll(Request $request, string $plan)
    {
        abort_if($request->user(), 409, 'Ya tenés una cuenta abierta. Gestioná tu plan desde Mi suscripción o contactanos.');
        $selected = CommercialCatalog::plan($plan);
        abort_unless($selected['kind'] === 'software', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'business_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'privacy_accepted' => ['accepted'], 'terms_accepted' => ['accepted'],
        ]);
        $user = DB::transaction(function () use ($data, $plan, $selected) {
            $gym = Gym::create(['name' => $data['business_name'], 'plan' => $plan, 'active' => true, 'privacy_contact_name' => $data['business_name'], 'privacy_contact_email' => $data['email']]);
            $gym->forceFill(['billing_managed' => true])->save();
            $user = User::create(['gym_id' => $gym->id, 'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'admin']);
            foreach (['privacy', 'terms'] as $scope) {
                $user->consents()->create(['scope' => $scope, 'version' => config('legal.versions.'.$scope), 'granted_at' => now()]);
            }
            CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $user->id, 'gym_id' => $gym->id, 'kind' => 'software', 'plan_code' => $plan, 'amount_cents' => $selected['price'] * 100, 'currency' => 'ARS', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(config('commerce.trial_days')), 'terms_version' => config('legal.versions.terms')]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/mi-suscripcion');
    }

    public function index(Request $request, MercadoPagoSubscriptions $gateway)
    {
        $subscription = CommercialSubscription::where('user_id', $request->user()->id)->first();
        $students = $subscription?->gym?->users()->where('role', 'client')->where('commercial_access_enabled', true)->count() ?? 0;

        return view('marketing.subscription', compact('subscription', 'students') + ['ready' => $gateway->ready(), 'meta' => ['title' => 'Mi suscripción | VisionFit', 'description' => 'Gestioná tu plan y su renovación.', 'path' => '/mi-suscripcion']]);
    }

    private function owned(Request $request): CommercialSubscription
    {
        $subscription = CommercialSubscription::where('user_id', $request->user()->id)->first();
        abort_unless($subscription, 403);

        return $subscription;
    }

    public function checkout(Request $request, MercadoPagoSubscriptions $gateway)
    {
        $subscription = $this->owned($request);
        $request->validate(['renewal_accepted' => ['accepted']]);
        try {
            $url = DB::transaction(fn () => $gateway->checkout(CommercialSubscription::lockForUpdate()->findOrFail($subscription->id)));
        } catch (RequestException|ConnectionException $e) {
            return back()->withErrors(['billing' => 'No pudimos conectar con el servicio de cobros. No confirmamos ningún pago. Volvé a intentar o contactanos.']);
        }

        return redirect()->away($url);
    }

    public function cancel(Request $request, MercadoPagoSubscriptions $gateway)
    {
        $subscription = $this->owned($request);
        try {
            DB::transaction(fn () => $gateway->cancel(CommercialSubscription::lockForUpdate()->findOrFail($subscription->id)));
        } catch (RequestException|ConnectionException $e) {
            return back()->withErrors(['billing' => 'No pudimos confirmar la cancelación. Volvé a intentar o registrá una solicitud de baja.']);
        }

        return redirect('/mi-suscripcion')->with('status', 'Renovación cancelada. Conservás el acceso hasta finalizar el período abonado.');
    }

    public function reconcile(Request $request, CommercialBilling $billing, MercadoPagoSubscriptions $gateway)
    {
        $subscription = $this->owned($request);
        if (! $gateway->ready()) {
            return back()->withErrors(['billing' => 'La actualización de cobros todavía no está habilitada.']);
        }
        try {
            $billing->reconcile($subscription);
        } catch (RequestException|ConnectionException $e) {
            return back()->withErrors(['billing' => 'No pudimos actualizar los cobros. Conservamos el último estado confirmado.']);
        }

        return redirect('/mi-suscripcion')->with('status', 'Estado de cobros actualizado.');
    }
}
