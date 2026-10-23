<?php

namespace App\Http\Controllers;

use App\Models\CommercialOperation;
use App\Models\CommercialSettings;
use App\Models\Gym;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommercialSettingsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $values = [];
        foreach (CommercialSettings::CONFIG_KEYS as $key => $config) {
            $values[$key] = config($config);
        }

        return view('marketing.commercial.settings', compact('values') + ['gyms' => Gym::orderBy('name')->get(), 'meta' => ['title' => 'Configuración comercial | VisionFit', 'description' => 'Datos del servicio y cobros manuales.', 'path' => '/gestion-comercial/configuracion']]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $data = $request->validate([
            'operator_name' => ['nullable', 'string', 'max:150'], 'contact_email' => ['nullable', 'email', 'max:190'],
            'operator_address' => ['nullable', 'string', 'max:240'], 'tax_id' => ['nullable', 'string', 'max:35'],
            'manual_payment_instructions' => ['nullable', 'string', 'max:2000'], 'coaching_gym_id' => ['nullable', 'integer', 'exists:gyms,id'],
        ]);
        DB::transaction(function () use ($data, $request) {
            CommercialSettings::saveValues($data);
            CommercialOperation::create(['public_id' => (string) Str::uuid(), 'actor_id' => $request->user()->id, 'actor_name' => $request->user()->name, 'action' => 'settings_updated', 'request_hash' => hash('sha256', json_encode($data)), 'payload' => ['fields' => array_keys($data)], 'result' => ['saved' => true]]);
        });
        Cache::forget('visionfit.commercial-settings');

        return redirect('/gestion-comercial/configuracion')->with('status', 'Configuración guardada. No se configuraron credenciales ni se movió dinero.');
    }
}
