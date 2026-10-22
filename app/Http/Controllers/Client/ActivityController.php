<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLogRequest;
use App\Models\ActivityLog;
use App\Models\BodyMeasurement;
use App\Models\User;
use App\Services\ActivityEnergy;
use App\Services\ActivitySummary;
use App\Support\ActivityCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today']]);

        return $this->page($request->user(), CarbonImmutable::parse($data['date'] ?? today()->toDateString()));
    }

    public function store(ActivityLogRequest $request, ActivityEnergy $energy)
    {
        $data = $request->validated();
        $log = ActivityLog::withTrashed()->firstOrCreate(
            ['client_id' => $request->user()->id, 'request_token' => $data['request_token']],
            $this->attributes($data, $energy)
        );
        $message = $log->trashed() ? 'Esa actividad fue eliminada. Usá el formulario nuevo para registrar otra.'
            : ($log->wasRecentlyCreated ? 'Actividad registrada.' : 'Esta actividad ya estaba registrada.');

        return to_route('client.activities.index', ['date' => $data['performed_on']])->with('success', $message);
    }

    public function edit(Request $request, ActivityLog $activityLog)
    {
        $this->authorizeOwner($request, $activityLog);

        return $this->page($request->user(), $activityLog->performed_on, $activityLog);
    }

    public function update(ActivityLogRequest $request, ActivityLog $activityLog, ActivityEnergy $energy)
    {
        $this->authorizeOwner($request, $activityLog);
        $data = $request->validated();
        $activityLog->update($this->attributes($data, $energy));

        return to_route('client.activities.index', ['date' => $data['performed_on']])->with('success', 'Actividad actualizada.');
    }

    public function destroy(Request $request, ActivityLog $activityLog)
    {
        $this->authorizeOwner($request, $activityLog);
        $date = $activityLog->performed_on->toDateString();
        $activityLog->delete();

        return to_route('client.activities.index', ['date' => $date])->with('success', 'Actividad eliminada del resumen.');
    }

    public function demo()
    {
        abort_unless(config('experience.enabled'), 404);
        $date = CarbonImmutable::today();
        $totals = [...ActivitySummary::emptyTotals(), 'cardio_kcal' => 160.0, 'strength_kcal' => 200.0,
            'total_kcal' => 360.0, 'cardio_minutes' => 30, 'strength_minutes' => 60, 'record_count' => 2];
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $date->startOfWeek()->addDays($i)->toDateString();
            $days[$day] = $day === $date->toDateString() ? $totals : ActivitySummary::emptyTotals();
        }

        return view('client.activities.index', ['isDemo' => true, 'selectedDate' => $date->toDateString(),
            'catalog' => ActivityCatalog::all(), 'editing' => null, 'suggestedWeight' => 80.0,
            'requestToken' => (string) Str::uuid(), 'entries' => collect(),
            'daySummary' => $totals, 'weekSummary' => $totals, 'weekDays' => $days]);
    }

    private function page(User $client, CarbonImmutable $date, ?ActivityLog $editing = null)
    {
        $weight = BodyMeasurement::query()->where('client_id', $client->id)
            ->whereDate('measured_at', '<=', $date->toDateString())->whereBetween('weight', [20, 400])
            ->orderByDesc('measured_at')->orderByDesc('id')->value('weight');

        return view('client.activities.index', [
            'isDemo' => false, 'selectedDate' => $date->toDateString(), 'editing' => $editing,
            'catalog' => ActivityCatalog::all(), 'suggestedWeight' => $weight === null ? null : (float) $weight,
            'requestToken' => (string) Str::uuid(),
            'entries' => ActivityLog::query()->where('client_id', $client->id)->whereDate('performed_on', $date->toDateString())
                ->latest()->paginate(20)->appends(['date' => $date->toDateString()]),
            ...app(ActivitySummary::class)->forClient($client, $date),
        ]);
    }

    private function attributes(array $data, ActivityEnergy $energy): array
    {
        $steps = $data['activity_key'] === 'walk_steps' ? (int) $data['step_count'] : null;
        $minutes = $steps === null ? (int) $data['duration_minutes'] : (int) ceil($steps / 100);

        return ['performed_on' => $data['performed_on'], 'duration_minutes' => $minutes, 'step_count' => $steps,
            'body_weight' => round((float) $data['body_weight'], 2), 'notes' => $data['notes'] ?? null,
            ...$energy->estimate($data['activity_key'], $minutes, round((float) $data['body_weight'], 2)),
            ...($steps === null ? [] : ['calculation_version' => 'steps100-compendium2024-net-v1'])];
    }

    private function authorizeOwner(Request $request, ActivityLog $log): void
    {
        abort_unless($log->client_id === $request->user()->id, 403);
    }
}
