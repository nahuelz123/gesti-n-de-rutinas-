<?php

namespace Tests\Feature;

use App\Models\CommercialSubscription;
use App\Models\User;
use App\Services\CommercialMemberWriter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommercialSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private function enroll(): CommercialSubscription
    {
        $this->post('/empezar/coach-inicial', ['name' => 'Entrenador Demo', 'business_name' => 'Mi espacio', 'email' => 'nuevo@example.test', 'password' => 'Password-test-123!', 'password_confirmation' => 'Password-test-123!', 'privacy_accepted' => '1', 'terms_accepted' => '1'])
            ->assertRedirect('/mi-suscripcion');

        return CommercialSubscription::firstOrFail();
    }

    private function ready(): void
    {
        config(['commerce.mercadopago_token' => 'TEST-private', 'commerce.mercadopago_webhook_secret' => 'webhook-secret', 'legal.operator_name' => 'Responsable de prueba', 'legal.contact_email' => 'privacidad@example.test']);
    }

    private function event(string $id, string $type = 'subscription_authorized_payment')
    {
        $ts = '1704908010';
        $request = 'request-test';
        $signature = hash_hmac('sha256', 'id:'.$id.';request-id:'.$request.';ts:'.$ts.';', 'webhook-secret');

        return $this->withHeaders(['x-signature' => 'ts='.$ts.',v1='.$signature, 'x-request-id' => $request])
            ->postJson('/webhooks/mercadopago?data.id='.$id, ['type' => $type, 'data' => ['id' => $id]]);
    }

    private function paymentFixture(CommercialSubscription $sub, string $status = 'approved', int $amount = 14900): void
    {
        $sub->update(['provider_id' => 'preapproval-demo']);
        Http::fake([
            'api.mercadopago.com/authorized_payments/123' => Http::response(['id' => 123, 'preapproval_id' => 'preapproval-demo', 'debit_date' => now()->toIso8601String(), 'payment' => ['id' => 456]]),
            'api.mercadopago.com/v1/payments/456' => Http::response(['id' => 456, 'status' => $status, 'transaction_amount' => $amount, 'currency_id' => 'ARS', 'date_approved' => now()->toIso8601String(), 'date_last_updated' => now()->toIso8601String()]),
        ]);
    }

    public function test_software_trial_creates_an_isolated_owner_and_no_charge(): void
    {
        Http::preventStrayRequests();
        $sub = $this->enroll();
        $this->assertAuthenticated();
        $this->assertSame('admin', auth()->user()->role);
        $this->assertSame(auth()->user()->gym_id, $sub->gym_id);
        $this->assertTrue($sub->hasAccess());
        $this->assertSame(14, (int) now()->startOfDay()->diffInDays($sub->trial_ends_at->startOfDay()));
        $this->assertNull($sub->provider_id);
        $this->get('/mi-suscripcion')->assertOk()->assertSee('Mi suscripción')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        Http::assertNothingSent();
    }

    public function test_existing_email_cannot_be_moved_to_another_gym_by_public_signup(): void
    {
        $user = User::factory()->create(['email' => 'nuevo@example.test']);
        $this->post('/empezar/coach-inicial', ['name' => 'Otro', 'business_name' => 'Otro espacio', 'email' => $user->email, 'password' => 'Password-test-123!', 'password_confirmation' => 'Password-test-123!', 'privacy_accepted' => '1', 'terms_accepted' => '1'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'gym_id' => $user->gym_id]);
    }

    public function test_unconfigured_checkout_never_calls_the_provider(): void
    {
        $this->enroll();
        Http::preventStrayRequests();
        $this->post('/mi-suscripcion/checkout', ['renewal_accepted' => '1'])->assertSessionHasErrors('billing');
        Http::assertNothingSent();
    }

    public function test_checkout_uses_the_catalogue_price_and_does_not_mark_a_payment(): void
    {
        $sub = $this->enroll();
        $this->ready();
        Http::fake(['api.mercadopago.com/preapproval' => Http::response(['id' => 'pending-123', 'init_point' => 'https://www.mercadopago.com.ar/subscriptions/checkout?id=pending-123', 'status' => 'pending'])]);
        $this->post('/mi-suscripcion/checkout', ['renewal_accepted' => '1', 'amount' => 1])->assertRedirect('https://www.mercadopago.com.ar/subscriptions/checkout?id=pending-123');
        Http::assertSent(fn ($request) => $request['auto_recurring']['transaction_amount'] === 14900 && $request['external_reference'] === $sub->public_id);
        $this->assertNull($sub->fresh()->paid_until);
    }

    public function test_other_tenant_cannot_cancel_an_owners_subscription(): void
    {
        $sub = $this->enroll();
        auth()->logout();
        $other = User::factory()->create();
        $this->assertNotSame($sub->gym_id, $other->gym_id);
        $this->actingAs($other)->post('/mi-suscripcion/cancelar', ['subscription_id' => $sub->id])->assertForbidden();
        $this->assertFalse($sub->fresh()->cancel_at_period_end);
    }

    public function test_forged_webhook_is_rejected_before_any_provider_request(): void
    {
        $this->ready();
        Http::preventStrayRequests();
        $this->postJson('/webhooks/mercadopago', ['type' => 'payment', 'data' => ['id' => '123']])->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_duplicate_approved_notifications_grant_one_billing_period(): void
    {
        $sub = $this->enroll();
        $this->ready();
        $this->paymentFixture($sub);
        $this->event('123')->assertOk();
        $until = $sub->fresh()->paid_until->toIso8601String();
        $this->event('123')->assertOk();
        $this->assertSame($until, $sub->fresh()->paid_until->toIso8601String());
        $this->assertDatabaseCount('commercial_payments', 1);
    }

    public function test_rejected_payments_do_not_grant_access(): void
    {
        $sub = $this->enroll();
        $sub->update(['trial_ends_at' => now()->subDay()]);
        $this->ready();
        $this->paymentFixture($sub, 'rejected');
        $this->event('123')->assertOk();
        $this->assertFalse($sub->fresh()->hasAccess());
    }

    public function test_wrong_amount_payments_do_not_grant_access(): void
    {
        $sub = $this->enroll();
        $sub->update(['trial_ends_at' => now()->subDay()]);
        $this->ready();
        $this->paymentFixture($sub, 'approved', 1);
        $this->event('123')->assertStatus(422);
        $this->assertFalse($sub->fresh()->hasAccess());
    }

    public function test_expired_managed_workspaces_cannot_keep_using_the_private_panel(): void
    {
        $sub = $this->enroll();
        $sub->update(['trial_ends_at' => now()->subDay()]);
        $this->get('/admin')->assertRedirect('/mi-suscripcion');
        $this->get('/mi-suscripcion')->assertOk();
    }

    public function test_paid_access_without_a_trial_expires_after_the_grace_period(): void
    {
        $sub = $this->enroll();
        $sub->update(['trial_ends_at' => null, 'paid_until' => now()->subDays(8)]);
        $this->assertFalse($sub->fresh()->hasAccess());
    }

    public function test_provider_failure_does_not_fake_a_successful_cancellation(): void
    {
        $sub = $this->enroll();
        $sub->update(['provider_id' => 'preapproval-demo']);
        $this->ready();
        Http::fake(['api.mercadopago.com/preapproval/preapproval-demo' => Http::response([], 503)]);
        $this->post('/mi-suscripcion/cancelar')->assertSessionHasErrors('billing');
        $this->assertFalse($sub->fresh()->cancel_at_period_end);
    }

    public function test_cancellation_keeps_paid_access_but_never_extends_it(): void
    {
        $sub = $this->enroll();
        $sub->update(['provider_id' => 'preapproval-demo', 'paid_until' => now()->addDays(10)]);
        $this->ready();
        Http::fake(['api.mercadopago.com/preapproval/preapproval-demo' => Http::response(['status' => 'cancelled'])]);
        $this->post('/mi-suscripcion/cancelar')->assertRedirect();
        $this->assertTrue($sub->fresh()->hasAccess());
        $this->travel(11)->days();
        $this->assertFalse($sub->fresh()->hasAccess());
    }

    public function test_managed_workspace_enforces_student_capacity_and_archiving_frees_a_place(): void
    {
        $sub = $this->enroll();
        $writer = app(CommercialMemberWriter::class);
        $data = ['name' => 'Alumno', 'email' => 'alumno-1@example.test', 'password' => 'password', 'role' => 'client'];
        config(['commerce.plans.coach-inicial.students' => 1]);
        $first = $writer->create($sub->gym, $data);
        try {
            $writer->create($sub->gym, array_replace($data, ['email' => 'alumno-2@example.test']));
            $this->fail('El cupo se debe aplicar antes de guardar al alumno.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('commercial_access_enabled', $exception->errors());
        }
        $writer->update($first, ['commercial_access_enabled' => false]);
        $second = $writer->create($sub->gym, array_replace($data, ['email' => 'alumno-2@example.test']));
        $this->assertNotNull($second->id);
        $this->actingAs($first)->get('/app')->assertForbidden();
    }

    public function test_owner_cannot_archive_their_own_billing_access(): void
    {
        $sub = $this->enroll();
        $this->expectException(ValidationException::class);
        app(CommercialMemberWriter::class)->update($sub->user, ['commercial_access_enabled' => false]);
    }

    public function test_super_admin_can_set_up_personal_coaching_for_a_client_in_the_coaching_space(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = User::factory()->create(['role' => 'client']);
        config(['commerce.coaching_gym_id' => $client->gym_id]);
        $this->actingAs($admin)->post('/gestion-comercial/asesorias', ['user_id' => $client->id, 'plan_code' => 'asesoria'])->assertRedirect();
        $this->assertDatabaseHas('commercial_subscriptions', ['user_id' => $client->id, 'kind' => 'coaching', 'amount_cents' => 6990000]);
        $this->actingAs($client)->get('/app')->assertRedirect('/mi-suscripcion');
        $this->get('/mi-suscripcion')->assertOk()->assertSee('Asesoría mensual');
    }

    public function test_owner_can_reconcile_a_missed_payment_notification(): void
    {
        $sub = $this->enroll();
        $this->ready();
        $this->paymentFixture($sub);
        Http::fake([
            'api.mercadopago.com/preapproval/preapproval-demo' => Http::response(['id' => 'preapproval-demo', 'external_reference' => $sub->public_id, 'status' => 'authorized']),
            'api.mercadopago.com/authorized_payments/search*' => Http::response(['results' => [['id' => 123]]]),
        ]);
        $this->post('/mi-suscripcion/actualizar')->assertRedirect();
        $this->assertNotNull($sub->fresh()->paid_until);
    }

    public function test_a_live_payer_cannot_be_deleted_without_first_stopping_the_renewal(): void
    {
        $sub = $this->enroll();
        $sub->update(['provider_id' => 'preapproval-demo']);
        $this->expectException(ValidationException::class);
        $sub->user->delete();
    }

    public function test_coaching_clients_can_still_have_their_training_profile_updated(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = User::factory()->create(['role' => 'client']);
        config(['commerce.coaching_gym_id' => $client->gym_id]);
        $this->actingAs($admin)->post('/gestion-comercial/asesorias', ['user_id' => $client->id, 'plan_code' => 'asesoria'])->assertRedirect();
        app(CommercialMemberWriter::class)->update($client, ['goals' => 'Registrar mi entrenamiento']);
        $this->assertSame('Registrar mi entrenamiento', $client->fresh()->goals);
    }

    public function test_provider_dates_with_a_timezone_offset_are_stored_as_the_correct_utc_instant(): void
    {
        $this->travelTo(Carbon::parse('2027-01-31 10:00:00', 'UTC'));
        $sub = $this->enroll();
        $sub->update(['provider_id' => 'offset-demo']);
        $this->ready();
        Http::fake([
            'api.mercadopago.com/authorized_payments/123' => Http::response(['id' => 123, 'preapproval_id' => 'offset-demo', 'debit_date' => '2027-01-31T10:00:00+00:00', 'payment' => ['id' => 456]]),
            'api.mercadopago.com/v1/payments/456' => Http::response(['id' => 456, 'status' => 'approved', 'transaction_amount' => 14900, 'currency_id' => 'ARS', 'date_approved' => '2027-01-31T07:00:00-03:00', 'date_last_updated' => '2027-01-31T07:00:00-03:00']),
        ]);
        $this->event('123')->assertOk();
        $this->assertSame('2027-01-31 10:00:00', $sub->payments()->firstOrFail()->period_start->utc()->format('Y-m-d H:i:s'));
    }

    public function test_a_newer_provider_refund_is_not_dropped_due_to_timezone_conversion(): void
    {
        $sub = $this->enroll();
        $this->ready();
        $sub->update(['provider_id' => 'preapproval-demo']);
        $snapshot = ['id' => 456, 'status' => 'approved', 'transaction_amount' => 14900, 'currency_id' => 'ARS', 'date_approved' => now()->toIso8601String(), 'date_last_updated' => now()->toIso8601String()];
        Http::fake([
            'api.mercadopago.com/authorized_payments/123' => Http::response(['id' => 123, 'preapproval_id' => 'preapproval-demo', 'debit_date' => now()->toIso8601String(), 'payment' => ['id' => 456]]),
            'api.mercadopago.com/v1/payments/456' => Http::sequence()->push($snapshot)->push(array_replace($snapshot, ['status' => 'refunded', 'date_last_updated' => now()->addSecond()->toIso8601String()])),
        ]);
        $this->event('123')->assertOk();
        $this->event('456', 'payment')->assertOk();
        $this->assertSame('refunded', $sub->payments()->firstOrFail()->status);
        $this->assertNull($sub->fresh()->paid_until);
    }
}
