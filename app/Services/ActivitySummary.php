<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\CarbonImmutable;

class ActivitySummary
{
    public static function emptyTotals(): array
    {
        return ['cardio_kcal' => 0.0, 'strength_kcal' => 0.0, 'total_kcal' => 0.0,
            'cardio_minutes' => 0, 'strength_minutes' => 0, 'record_count' => 0];
    }

    public function forClient(User $client, CarbonImmutable $date): array
    {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[$date->startOfWeek()->addDays($i)->toDateString()] = self::emptyTotals();
        }
        $rows = ActivityLog::query()->where('client_id', $client->id)
            ->whereBetween('performed_on', [$date->startOfWeek()->startOfDay(), $date->endOfWeek()->endOfDay()])
            ->where('performed_on', '<=', now()->endOfDay())
            ->selectRaw('performed_on, category, SUM(active_kcal) AS kcal, SUM(duration_minutes) AS minutes, COUNT(*) AS records')
            ->groupBy('performed_on', 'category')->get();
        foreach ($rows as $row) {
            $day = $row->performed_on->toDateString();
            if (! isset($days[$day]) || ! in_array($row->category, ['cardio', 'strength'], true)) {
                continue;
            }
            $days[$day][$row->category.'_kcal'] = (float) $row->kcal;
            $days[$day][$row->category.'_minutes'] = (int) $row->minutes;
            $days[$day]['record_count'] += (int) $row->records;
            $days[$day]['total_kcal'] = round($days[$day]['cardio_kcal'] + $days[$day]['strength_kcal'], 2);
        }
        $week = self::emptyTotals();
        foreach ($days as $totals) {
            foreach ($week as $key => $value) {
                $week[$key] += $totals[$key];
            }
        }
        foreach (['cardio_kcal', 'strength_kcal', 'total_kcal'] as $key) {
            $week[$key] = round($week[$key], 2);
        }

        return ['daySummary' => $days[$date->toDateString()], 'weekSummary' => $week, 'weekDays' => $days];
    }
}
