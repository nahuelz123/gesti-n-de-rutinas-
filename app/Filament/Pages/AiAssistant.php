<?php

namespace App\Filament\Pages;

use App\Models\AiConversation;
use App\Models\Assignment;
use App\Models\ExerciseLog;
use App\Models\User;
use App\Services\CoachAiTools;
use App\Services\DeepSeekClient;
use App\Services\NutritionCalculator;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AiAssistant extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Asistente IA';

    protected static ?string $title = 'Asistente IA';

    protected string $view = 'filament.pages.ai-assistant';

    public ?int $selectedClientId = null;

    public string $message = '';

    public Collection $history;

    public function mount(): void
    {
        $this->history = collect();
        $this->selectedClientId = $this->getClients()->first()?->id;
        $this->loadHistory();
    }

    public function getClients(): Collection
    {
        $user = Auth::user();

        return User::query()
            ->where('role', 'client')
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id))
            ->orderBy('name')
            ->get();
    }

    public function selectClient(int $clientId): void
    {
        $this->selectedClientId = $clientId;
        $this->loadHistory();
    }

    private function loadHistory(): void
    {
        if (! $this->selectedClientId) {
            $this->history = collect();

            return;
        }

        $this->history = AiConversation::query()
            ->where('user_id', Auth::id())
            ->where('client_id', $this->selectedClientId)
            ->orderBy('id')
            ->get();
    }

    public function send(DeepSeekClient $deepSeek): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        if (! $this->selectedClientId) {
            return;
        }

        $coach = Auth::user();
        $client = User::find($this->selectedClientId);

        if (! $client) {
            return;
        }

        AiConversation::create([
            'user_id' => $coach->id,
            'client_id' => $client->id,
            'role' => 'user',
            'content' => $this->message,
        ]);

        $this->message = '';

        $systemPrompt = $this->buildSystemPrompt($coach, $client);

        $recent = AiConversation::query()
            ->where('user_id', $coach->id)
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->take(12)
            ->get()
            ->sortBy('id')
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();

        $result = $deepSeek->chatWithTools($systemPrompt, $recent, CoachAiTools::definitions());

        if ($result === null) {
            $reply = 'No pude conectarme con el asistente en este momento. Probá de nuevo en un rato.';
        } elseif (! empty($result['tool_calls'])) {
            $toolResults = [];

            foreach ($result['tool_calls'] as $call) {
                $fnName = $call['function']['name'] ?? '';
                $argsJson = $call['function']['arguments'] ?? '{}';
                $args = json_decode($argsJson, true) ?? [];

                $resultText = CoachAiTools::execute($fnName, $args, $coach, $client);

                $toolResults[] = [
                    'tool_call_id' => $call['id'] ?? '',
                    'role' => 'tool',
                    'content' => $resultText,
                ];
            }

            $followUpMessages = array_merge(
                $recent,
                [[
                    'role' => 'assistant',
                    'content' => $result['content'] ?? '',
                    'tool_calls' => $result['tool_calls'],
                ]],
                $toolResults
            );

            $followUp = $deepSeek->chatWithTools($systemPrompt, $followUpMessages, []);
            $reply = $followUp['content'] ?? implode("\n", array_column($toolResults, 'content'));

            Notification::make()
                ->title('El asistente ejecutó una acción')
                ->body(implode(' / ', array_column($toolResults, 'content')))
                ->success()
                ->send();
        } else {
            $reply = $result['content'] ?? 'No pude generar una respuesta.';
        }

        AiConversation::create([
            'user_id' => $coach->id,
            'client_id' => $client->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        $this->loadHistory();
    }

    public function newChat(): void
    {
        if (! $this->selectedClientId) {
            return;
        }

        AiConversation::query()
            ->where('user_id', Auth::id())
            ->where('client_id', $this->selectedClientId)
            ->delete();

        $this->loadHistory();
    }

    private function buildSystemPrompt(User $coach, User $client): string
    {
        $lines = [
            "Sos el asistente virtual de VisionFit, una app de gestión de gimnasios.",
            "Estás ayudando a {$coach->name}, que es coach/admin del gimnasio, a analizar a un cliente puntual.",
            "Respondé en español rioplatense (Argentina), de forma breve y accionable — el coach está apurado.",
            "Podés usar las herramientas disponibles (enviar notificación, crear y asignar rutina) cuando el coach te lo pida explícitamente. No las uses si solo te está preguntando algo, sin pedir una acción.",
            "Después de ejecutar una herramienta, confirmale al coach en una frase qué hiciste.",
            "No inventes datos que no te haya dado el sistema.",
        ];

        $lines[] = "Cliente: {$client->name} ({$client->email}).";

        $activeAssignment = Assignment::query()
            ->with(['routine.days.exercises.exercise'])
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();

        if ($activeAssignment) {
            $routine = $activeAssignment->routine;
            $lines[] = "Rutina activa: \"{$routine->title}\".";

            foreach ($routine->days as $day) {
                $exNames = $day->exercises->map(fn ($e) => $e->exercise?->title)->filter()->implode(', ');
                if ($exNames) {
                    $lines[] = "- Día \"{$day->title}\": {$exNames}";
                }
            }

            $recentLogs = ExerciseLog::query()
                ->where('assignment_id', $activeAssignment->id)
                ->with('routineDayExercise.exercise')
                ->latest('logged_at')
                ->take(15)
                ->get();

            if ($recentLogs->isNotEmpty()) {
                $lines[] = 'Últimos registros de entrenamiento (ejercicio: peso x reps):';
                foreach ($recentLogs as $log) {
                    $exTitle = $log->routineDayExercise?->exercise?->title ?? '?';
                    $lines[] = "- {$exTitle}: {$log->weight}kg x {$log->reps} reps ({$log->logged_at->format('d/m')})";
                }
            }
        } else {
            $lines[] = 'No tiene rutina activa.';
        }

        $dietAssignment = NutritionCalculator::activeAssignmentFor($client->id);

        if ($dietAssignment) {
            $plan = $dietAssignment->dietPlan;
            $goalLabel = NutritionCalculator::$goalLabels[$plan->goal] ?? 'sin objetivo definido';
            $lines[] = "Plan de dieta activo: \"{$plan->title}\" (objetivo: {$goalLabel}"
                .($plan->target_calories ? ", {$plan->target_calories} kcal/día" : '').').';
        } else {
            $lines[] = 'No tiene plan de dieta activo.';
        }

        return implode("\n", $lines);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
    }
}
