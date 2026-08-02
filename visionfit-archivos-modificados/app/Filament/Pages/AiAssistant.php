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

    // null = "Chat general" (sin cliente puntual seleccionado)
    public ?int $selectedClientId = null;

    public string $message = '';

    public string $clientSearch = '';

    public Collection $history;

    /** @var array{type: string, client_id: ?int, client_name: ?string, args: array}|null */
    public ?array $pendingAction = null;

    public function mount(): void
    {
        $this->history = collect();
        // Arranca en el chat general, no en un cliente puntual.
        $this->selectedClientId = null;
        $this->loadHistory();
    }

    public function getClients(): Collection
    {
        $user = Auth::user();

        $query = User::query()
            ->where('role', 'client')
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id));

        if (trim($this->clientSearch) !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->clientSearch.'%')
                ->orWhere('email', 'like', '%'.$this->clientSearch.'%'));
        }

        return $query->orderBy('name')->get();
    }

    public function selectClient(?int $clientId): void
    {
        $this->selectedClientId = $clientId;
        $this->pendingAction = null;
        $this->loadHistory();
    }

    private function loadHistory(): void
    {
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

        $coach = Auth::user();
        $client = $this->selectedClientId ? User::find($this->selectedClientId) : null;

        AiConversation::create([
            'user_id' => $coach->id,
            'client_id' => $client?->id,
            'role' => 'user',
            'content' => $this->message,
        ]);

        $this->message = '';

        $generalChat = $client === null;
        $systemPrompt = $this->buildSystemPrompt($coach, $client, $generalChat);

        $recent = AiConversation::query()
            ->where('user_id', $coach->id)
            ->where('client_id', $client?->id)
            ->orderByDesc('id')
            ->take(12)
            ->get()
            ->sortBy('id')
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();

        $result = $deepSeek->chatWithTools($systemPrompt, $recent, CoachAiTools::definitions($generalChat));

        if ($result === null) {
            $reply = 'No pude conectarme con el asistente en este momento. Probá de nuevo en un rato.';
        } elseif (! empty($result['tool_calls'])) {
            $toolResults = [];

            foreach ($result['tool_calls'] as $call) {
                $fnName = $call['function']['name'] ?? '';
                $argsJson = $call['function']['arguments'] ?? '{}';
                $args = json_decode($argsJson, true) ?? [];

                $toolOutcome = CoachAiTools::execute($fnName, $args, $coach, $client);

                if ($toolOutcome['pending']) {
                    // Solo dejamos una propuesta pendiente a la vez.
                    $this->pendingAction = $toolOutcome['pending'];
                }

                $toolResults[] = [
                    'tool_call_id' => $call['id'] ?? '',
                    'role' => 'tool',
                    'content' => $toolOutcome['content'],
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
        } else {
            $reply = $result['content'] ?? 'No pude generar una respuesta.';
        }

        AiConversation::create([
            'user_id' => $coach->id,
            'client_id' => $client?->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        $this->loadHistory();
    }

    public function confirmPendingAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $coach = Auth::user();
        $pending = $this->pendingAction;
        $summary = CoachAiTools::confirmAction($pending, $coach);

        AiConversation::create([
            'user_id' => $coach->id,
            'client_id' => $pending['client_id'],
            'role' => 'assistant',
            'content' => "✅ {$summary}",
        ]);

        Notification::make()
            ->title('Acción confirmada')
            ->body($summary)
            ->success()
            ->send();

        $this->pendingAction = null;
        $this->loadHistory();
    }

    public function cancelPendingAction(): void
    {
        $this->pendingAction = null;

        Notification::make()
            ->title('Propuesta descartada')
            ->body('Podés pedirle a la IA una versión distinta cuando quieras.')
            ->send();
    }

    public function newChat(): void
    {
        AiConversation::query()
            ->where('user_id', Auth::id())
            ->where('client_id', $this->selectedClientId)
            ->delete();

        $this->pendingAction = null;
        $this->loadHistory();
    }

    private function buildSystemPrompt(User $coach, ?User $client, bool $generalChat): string
    {
        $lines = [
            'Sos el asistente virtual de VisionFit, una app de gestión de gimnasios.',
            "Estás ayudando a {$coach->name}, que es coach/admin del gimnasio.",
            'Respondé en español rioplatense (Argentina), de forma breve y accionable — el coach está apurado.',
            'IMPORTANTE sobre acciones que crean o modifican datos (rutinas, ediciones de rutina, planes de dieta, recetas): NUNCA se ejecutan solas. Cuando el coach te pida crear o editar algo, usá las herramientas "propose_*" correspondientes, que solo arman una propuesta visible para que el coach la apruebe a mano. No digas que "ya quedó creada/asignada" hasta que el coach la haya aprobado explícitamente (vas a recibir un mensaje de confirmación cuando eso pase).',
            'Si el coach pide cambios sobre una propuesta que ya armaste, volvé a llamar la herramienta propose_* correspondiente con la versión corregida completa (no un parche).',
            'send_notification es la única acción que SÍ se ejecuta directo, sin aprobación previa.',
            'No inventes datos que no te haya dado el sistema.',
        ];

        if ($generalChat) {
            $lines[] = 'Estás en el CHAT GENERAL: no hay un cliente puntual seleccionado. El coach te puede pedir cosas para clientes específicos nombrándolos. Antes de proponer o hacer algo para un cliente, asegurate de saber a cuál se refiere (usá search_client si hay dudas o nombres parecidos) y pasá client_query en las herramientas que lo piden.';

            return implode("\n", $lines);
        }

        $lines[] = "Estás analizando puntualmente a este cliente: {$client->name} ({$client->email}).";

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

        $lastMeasurement = $client->bodyMeasurements()->first();

        if ($lastMeasurement) {
            $lines[] = "Última medición corporal cargada ({$lastMeasurement->measured_at->format('d/m/Y')}):";
            if ($lastMeasurement->weight) $lines[] = "- Peso: {$lastMeasurement->weight}kg";
            if ($lastMeasurement->body_fat_percentage) $lines[] = "- % Grasa corporal: {$lastMeasurement->body_fat_percentage}%";
            if ($lastMeasurement->waist) $lines[] = "- Cintura: {$lastMeasurement->waist}cm";
            if ($lastMeasurement->chest) $lines[] = "- Pecho/busto: {$lastMeasurement->chest}cm";
            if ($lastMeasurement->hip) $lines[] = "- Cadera: {$lastMeasurement->hip}cm";
            if ($lastMeasurement->arm) $lines[] = "- Brazo: {$lastMeasurement->arm}cm";
            if ($lastMeasurement->thigh) $lines[] = "- Muslo: {$lastMeasurement->thigh}cm";
        } else {
            $lines[] = 'El cliente todavía no cargó ninguna medición corporal.';
        }

        return implode("\n", $lines);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
    }
}
