<?php

namespace Tests\Feature;

use App\Models\CommercialPayment;
use App\Models\CommercialSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ManualMembershipPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function subscription(): CommercialSubscription
    {
        $this->travelTo(Carbon::parse('2027-01-31 10:00:00'));
        $owner = User::factory()->create(['role' => 'admin']);
        $owner->gym->forceFill(['billing_managed' => true])->save();
        $sub = CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'gym_id' => $owner->gym_id, 'plan_code' => 'coach-inicial', 'kind' => 'software', 'amount_cents' => 1490000, 'currency' => 'ARS', 'status' => 'awaiting_payment', 'terms_version' => 'test']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        Http::preventStrayRequests();

        return $sub;
    }

    private function paymentData(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'amount' => '14900.00', 'months' => 1, 'method' => 'transfer', 'reference' => 'OPERACION-123', 'paid_on' => '2027-01-31', 'confirmed' => '1', 'fiscal_reference' => 'Referencia de factura emitida afuera'], $changes);
    }

    private function pay(CommercialSubscription $sub, array $changes = []): CommercialPayment
    {
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $this->paymentData($changes))->assertRedirect('/gestion-comercial/membresias/'.$sub->id)->assertSessionHasNoErrors();

        return CommercialPayment::latest('id')->firstOrFail();
    }

    private function refundData(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'amount' => '5000.00', 'reason' => 'Reintegro acordado y realizado', 'performed_on' => '2027-01-31', 'confirmed' => '1'], $changes);
    }

    public function test_confirmed_manual_payment_grants_one_exact_month_and_records_the_actor(): void
    {
        $sub = $this->subscription();
        $payment = $this->pay($sub);
        $this->assertSame('manual', $payment->source);
        $this->assertSame(1490000, $payment->amount_cents);
        $this->assertSame('2027-01-31 10:00:00', $payment->period_start->format('Y-m-d H:i:s'));
        $this->assertSame('2027-02-28 10:00:00', $sub->fresh()->paid_until->format('Y-m-d H:i:s'));
        $this->assertTrue($sub->fresh()->hasAccess());
        $this->assertDatabaseHas('commercial_operations', ['action' => 'payment_recorded', 'actor_id' => auth()->id(), 'commercial_subscription_id' => $sub->id]);
        Http::assertNothingSent();
    }

    public function test_prepayment_starts_after_the_existing_paid_period(): void
    {
        $sub = $this->subscription();
        $this->pay($sub);
        $next = $this->pay($sub, ['reference' => 'OPERACION-456']);
        $this->assertSame('2027-02-28 10:00:00', $next->period_start->format('Y-m-d H:i:s'));
        $this->assertSame('2027-03-28 10:00:00', $next->period_end->format('Y-m-d H:i:s'));
    }

    public function test_resending_a_payment_does_not_duplicate_money_or_extend_access(): void
    {
        $sub = $this->subscription();
        $data = $this->paymentData();
        for ($i = 0; $i < 2; $i++) {
            $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $data)->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('commercial_payments', 1);
        $this->assertDatabaseCount('commercial_operations', 1);
        $this->assertSame('2027-02-28', $sub->fresh()->paid_until->toDateString());
        $data['months'] = 2;
        $data['amount'] = '29800.00';
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $data)->assertSessionHasErrors('operation_id');
        $this->assertDatabaseCount('commercial_payments', 1);
    }

    public function test_a_second_token_cannot_reuse_the_same_transfer_reference(): void
    {
        $sub = $this->subscription();
        $this->pay($sub);
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $this->paymentData())->assertSessionHasErrors('reference');
        $this->assertDatabaseCount('commercial_payments', 1);
    }

    public static function invalidPayments(): array
    {
        return ['zero' => [['amount' => '0'], 'amount'], 'negative' => [['amount' => '-1'], 'amount'], 'precision' => [['amount' => '14900.001'], 'amount'], 'wrong-price' => [['amount' => '1.00'], 'amount'], 'too-many-months' => [['months' => 13], 'months'], 'not-confirmed' => [['confirmed' => '0'], 'confirmed'], 'foreign-currency' => [['currency' => 'USD'], 'currency']];
    }

    #[DataProvider('invalidPayments')]
    public function test_invalid_or_unconfirmed_payments_never_enable_access(array $changes, string $field): void
    {
        $sub = $this->subscription();
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $this->paymentData($changes))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('commercial_payments', 0);
        $this->assertDatabaseCount('commercial_operations', 0);
        $this->assertFalse($sub->fresh()->hasAccess());
    }

    public function test_customers_cannot_confirm_their_own_payment(): void
    {
        $sub = $this->subscription();
        $this->actingAs($sub->user)->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $this->paymentData())->assertForbidden();
        $this->assertDatabaseCount('commercial_payments', 0);
    }

    public function test_gateway_subscriptions_reject_manual_payments(): void
    {
        $sub = $this->subscription();
        $sub->update(['billing_mode' => 'mercadopago', 'provider_id' => 'provider-live']);
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', $this->paymentData())->assertSessionHasErrors('billing');
        $this->assertDatabaseCount('commercial_payments', 0);
    }

    public function test_partial_refunds_keep_the_period_and_total_refunds_remove_it(): void
    {
        $sub = $this->subscription();
        $payment = $this->pay($sub);
        $path = '/gestion-comercial/pagos/'.$payment->id.'/reintegros';
        $first = $this->refundData();
        $this->post($path, $first)->assertRedirect()->assertSessionHasNoErrors();
        $this->post($path, $first)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('commercial_refunds', 1);
        $this->assertSame(500000, $payment->fresh()->refunded_cents);
        $this->assertTrue($sub->fresh()->hasAccess());
        $this->post($path, $this->refundData(['amount' => '10000']))->assertSessionHasErrors('amount');
        $this->post($path, $this->refundData(['amount' => '9900']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertNull($sub->fresh()->paid_until);
        $this->assertFalse($sub->fresh()->hasAccess());
        $this->assertDatabaseCount('commercial_refunds', 2);
    }

    public function test_refunding_the_current_period_does_not_fill_a_gap_before_future_periods(): void
    {
        $sub = $this->subscription();
        $current = $this->pay($sub);
        $this->pay($sub, ['reference' => 'NEXT-PERIOD']);
        $this->post('/gestion-comercial/pagos/'.$current->id.'/reintegros', $this->refundData(['amount' => '14900']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($sub->fresh()->hasAccess());
        $this->travelTo(Carbon::parse('2027-03-01 10:00:00'));
        $this->assertTrue($sub->fresh()->hasAccess());
    }

    public function test_recorded_payment_amounts_cannot_be_edited(): void
    {
        $payment = $this->pay($this->subscription());
        $this->expectException(ValidationException::class);
        $payment->update(['amount_cents' => 1]);
    }

    public function test_a_customer_cannot_record_a_refund(): void
    {
        $sub = $this->subscription();
        $payment = $this->pay($sub);
        $this->actingAs($sub->user)->post('/gestion-comercial/pagos/'.$payment->id.'/reintegros', $this->refundData())->assertForbidden();
        $this->assertDatabaseCount('commercial_refunds', 0);
    }

    public function test_a_refund_cannot_be_dated_before_the_original_payment(): void
    {
        $payment = $this->pay($this->subscription());
        $this->post('/gestion-comercial/pagos/'.$payment->id.'/reintegros', $this->refundData(['performed_on' => '2027-01-30']))->assertSessionHasErrors('performed_on');
        $this->assertDatabaseCount('commercial_refunds', 0);
    }

    public function test_a_business_month_uses_the_argentine_calendar_near_utc_midnight(): void
    {
        $sub = $this->subscription();
        $this->travelTo(Carbon::parse('2027-01-31 01:00:00', 'UTC'));
        $payment = $this->pay($sub, ['paid_on' => '2027-01-30']);
        $this->assertSame('2027-03-01 01:00:00', $payment->period_end->utc()->format('Y-m-d H:i:s'));
    }
}
