<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordedTrainingSets
{
    public function between(User $client, CarbonImmutable $from, CarbonImmutable $until): Collection
    {
        // Joins retain the client's history even when an assignment or exercise was archived.
        return DB::table('exercise_logs')
            ->join('assignments', 'assignments.id', '=', 'exercise_logs.assignment_id')
            ->leftJoin('routine_day_exercises', 'routine_day_exercises.id', '=', 'exercise_logs.routine_day_exercise_id')
            ->leftJoin('exercises', 'exercises.id', '=', DB::raw('COALESCE(exercise_logs.exercise_id, routine_day_exercises.exercise_id)'))
            ->where('assignments.client_id', $client->id)
            ->whereBetween('exercise_logs.logged_at', [$from->toDateTimeString(), $until->toDateTimeString()])
            ->whereBetween('exercise_logs.reps', [1, 100])
            ->whereBetween('exercise_logs.set_number', [1, 20])
            ->where(fn ($query) => $query->whereNull('exercise_logs.weight')->orWhere('exercise_logs.weight', '>=', 0))
            ->select(['exercise_logs.*', 'routine_day_exercises.rest', 'routine_day_exercises.routine_day_id',
                'exercises.title', 'exercises.muscle_group', DB::raw('COALESCE(exercise_logs.exercise_id, routine_day_exercises.exercise_id) AS performed_exercise_id')])
            ->orderByDesc('exercise_logs.id')->get()
            ->filter(function ($log): bool {
                try {
                    $date = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $log->logged_at, config('app.timezone', 'UTC'));
                } catch (\InvalidArgumentException) {
                    return false;
                }
                if (! $date || $date->toDateTimeString() !== $log->logged_at) {
                    return false;
                }
                $log->logged_at = $date;

                return true;
            })
            ->unique(fn ($log) => implode(':', [$log->assignment_id, $log->routine_day_exercise_id,
                $log->set_number, $log->logged_at->toDateString()]))->values();
    }
}
