<?php

namespace App\Services;

use App\Models\BodyMeasurement;
use App\Models\User;
use App\Support\RestDuration;
use Carbon\CarbonImmutable;

class AutomaticStrengthEnergy
{
    public function __construct(private RecordedTrainingSets $sets, private ActivityEnergy $energy) {}

    public function forClient(User $client, CarbonImmutable $date): array
    {
        $start = $date->startOfWeek();
        $until = $date->endOfWeek()->min(CarbonImmutable::now());
        $logs = $this->sets->between($client, $start, $until)
            ->reject(fn ($log) => strtolower(trim((string) $log->muscle_group)) === 'cardio');
        if ($logs->isEmpty()) {
            return [];
        }

        $weights = BodyMeasurement::query()->where('client_id', $client->id)->whereBetween('weight', [20, 400]);
        $prior = (clone $weights)->whereDate('measured_at', '<', $start->toDateString())->orderByDesc('measured_at')->orderByDesc('id')->first();
        $measurements = (clone $weights)->whereDate('measured_at', '>=', $start->toDateString())->whereDate('measured_at', '<=', $until->toDateString())
            ->orderBy('measured_at')->orderBy('id')->get();
        if ($prior) {
            $measurements->prepend($prior);
        }

        $estimates = [];
        foreach ($logs->groupBy(fn ($log) => $log->logged_at->toDateString()) as $day => $daySets) {
            $seconds = 0;
            foreach ($daySets->groupBy(fn ($log) => $log->assignment_id.':'.$log->routine_day_id) as $session) {
                $ordered = $session->sortBy('logged_at')->values();
                foreach ($ordered as $index => $set) {
                    // Model assumption, not a measured tempo: 3 seconds per recorded repetition.
                    $seconds += (int) $set->reps * 3;
                    if ($index < $ordered->count() - 1) {
                        $seconds += min(300, RestDuration::seconds($set->rest) ?? 90);
                    }
                }
            }
            $minutes = min(600, max(1, (int) ceil($seconds / 60)));
            $measurement = $measurements->last(fn ($row) => $row->measured_at->toDateString() <= $day);
            $weight = $measurement ? (float) $measurement->weight : null;
            $estimates[$day] = ['sets' => $daySets->count(), 'duration_minutes' => $minutes, 'body_weight' => $weight,
                'active_kcal' => $weight === null ? null : $this->energy->estimate('strength_general', $minutes, $weight)['active_kcal']];
        }

        return $estimates;
    }
}
