<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\RoutineDay;
use App\Models\RoutineDayExercise;
use App\Models\User;
use App\Services\TrainingExperience;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingExperienceTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Assignment $assignment;

    private Exercise $exercise;

    private RoutineDayExercise $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00', config('app.timezone')));
        $gym = Gym::create(['name' => 'Gym experiencia']);
        $this->client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $coach->id, 'title' => 'Rutina']);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'day_number' => 1, 'title' => 'Día 1']);
        $this->exercise = Exercise::create(['title' => 'Curl con barra', 'muscle_group' => 'biceps', 'is_global' => true]);
        $this->slot = RoutineDayExercise::create([
            'routine_day_id' => $day->id, 'exercise_id' => $this->exercise->id,
            'sets' => 3, 'reps' => '10', 'order' => 1,
        ]);
        $this->assignment = Assignment::create([
            'gym_id' => $gym->id, 'routine_id' => $routine->id, 'client_id' => $this->client->id,
            'assigned_by_id' => $coach->id, 'status' => 'active',
        ]);
    }

    public function test_empty_history_returns_the_complete_initial_contract(): void
    {
        $experience = $this->experience();

        $this->assertSame([
            'xp', 'level', 'level_xp', 'next_level_xp', 'level_percent', 'active_days',
            'active_weeks', 'total_sets', 'today_sets', 'week_days', 'muscles',
            'muscle_activity', 'achievements', 'recent_exercises',
        ], array_keys($experience));
        foreach (['xp', 'level_xp', 'level_percent', 'active_days', 'active_weeks', 'total_sets', 'today_sets', 'week_days'] as $key) {
            $this->assertSame(0, $experience[$key], $key);
        }
        $this->assertSame(1, $experience['level']);
        $this->assertSame(500, $experience['next_level_xp']);
        $this->assertSame([], $experience['muscles']);
        $this->assertSame([], $experience['muscle_activity']);
        $this->assertSame([], $experience['recent_exercises']);
        $this->assertCount(5, $experience['achievements']);
        foreach ($experience['achievements'] as $achievement) {
            $this->assertFalse($achievement['unlocked']);
            $this->assertSame(0, $achievement['current']);
        }
    }

    public function test_xp_rewards_own_distinct_dates_and_ignores_extra_sets_weight_and_edits(): void
    {
        $log = $this->log(['logged_at' => now()->setTime(8, 0), 'weight' => 10]);
        $this->log(['logged_at' => now()->setTime(10, 0), 'weight' => 200, 'set_number' => 2, 'reps' => 50]);
        $this->log(['logged_at' => now()->subDay()]);
        $repeat = $this->assignment->replicate();
        $repeat->save();
        $this->log(['assignment_id' => $repeat->id]);
        $other = User::factory()->create(['role' => 'client', 'gym_id' => $this->client->gym_id]);
        $foreign = $this->assignment->replicate()->fill(['client_id' => $other->id]);
        $foreign->save();
        $this->log(['assignment_id' => $foreign->id, 'logged_at' => now()->subDays(2), 'weight' => 999]);

        $experience = $this->experience();

        $this->assertSame(200, $experience['xp']);
        $this->assertSame(2, $experience['active_days']);
        $this->assertSame(4, $experience['total_sets']);
        $this->assertSame(3, $experience['today_sets']);
        $this->assertSame(2, $experience['week_days']);
        $this->assertSame(['biceps'], $experience['muscles']);
        $this->assertSame([['id' => 'biceps', 'label' => 'Bíceps', 'sets' => 4]], $experience['muscle_activity']);
        $this->assertNotContains(999.0, array_column($experience['recent_exercises'], 'weight'));

        $log->update(['weight' => 350, 'reps' => 100]);
        $this->assertSame(200, $this->experience()['xp']);
    }

    public function test_invalid_and_future_sets_are_excluded_while_bodyweight_is_valid(): void
    {
        $this->log(['weight' => null]);
        $this->log(['weight' => 0, 'logged_at' => now()->subDay()]);
        $this->log(['reps' => 0, 'logged_at' => now()->subDays(2)]);
        $this->log(['reps' => null, 'logged_at' => now()->subDays(3)]);
        $this->log(['reps' => -1, 'logged_at' => now()->subDays(4)]);
        $this->log(['weight' => -5, 'logged_at' => now()->subDays(5)]);
        $this->log(['logged_at' => now()->addDay()]);
        $this->log(['logged_at' => now()->addMinute()]);

        $experience = $this->experience();

        $this->assertSame(200, $experience['xp']);
        $this->assertSame(2, $experience['total_sets']);
        $this->assertSame(1, $experience['today_sets']);
        $this->assertNull($experience['recent_exercises'][0]['weight']);
        $this->assertSame(0.0, $experience['recent_exercises'][1]['weight']);
    }

    public function test_invalid_legacy_calendar_dates_do_not_reward_activity(): void
    {
        DB::table('exercise_logs')->insert([
            'assignment_id' => $this->assignment->id, 'routine_day_exercise_id' => $this->slot->id,
            'exercise_id' => $this->exercise->id, 'set_number' => 1, 'weight' => null,
            'reps' => 10, 'logged_at' => '2026-02-30 12:00:00',
        ]);

        $experience = $this->experience();

        $this->assertSame(0, $experience['xp']);
        $this->assertSame(0, $experience['total_sets']);
    }

    public function test_local_midnight_and_iso_week_boundaries_use_the_application_timezone(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-19 00:15:00', 'America/Argentina/Buenos_Aires'));
        $this->log(['logged_at' => '2026-10-18 23:55:00']);
        $this->log(['logged_at' => '2026-10-19 00:05:00']);
        $this->log(['logged_at' => '2026-10-19 00:16:00']);

        $experience = $this->experience();

        $this->assertSame(200, $experience['xp']);
        $this->assertSame(2, $experience['active_weeks']);
        $this->assertSame(1, $experience['today_sets']);
        $this->assertSame(1, $experience['week_days']);
        $this->assertSame('2026-10-19T00:05:00-03:00', $experience['recent_exercises'][0]['logged_at']);
    }

    public function test_level_advances_every_five_active_days(): void
    {
        for ($day = 0; $day < 5; $day++) {
            $this->log(['logged_at' => now()->subDays($day)]);
        }

        $experience = $this->experience();
        $this->assertSame(500, $experience['xp']);
        $this->assertSame(2, $experience['level']);
        $this->assertSame(0, $experience['level_xp']);
        $this->assertSame(0, $experience['level_percent']);

        $this->log(['logged_at' => now()->subDays(5)]);
        $experience = $this->experience();
        $this->assertSame(2, $experience['level']);
        $this->assertSame(100, $experience['level_xp']);
        $this->assertSame(20, $experience['level_percent']);
    }

    public function test_actual_exercise_prevails_and_legacy_and_deleted_history_are_preserved(): void
    {
        $this->exercise->update(['title' => 'Aperturas', 'muscle_group' => 'pecho']);
        $replacement = Exercise::create(['title' => 'Gemelos', 'muscle_group' => 'piernas', 'is_global' => true]);
        $unknown = Exercise::create(['title' => 'Movimiento secreto', 'muscle_group' => 'desconocido', 'is_global' => true]);
        $this->log(['exercise_id' => null, 'logged_at' => now()->subHours(3)]);
        $this->log(['exercise_id' => $replacement->id, 'logged_at' => now()->subHours(2)]);
        $this->log(['exercise_id' => $unknown->id, 'logged_at' => now()->subHour()]);
        $this->exercise->delete();
        $replacement->delete();
        $this->assignment->delete();

        $experience = $this->experience();

        $this->assertSame(3, $experience['total_sets']);
        $this->assertSame(['chest', 'calves'], $experience['muscles']);
        $this->assertSame([$unknown->id, $replacement->id, $this->exercise->id], array_column($experience['recent_exercises'], 'id'));
        $this->assertSame(['Movimiento secreto', 'Gemelos', 'Aperturas'], array_column($experience['recent_exercises'], 'title'));
        $this->assertSame([1, 1], array_column($experience['muscle_activity'], 'sets'));
    }

    public function test_muscle_activity_counts_each_known_primary_and_secondary_group_once_per_set(): void
    {
        $this->exercise->update(['title' => 'Press banca con barra', 'muscle_group' => 'pecho']);
        $this->log();

        $experience = $this->experience();

        $this->assertSame(['chest', 'shoulders', 'triceps'], $experience['muscles']);
        $this->assertSame([
            ['id' => 'chest', 'label' => 'Pecho', 'sets' => 1],
            ['id' => 'shoulders', 'label' => 'Hombros', 'sets' => 1],
            ['id' => 'triceps', 'label' => 'Tríceps', 'sets' => 1],
        ], $experience['muscle_activity']);
        $this->assertSame(1, $experience['total_sets']);
    }

    public function test_achievements_use_activity_days_weeks_and_known_muscles(): void
    {
        $groups = ['biceps', 'triceps', 'pecho', 'espalda', 'abdomen'];
        $exercises = array_map(fn (string $group) => Exercise::create([
            'title' => 'Ejercicio '.$group, 'muscle_group' => $group, 'is_global' => true,
        ]), $groups);
        for ($day = 0; $day < 20; $day++) {
            $this->log(['logged_at' => now()->subDays($day), 'exercise_id' => $exercises[$day % 5]->id]);
        }

        $experience = $this->experience();

        $this->assertSame(2000, $experience['xp']);
        $this->assertSame(5, $experience['level']);
        $this->assertSame(4, $experience['active_weeks']);
        $this->assertCount(5, $experience['muscles']);
        foreach ($experience['achievements'] as $achievement) {
            $this->assertTrue($achievement['unlocked'], $achievement['id']);
            $this->assertGreaterThanOrEqual($achievement['target'], $achievement['current']);
        }
    }

    public function test_active_weeks_distinguish_the_iso_week_year(): void
    {
        $this->log(['logged_at' => '2024-12-30 12:00:00']);
        $this->log(['logged_at' => '2025-12-29 12:00:00']);

        $this->assertSame(2, $this->experience()['active_weeks']);
    }

    public function test_history_has_no_arbitrary_limit_and_query_count_does_not_grow_per_set(): void
    {
        $rows = [];
        for ($day = 0; $day < 1040; $day++) {
            $rows[] = [
                'assignment_id' => $this->assignment->id, 'routine_day_exercise_id' => $this->slot->id,
                'exercise_id' => $this->exercise->id, 'set_number' => 1, 'weight' => null,
                'reps' => 10, 'logged_at' => now()->subDays($day)->toDateTimeString(),
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('exercise_logs')->insert($chunk);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();

        $experience = $this->experience();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(104000, $experience['xp']);
        $this->assertSame(1040, $experience['total_sets']);
        $this->assertCount(8, $experience['recent_exercises']);
        $this->assertLessThanOrEqual(4, count($queries));
    }

    private function experience(): array
    {
        return app(TrainingExperience::class)->forClient($this->client);
    }

    private function log(array $attributes = []): ExerciseLog
    {
        return ExerciseLog::create(array_replace([
            'assignment_id' => $this->assignment->id, 'routine_day_exercise_id' => $this->slot->id,
            'exercise_id' => $this->exercise->id, 'set_number' => 1, 'weight' => 20,
            'reps' => 10, 'logged_at' => now(),
        ], $attributes));
    }
}
