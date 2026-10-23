<?php

namespace Tests\Feature;

use App\Models\CommercialPayment;
use App\Models\CommercialSubscription;
use App\Models\User;
use App\Services\CommercialBilling;
use App\Services\CommercialMemberWriter;
use App\Services\ManualMembershipPayments;
use App\Services\ManualMembershipPlans;
use App\Support\CommercialCatalog;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManualMembershipReviewTest extends TestCase
{
    use RefreshDatabase;

    private function membership(): CommercialSubscription
    {
        $this->travelTo(Carbon::parse('2027-01-31 10:00:00'));
        $owner = User::factory()->create(['role' => 'admin']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        return app(ManualMembershipPlans::class)->assign($owner, auth()->user(), 'coach-inicial', (string) Str::uuid(), CommercialCatalog::quote());
    }

    private function action(string $plan): array
    {
        return ['catalog_quote' => CommercialCatalog::quote(), 'operation_id' => (string) Str::uuid(), 'plan_code' => $plan, 'reason' => 'Cambio acordado con el titular', 'confirmed' => '1'];
    }

    private function pay(CommercialSubscription $sub, string $amount): void
    {
        app(ManualMembershipPayments::class)->record($sub, auth()->user(), ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'months' => 1, 'method' => 'cash', 'paid_on' => '2027-01-31', 'confirmed' => '1']);
    }

    private function selectQueries(Closure $action): array
    {
        $connection = DB::connection();
        $original = $connection->getQueryGrammar();
        // SQLite cannot exercise row contention. Preserve the lock intent as a SQL
        // comment so these integration tests verify the shared acquisition order.
        $connection->setQueryGrammar(new class($connection) extends SQLiteGrammar
        {
            protected function compileLock(Builder $query, $value)
            {
                return $value === true ? '/* row-lock */' : '';
            }
        });
        $connection->enableQueryLog();
        $connection->flushQueryLog();
        try {
            $action();

            return array_values(array_filter(array_column($connection->getQueryLog(), 'query'), fn ($sql) => str_starts_with($sql, 'select')));
        } finally {
            $connection->setQueryGrammar($original);
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
    }

    public function test_plan_capacity_changes_acquire_the_gym_lock_before_snapshot_reads(): void
    {
        $sub = $this->membership();
        $this->pay($sub, '14900');
        $sub = $sub->fresh();
        $queries = $this->selectQueries(fn () => app(ManualMembershipPlans::class)->schedule($sub, auth()->user(), $this->action('coach-profesional')));
        $this->assertStringContainsString('from "gyms"', $queries[0]);
        $this->assertStringContainsString('/* row-lock */', $queries[0]);
        $this->assertSame('coach-profesional', $sub->fresh()->next_plan_code);
    }

    public function test_member_updates_take_the_gym_lock_before_the_owner_and_quota_snapshot(): void
    {
        $sub = $this->membership();
        $this->pay($sub, '14900');
        $member = User::factory()->create(['gym_id' => $sub->gym_id, 'role' => 'client']);
        $queries = $this->selectQueries(fn () => app(CommercialMemberWriter::class)->update($member, ['goals' => 'Revisar progreso']));
        $this->assertStringContainsString('from "gyms"', $queries[0]);
        $this->assertStringContainsString('/* row-lock */', $queries[0]);
        $this->assertSame('Revisar progreso', $member->fresh()->goals);
    }

    public function test_a_started_prepaid_plan_allows_scheduling_the_following_renewal(): void
    {
        $sub = $this->membership();
        $this->pay($sub, '14900');
        app(ManualMembershipPlans::class)->schedule($sub->fresh(), auth()->user(), $this->action('coach-profesional'));
        $this->pay($sub->fresh(), '24900');
        $this->get('/gestion-comercial/membresias/'.$sub->id)->assertOk()->assertDontSee('Guardar próximo plan');
        $this->travelTo(Carbon::parse('2027-03-01 10:00:00'));
        $this->get('/gestion-comercial/membresias/'.$sub->id)->assertOk()->assertSee('Guardar próximo plan')->assertSee('No hay cambio de plan programado.');
        $root = auth()->user();
        $this->actingAs($sub->user)->get('/mi-suscripcion')->assertOk()->assertDontSee('Próximo plan acordado');
        $this->actingAs($root)->post('/gestion-comercial/membresias/'.$sub->id.'/plan', $this->action('coach-inicial'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('coach-profesional', $sub->fresh()->plan_code);
        $this->assertSame('coach-inicial', $sub->fresh()->next_plan_code);
        $this->assertSame(2, $sub->payments()->count());
    }

    public function test_legacy_utc_provider_versions_accept_a_newer_refund_and_reject_an_older_snapshot(): void
    {
        $sub = $this->membership();
        $sub->update(['billing_mode' => 'mercadopago', 'provider_id' => 'existing-provider']);
        $payment = CommercialPayment::create(['commercial_subscription_id' => $sub->id, 'provider_payment_id' => '456', 'source' => 'mercadopago', 'status' => 'approved', 'amount_cents' => 1490000, 'currency' => 'ARS', 'period_start' => now(), 'period_end' => now()->addMonth()]);
        DB::table('commercial_payments')->where('id', $payment->id)->update(['provider_updated_at' => '2027-01-31 13:00:00.000000']);
        $snapshot = ['id' => 456, 'status' => 'refunded', 'transaction_amount' => 14900, 'currency_id' => 'ARS', 'date_approved' => '2027-01-31T10:00:00-03:00', 'date_last_updated' => '2027-01-31T10:05:00-03:00'];
        Http::fake(['api.mercadopago.com/v1/payments/456' => Http::sequence()->push($snapshot)->push(array_replace($snapshot, ['status' => 'approved', 'date_last_updated' => '2027-01-31T10:04:00-03:00']))]);
        app(CommercialBilling::class)->syncPayment('456');
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('2027-01-31 13:05:00', $payment->fresh()->provider_updated_at->utc()->format('Y-m-d H:i:s'));
        app(CommercialBilling::class)->syncPayment('456');
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertFalse($sub->fresh()->hasAccess());
    }
}
