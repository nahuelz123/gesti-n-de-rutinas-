<?php

namespace Tests\Feature;

use App\Models\CommercialSubscription;
use App\Models\User;
use App\Services\CommercialMemberWriter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualMembershipPlansTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->travelTo(Carbon::parse('2027-01-31 10:00:00'));
        $owner = User::factory()->create(['role' => 'admin']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        return $owner;
    }

    private function assign(User $owner, string $plan = 'coach-inicial'): CommercialSubscription
    {
        $this->post('/gestion-comercial/membresias', ['operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => $plan, 'confirmed' => '1'])->assertRedirect()->assertSessionHasNoErrors();

        return CommercialSubscription::where('user_id', $owner->id)->firstOrFail();
    }

    private function pay(CommercialSubscription $sub, string $amount = '14900', string $reference = 'PLAN-OP-1'): void
    {
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'months' => '1', 'method' => 'transfer', 'reference' => $reference, 'paid_on' => '2027-01-31', 'confirmed' => '1'])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function changeData(string $plan): array
    {
        return ['operation_id' => (string) Str::uuid(), 'plan_code' => $plan, 'reason' => 'Cambio acordado con el titular', 'confirmed' => '1'];
    }

    public function test_assigning_an_existing_workspace_keeps_legacy_access_until_the_first_payment(): void
    {
        $owner = $this->owner();
        $sub = $this->assign($owner);
        $this->assertFalse((bool) $owner->gym->fresh()->billing_managed);
        $this->assertNull($sub->paid_until);
        $this->pay($sub);
        $this->assertTrue((bool) $owner->gym->fresh()->billing_managed);
        $this->assertTrue($sub->fresh()->hasAccess());
        $this->assertDatabaseHas('commercial_operations', ['action' => 'subscription_assigned', 'commercial_subscription_id' => $sub->id]);
    }

    public function test_assignments_are_idempotent_and_do_not_duplicate_subscriptions(): void
    {
        $owner = $this->owner();
        $data = ['operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => 'coach-inicial', 'confirmed' => '1'];
        $this->post('/gestion-comercial/membresias', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/gestion-comercial/membresias', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('commercial_subscriptions', 1);
        $data['operation_id'] = (string) Str::uuid();
        $this->post('/gestion-comercial/membresias', $data)->assertSessionHasErrors('user_id');
    }

    public function test_only_root_can_assign_a_membership(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/gestion-comercial/membresias', ['operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => 'coach-inicial', 'confirmed' => '1'])->assertForbidden();
        $this->assertDatabaseCount('commercial_subscriptions', 0);
    }

    public function test_coaching_memberships_require_a_client_in_the_selected_coaching_space(): void
    {
        $this->owner();
        $client = User::factory()->create(['role' => 'client']);
        $data = ['operation_id' => (string) Str::uuid(), 'user_id' => $client->id, 'plan_code' => 'asesoria', 'confirmed' => '1'];
        $this->post('/gestion-comercial/membresias', $data)->assertSessionHasErrors('user_id');
        config(['commerce.coaching_gym_id' => $client->gym_id]);
        $this->post('/gestion-comercial/membresias', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('commercial_subscriptions', ['user_id' => $client->id, 'kind' => 'coaching', 'gym_id' => null, 'amount_cents' => 6990000]);
    }

    public function test_a_prepaid_plan_change_activates_only_when_its_billing_period_starts(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/plan', $this->changeData('coach-profesional'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('coach-inicial', $sub->fresh()->currentPlanCode());
        $this->pay($sub, '24900', 'PLAN-OP-2');
        $this->assertSame('coach-inicial', $sub->fresh()->currentPlanCode());
        $this->assertSame('2027-02-28', $sub->fresh()->next_plan_effective_at->toDateString());
        $this->travelTo(Carbon::parse('2027-03-01 10:00:00'));
        $this->assertSame('coach-profesional', $sub->fresh()->currentPlanCode());
        $this->assertSame(2490000, $sub->fresh()->currentAmountCents());
    }

    public function test_plan_changes_cannot_cross_software_and_personal_coaching(): void
    {
        $sub = $this->assign($this->owner());
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/plan', $this->changeData('asesoria'))->assertSessionHasErrors('plan_code');
        $this->assertNull($sub->fresh()->next_plan_code);
    }

    public function test_an_agreed_new_price_for_the_same_plan_starts_with_its_new_period(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        config(['commerce.plans.coach-inicial.price' => 15900]);
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/plan', $this->changeData('coach-inicial'))->assertRedirect()->assertSessionHasNoErrors();
        $this->pay($sub, '15900', 'NEW-PRICE');
        $this->assertSame(1490000, $sub->fresh()->currentAmountCents());
        $this->travelTo(Carbon::parse('2027-03-01 10:00:00'));
        $this->assertSame(1590000, $sub->fresh()->currentAmountCents());
    }

    public function test_downgrades_reserve_the_pending_capacity_and_prevent_new_overbooking(): void
    {
        $sub = $this->assign($this->owner(), 'coach-profesional');
        $this->pay($sub, '24900');
        for ($i = 0; $i < 11; $i++) {
            User::factory()->create(['gym_id' => $sub->gym_id, 'role' => 'client']);
        }
        $path = '/gestion-comercial/membresias/'.$sub->id.'/plan';
        $this->post($path, $this->changeData('coach-inicial'))->assertSessionHasErrors('plan_code');
        $sub->gym->users()->where('role', 'client')->first()->update(['commercial_access_enabled' => false]);
        $this->post($path, $this->changeData('coach-inicial'))->assertRedirect()->assertSessionHasNoErrors();
        $this->expectException(ValidationException::class);
        app(CommercialMemberWriter::class)->create($sub->gym, ['name' => 'Exceso', 'email' => 'exceso@example.test', 'password' => 'password', 'role' => 'client']);
    }

    public function test_first_payment_rechecks_capacity_before_managing_a_legacy_workspace(): void
    {
        $owner = $this->owner();
        $sub = $this->assign($owner);
        for ($i = 0; $i < 11; $i++) {
            User::factory()->create(['gym_id' => $sub->gym_id, 'role' => 'client']);
        }
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', ['operation_id' => (string) Str::uuid(), 'amount' => '14900', 'months' => 1, 'method' => 'cash', 'paid_on' => '2027-01-31', 'confirmed' => '1'])->assertSessionHasErrors('plan_code');
        $this->assertFalse((bool) $owner->gym->fresh()->billing_managed);
        $this->assertDatabaseCount('commercial_payments', 0);
    }

    public function test_cancel_and_resume_preserve_paid_dates_and_require_explicit_resume_before_renewing(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        $action = ['operation_id' => (string) Str::uuid(), 'reason' => 'Pedido del titular de la cuenta', 'confirmed' => '1'];
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/baja', $action)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($sub->fresh()->hasAccess());
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', ['operation_id' => (string) Str::uuid(), 'amount' => '14900', 'months' => 1, 'method' => 'cash', 'paid_on' => '2027-01-31', 'confirmed' => '1'])->assertSessionHasErrors('billing');
        $action['operation_id'] = (string) Str::uuid();
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/reanudar', $action)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($sub->fresh()->cancel_at_period_end);
        $this->assertSame('2027-02-28', $sub->fresh()->paid_until->toDateString());
        $this->assertDatabaseCount('commercial_payments', 1);
    }

    public function test_owner_cancellation_is_recorded_without_allowing_owner_money_operations(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        $owner = $sub->user;
        $this->actingAs($owner)->post('/mi-suscripcion/cancelar', ['operation_id' => (string) Str::uuid()])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($sub->fresh()->cancel_at_period_end);
        $this->assertDatabaseHas('commercial_operations', ['action' => 'subscription_cancelled', 'actor_id' => $owner->id]);
    }

    public function test_a_paid_manual_period_cannot_be_charged_again_through_gateway_checkout(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        config(['commerce.mercadopago_token' => 'TEST-private', 'commerce.mercadopago_webhook_secret' => 'secret', 'legal.operator_name' => 'Prueba', 'legal.contact_email' => 'prueba@example.test']);
        Http::preventStrayRequests();
        $this->actingAs($sub->user)->post('/mi-suscripcion/checkout', ['renewal_accepted' => '1'])->assertSessionHasErrors('billing');
        Http::assertNothingSent();
        $this->assertNull($sub->fresh()->provider_id);
    }

    public function test_accounts_with_manual_financial_history_cannot_be_deleted(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        $this->expectException(ValidationException::class);
        $sub->user->delete();
    }

    public function test_workspaces_with_manual_financial_history_cannot_be_deleted(): void
    {
        $sub = $this->assign($this->owner());
        $this->pay($sub);
        $this->expectException(ValidationException::class);
        $sub->gym->delete();
    }
}
