<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoachHelpController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->user()->role === 'client', 403);

        $coach = $request->user()->myCoach();

        return view('client.coach-help', compact('coach'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'client', 403);

        $validated = $request->validate([
            'reason' => ['required', Rule::in(['tecnica', 'reemplazo', 'consulta', 'dolor', 'otro'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $client = $request->user();
        $coach = $client->myCoach();

        if (! $coach) {
            return back()->withInput()->withErrors([
                'coach' => 'Todavía no tenés un coach asignado para recibir solicitudes de ayuda.',
            ]);
        }

        $labels = [
            'tecnica' => 'Técnica de un ejercicio',
            'reemplazo' => 'Reemplazar un ejercicio',
            'consulta' => 'Consulta general',
            'dolor' => 'Molestia o dolor',
            'otro' => 'Otro motivo',
        ];

        $details = trim((string) ($validated['message'] ?? ''));
        $body = $client->name.' necesita ayuda: '.$labels[$validated['reason']].'.';
        if ($details !== '') {
            $body .= ' Mensaje: '.$details;
        }

        FilamentNotification::make()
            ->title('Solicitud de ayuda de un alumno')
            ->body($body)
            ->warning()
            ->persistent()
            ->sendToDatabase($coach);

        return redirect()->route('client.coach-help.create')
            ->with('success', 'Tu solicitud fue enviada al coach dentro de VisionFit.');
    }
}
