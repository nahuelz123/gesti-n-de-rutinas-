<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActivityLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(18, 0));
    }

    public function test_pages_and_writes_require_a_client(): void
    {
        $this->get('/app/activities')->assertRedirect(route('login'));
        $this->post('/app/activities', $this->payload())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'coach']))->get('/app/activities')->assertForbidden();
    }

    public function test_stores_one_activity_per_submission_and_ignores_forged_calories(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $payload = $this->payload() + ['client_id' => 999, 'active_kcal' => 99999, 'met' => 99, 'category' => 'strength'];
        $this->actingAs($client)->post('/app/activities', $payload)->assertRedirect();
        $this->post('/app/activities', $payload)->assertRedirect();
        $this->assertDatabaseCount('activity_logs', 1);
        $log = ActivityLog::firstOrFail();
        $this->assertSame($client->id, $log->client_id);
        $this->assertSame('cardio', $log->category);
        $this->assertEquals(5, $log->met);
        $this->assertEquals(160, $log->active_kcal); // (5 - 1) × 80 kg × half an hour.
        $this->assertSame('compendium2024-net-v1', $log->calculation_version);
    }

    #[DataProvider('invalidEntries')]
    public function test_rejects_invalid_activity_data(string $field, mixed $value): void
    {
        $this->actingAs(User::factory()->create(['role' => 'client']))
            ->post('/app/activities', array_replace($this->payload(), [$field => $value]))
            ->assertSessionHasErrors($field);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function invalidEntries(): array
    {
        return [['duration_minutes', 0], ['duration_minutes', -10], ['duration_minutes', 30.5],
            ['duration_minutes', 601], ['body_weight', null], ['body_weight', -80], ['body_weight', 401],
            ['activity_key', 'invented'], ['performed_on', '2026-10-21'], ['performed_on', '2026-02-30'],
            ['request_token', 'not-a-uuid']];
    }

    public function test_day_week_and_weight_snapshots_stay_scoped_to_the_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client);
        $this->post('/app/activities', $this->payload())->assertRedirect();
        $this->post('/app/activities', $this->payload(['activity_key' => 'strength_general', 'duration_minutes' => 60]))->assertRedirect();
        $this->post('/app/activities', $this->payload(['performed_on' => '2026-10-19']))->assertRedirect();
        $this->post('/app/activities', $this->payload(['performed_on' => '2026-10-18']))->assertRedirect();
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => today(), 'weight' => 95]);
        $other = User::factory()->create(['role' => 'client']);
        $this->actingAs($other)->post('/app/activities', $this->payload())->assertRedirect();
        $response = $this->actingAs($client)->get('/app/activities?date=2026-10-20')->assertOk()
            ->assertSee('Estimación')->assertSee('Cardio y gasto');
        $this->assertSame(160.0, $response->viewData('daySummary')['cardio_kcal']);
        $this->assertSame(200.0, $response->viewData('daySummary')['strength_kcal']);
        $this->assertSame(360.0, $response->viewData('daySummary')['total_kcal']);
        $this->assertSame(520.0, $response->viewData('weekSummary')['total_kcal']);
    }

    public function test_edit_recalculates_and_delete_removes_the_record_from_totals(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client)->post('/app/activities', $this->payload())->assertRedirect();
        $log = ActivityLog::firstOrFail();
        $this->get('/app/activities/'.$log->id.'/edit')->assertOk();
        $this->put('/app/activities/'.$log->id, $this->payload(['duration_minutes' => 60]))->assertRedirect();
        $this->assertEquals(320, $log->fresh()->active_kcal);
        $this->delete('/app/activities/'.$log->id)->assertRedirect();
        $this->assertSoftDeleted('activity_logs', ['id' => $log->id]);
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($d) => $d['total_kcal'] === 0.0);
    }

    public function test_cannot_read_edit_or_delete_another_clients_activity(): void
    {
        $first = User::factory()->create(['role' => 'client']);
        $this->actingAs($first)->post('/app/activities', $this->payload())->assertRedirect();
        $log = ActivityLog::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'client']));
        $this->get('/app/activities/'.$log->id.'/edit')->assertForbidden();
        $this->put('/app/activities/'.$log->id, $this->payload())->assertForbidden();
        $this->delete('/app/activities/'.$log->id)->assertForbidden();
    }

    public function test_body_weight_prefill_ignores_future_and_invalid_measurements(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        foreach ([['2026-10-18', 82], ['2026-10-19', -5], ['2026-10-20', 84], ['2026-10-21', 90]] as [$date, $weight]) {
            BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => $date, 'weight' => $weight]);
        }
        $this->actingAs($client)->get('/app/activities?date=2026-10-20')->assertOk()->assertViewHas('suggestedWeight', 84.0);
    }

    public function test_demo_is_fictional_and_cannot_write_without_login(): void
    {
        config()->set('experience.enabled', true);
        $this->get('/experiencia/actividad')->assertOk()->assertViewHas('isDemo', true)->assertSee('Datos ficticios');
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('users', 0);
        config()->set('experience.enabled', false);
        $this->get('/experiencia/actividad')->assertNotFound();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['performed_on' => '2026-10-20', 'activity_key' => 'elliptical_moderate',
            'duration_minutes' => 30, 'body_weight' => 80, 'request_token' => (string) Str::uuid()], $overrides);
    }
}
