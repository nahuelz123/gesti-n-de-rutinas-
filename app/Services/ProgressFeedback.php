<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BodyMeasurement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ProgressFeedback
{
    public function __construct(private RecordedTrainingSets $sets) {}

    public function forClient(User $client): array
    {
        $now = CarbonImmutable::now();
        $weekStart = $now->subDays(6)->startOfDay();
        $logs = $this->sets->between($client, $now->subDays(27)->startOfDay(), $now);
        $week = $logs->filter(fn ($log) => $log->logged_at->greaterThanOrEqualTo($weekStart));
        $days = $week->map(fn ($log) => $log->logged_at->toDateString())->unique()->count();
        $improvements = $this->improvements($logs, $weekStart);
        $positives = [];
        $suggestions = [];
        if ($days > 0) {
            $positives[] = ['code' => 'training_recorded', 'title' => 'Tu entrenamiento quedó registrado',
                'text' => $days.' '.($days === 1 ? 'día' : 'días').' de entrenamiento y '.$week->count().' series en los últimos 7 días.'];
        } else {
            $suggestions[] = ['code' => 'start_logging', 'text' => 'Registrá tus series al entrenar para poder comparar tus próximas sesiones.',
                'url' => route('client.routines.active'), 'link' => 'Ir a mi rutina'];
        }
        foreach ($improvements as $improvement) {
            $positives[] = ['code' => 'comparable_improvement', 'title' => $improvement['title'],
                'text' => $improvement['reps_gain'] > 0
                    ? $improvement['reps_gain'].' repeticiones más con la misma carga registrada ('.$improvement['weight_label'].').'
                    : $improvement['weight_gain'].' kg más con las mismas '.$improvement['reps'].' repeticiones.'];
        }

        $weight = BodyMeasurement::query()->where('client_id', $client->id)->whereBetween('weight', [20, 400])
            ->whereDate('measured_at', '<=', $now->toDateString())->orderByDesc('measured_at')->orderByDesc('id')->first();
        if (! $weight || $weight->measured_at->lt($now->subDays(30)->startOfDay())) {
            $suggestions[] = ['code' => $weight ? 'body_weight_stale' : 'body_weight_missing',
                'text' => $weight ? 'Actualizá tu peso corporal para que las nuevas estimaciones de gasto usen un dato reciente.'
                    : 'Cargá tu peso corporal para completar las estimaciones de gasto.',
                'url' => route('client.progress.index').'#measurement-form', 'link' => 'Registrar peso'];
        }
        if ($days > 0 && $improvements === []) {
            $suggestions[] = ['code' => 'comparable_sessions',
                'text' => 'Al repetir un ejercicio, compará carga y repeticiones con la misma máquina o variante. Con menos repeticiones, más kilos por sí solos no indican una mejora comparable.',
                'url' => route('client.routines.active'), 'link' => 'Ver mis ejercicios'];
        }
        if ($days > 0 && ! ActivityLog::query()->where('client_id', $client->id)->where('category', 'cardio')
            ->whereDate('performed_on', '>=', $weekStart->toDateString())->whereDate('performed_on', '<=', $now->toDateString())->exists()) {
            $suggestions[] = ['code' => 'optional_cardio', 'text' => 'Si también hiciste cardio o caminaste, cargalo para completar el resumen de actividad.',
                'url' => route('client.activities.index'), 'link' => 'Registrar cardio o pasos'];
        }

        return ['training_days' => $days, 'sets' => $week->count(), 'improvements' => $improvements,
            'positives' => $positives, 'suggestions' => $suggestions];
    }

    private function improvements(Collection $logs, CarbonImmutable $weekStart): array
    {
        $improvements = [];
        foreach ($logs->filter(fn ($log) => $log->performed_exercise_id !== null)->groupBy('performed_exercise_id') as $exerciseId => $exerciseLogs) {
            $sessions = $exerciseLogs->groupBy(fn ($log) => $log->logged_at->toDateString())->sortKeysDesc()->values();
            if ($sessions->count() < 2 || $sessions[0]->first()->logged_at->lt($weekStart)) {
                continue;
            }
            // Compare one best set from each of the two latest days, using the performed variant.
            $best = fn ($session) => $session->sort(fn ($a, $b) => ((float) $b->weight <=> (float) $a->weight) ?: ((int) $b->reps <=> (int) $a->reps))->first();
            $current = $best($sessions[0]);
            $previous = $best($sessions[1]);
            if ($current->weight === null || $previous->weight === null) {
                continue;
            }
            $repsGain = (float) $current->weight === (float) $previous->weight ? max(0, (int) $current->reps - (int) $previous->reps) : 0;
            $weightGain = (int) $current->reps === (int) $previous->reps ? max(0, round((float) $current->weight - (float) $previous->weight, 2)) : 0;
            if ($repsGain === 0 && $weightGain <= 0) {
                continue;
            }
            $improvements[] = ['exercise_id' => (int) $exerciseId, 'title' => $current->title ?? 'Ejercicio registrado',
                'reps_gain' => $repsGain, 'weight_gain' => $weightGain, 'reps' => (int) $current->reps,
                'weight_label' => (float) $current->weight === 0.0 ? 'sin carga adicional' : number_format((float) $current->weight, 2, ',', '.').' kg'];
            if (count($improvements) === 3) {
                break;
            }
        }

        return $improvements;
    }
}
