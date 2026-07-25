<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AiConversation;
use App\Services\DeepSeekClient;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    private const MAX_HISTORY = 12; // mensajes de contexto que se mandan al modelo

    public function index(Request $request)
    {
        $history = AiConversation::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('client_id')
            ->orderBy('id')
            ->get();

        return view('client.ai-chat', ['history' => $history]);
    }

    public function send(Request $request, DeepSeekClient $deepSeek)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        AiConversation::create([
            'user_id' => $user->id,
            'client_id' => null,
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $systemPrompt = $this->buildSystemPrompt($user);

        $recent = AiConversation::query()
            ->where('user_id', $user->id)
            ->whereNull('client_id')
            ->orderByDesc('id')
            ->take(self::MAX_HISTORY)
            ->get()
            ->sortBy('id')
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();

        $reply = $deepSeek->chat($systemPrompt, $recent);

        if ($reply === null) {
            $reply = 'Uy, no pude conectarme con el asistente en este momento. Probá de nuevo en un rato.';
        }

        AiConversation::create([
            'user_id' => $user->id,
            'client_id' => null,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return back();
    }

    public function reset(Request $request)
    {
        AiConversation::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('client_id')
            ->delete();

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
