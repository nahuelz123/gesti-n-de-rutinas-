<?php

namespace App\Http\Controllers;

use App\Models\CommercialOperation;
use App\Models\CommercialReminderRun;
use App\Models\CommercialSettings;
use App\Models\User;
use App\Services\CommercialMembershipReminders;
use App\Services\CommercialReminderDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommercialReminderController extends Controller
{
    public function index(Request $request, CommercialMembershipReminders $reminders, CommercialReminderDispatcher $delivery)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $settings = CommercialSettings::values();

        return view('marketing.commercial.reminders', [
            'reminders' => $reminders->query()->paginate(20), 'enabled' => (bool) ($settings['reminders_enabled'] ?? false),
            'recipient' => User::whereKey($settings['reminder_recipient_id'] ?? 0)->where('role', 'super_admin')->first(),
            'mailReady' => $delivery->ready(),
            'latestRun' => CommercialReminderRun::where('recipient_user_id', $settings['reminder_recipient_id'] ?? $request->user()->id)->latest('id')->first(),
            'meta' => ['title' => 'Avisos de vencimiento | VisionFit', 'description' => 'Recordatorios privados de membresías.', 'path' => '/gestion-comercial/recordatorios'],
        ]);
    }

    public function configure(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $data = $request->validate(['reminders_enabled' => ['required', 'boolean'], 'confirmed' => ['accepted']]);
        $enabled = (bool) $data['reminders_enabled'];
        DB::transaction(function () use ($request, $enabled) {
            CommercialSettings::saveValues(['reminders_enabled' => $enabled, 'reminder_recipient_id' => $enabled ? $request->user()->id : null]);
            CommercialOperation::create(['public_id' => (string) Str::uuid(), 'actor_id' => $request->user()->id, 'actor_name' => $request->user()->name, 'action' => 'reminders_configured', 'request_hash' => hash('sha256', json_encode([$request->user()->id, $enabled])), 'payload' => ['enabled' => $enabled, 'recipient_user_id' => $enabled ? $request->user()->id : null], 'result' => ['saved' => true]]);
        });
        Cache::forget('visionfit.commercial-settings');

        return redirect('/gestion-comercial/recordatorios')->with('status', $enabled ? 'Resumen diario activado para el correo de tu cuenta. Requiere un transporte de correo configurado.' : 'Resumen por correo desactivado. La lista de avisos sigue disponible.');
    }
}
