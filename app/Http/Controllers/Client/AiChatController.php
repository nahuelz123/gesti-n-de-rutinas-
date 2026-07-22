<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\DeepSeekClient;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    private const SESSION_KEY = 'ai_chat_history';
    private const MAX_HISTORY = 12; // mensajes (user+assistant) que se mandan de contexto

    public function index(Request $request)
    {
        $history = session(self::SESSION_KEY, []);

        return view('client.ai-chat', ['history' => $history]);
    }

    public function send(Request $request, DeepSeekClient $deepSeek)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $history = session(self::SESSION_KEY, []);
        $history[] = ['role' => 'user', 'content' => $data['message']];

        $systemPrompt = $this->buildSystemPrompt($user);

        // Solo mandamos los últimos N mensajes como contexto (para no gastar tokens de más)
        $recent = array_slice($history, -self::MAX_HISTORY);

        $reply = $deepSeek->chat($systemPrompt, $recent);

        if ($reply === null) {
            $reply = 'Uy, no pude conectarme con el asistente en este momento. Probá de nuevo en un rato.';
        }

        $history[] = ['role' => 'assistant', 'content' => $reply];

        session([self::SESSION_KEY => $history]);

        return back();
    }

    public function reset(Request $request)
    {
        session()->forget(self::SESSION_KEY);

        return back();
    }

    private function buildSystemPrompt($user): string
    {
        $lines = [
            "Sos el asistente virtual de VisionFit, una app de gestión de gimnasios.",
            "Estás hablando con {$user->name}, un cliente del gimnasio.",
            "Respondé siempre en español rioplatense (Argentina), de forma breve, clara y motivadora.",
            "No des consejos médicos ni de nutrición clínica; para eso siempre sugerí consultar a su coach o a un profesional.",
            "No inventes datos de la rutina o dieta del cliente que no te haya dado el sistema.",
        ];

        // Rutina activa
        $activeAssignment = Assignment::query()
            ->with(['routine.days.exercises.exercise'])
            ->where('client_id', $user->id)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();

        if ($activeAssignment) {
            $routine = $activeAssignment->routine;
            $lines[] = "Rutina activa del cliente: \"{$routine->title}\".";

            foreach ($routine->days as $day) {
                $exerciseNames = $day->exercises->map(fn ($e) => $e->exercise?->title)->filter()->implode(', ');
                if ($exerciseNames) {
                    $lines[] = "- Día \"{$day->title}\": {$exerciseNames}";
                }
            }
        } else {
            $lines[] = 'El cliente no tiene una rutina activa asignada.';
        }

        // Plan de dieta activo
        $dietAssignment = NutritionCalculator::activeAssignmentFor($user->id);

        if ($dietAssignment) {
            $plan = $dietAssignment->dietPlan;
            $goalLabel = NutritionCalculator::$goalLabels[$plan->goal] ?? 'sin objetivo definido';
            $lines[] = "Plan de dieta activo: \"{$plan->title}\" (objetivo: {$goalLabel}"
                .($plan->target_calories ? ", {$plan->target_calories} kcal/día" : '').').';

            $todayDay = NutritionCalculator::planDayFor($dietAssignment, NutritionCalculator::todayKey());
            if ($todayDay) {
                $meals = $todayDay->recipes->map(fn ($dpr) => $dpr->recipe?->title)->filter()->implode(', ');
                if ($meals) {
                    $lines[] = "Comidas planificadas para hoy: {$meals}";
                }
            }
        } else {
            $lines[] = 'El cliente no tiene un plan de dieta activo.';
        }

        return implode("\n", $lines);
    }
}
