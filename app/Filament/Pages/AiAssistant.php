<?php

namespace App\Filament\Pages;

use App\Models\Assignment;
use App\Models\ExerciseLog;
use App\Models\User;
use App\Services\DeepSeekClient;
use App\Services\NutritionCalculator;
use BackedEnum;
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

    /** @var array<int, array{role: string, content: string}> */
    public array $history = [];

    public function mount(): void
    {
        $this->selectedClientId = $this->getClients()->first()?->id;
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
        $this->history = [];
    }

    public function send(DeepSeekClient $deepSeek): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        if (! $this->selectedClientId) {
            return;
        }

        $this->history[] = ['role' => 'user', 'content' => $this->message];
        $this->message = '';

        $systemPrompt = $this->buildSystemPrompt();

        $recent = array_slice($this->history, -12);

        $reply = $deepSeek->chat($systemPrompt, $recent)
            ?? 'No pude conectarme con el asistente en este momento. Probá de nuevo en un rato.';

        $this->history[] = ['role' => 'assistant', 'content' => $reply];
    }

    public function newChat(): void
    {
        $this->history = [];
    }

    private function buildSystemPrompt(): string
    {
        $coach = Auth::user();
        $client = User::find($this->selectedClientId);

        $lines = [
            "Sos el asistente virtual de VisionFit, una app de gestión de gimnasios.",
            "Estás ayudando a {$coach->name}, que es coach/admin del gimnasio, a analizar a un cliente puntual.",
            "Respondé en español rioplatense (Argentina), de forma breve y accionable — el coach está apurado.",
            "Podés sugerir ajustes de rutina o dieta, pero aclará que la decisión final es del coach.",
            "No inventes datos que no te haya dado el sistema.",
        ];

        if (! $client) {
            $lines[] = 'No hay un cliente seleccionado.';

            return implode("\n", $lines);
        }

        $lines[] = "Cliente: {$client->name} ({$client->email}).";

        // Rutina activa + progreso reciente
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
                ->whereHas('routineDayExercise', fn ($q) => $q->whereHas('exercise'))
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

        // Plan de dieta activo
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
