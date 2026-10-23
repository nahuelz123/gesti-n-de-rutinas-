<?php

namespace Tests\Feature;

use App\Models\CommercialSettings;
use App\Models\CommercialSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommercialRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function membership(string $name, array $changes = []): CommercialSubscription
    {
        $this->travelTo(Carbon::parse('2027-01-31 10:00:00'));
        $owner = User::factory()->create(['role' => 'admin', 'name' => $name, 'medical_notes' => 'SALUD-NO-COMERCIAL']);
        $owner->gym->forceFill(['billing_managed' => true])->save();

        return CommercialSubscription::create(array_replace(['public_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'gym_id' => $owner->gym_id, 'kind' => 'software', 'plan_code' => 'coach-inicial', 'amount_cents' => 1490000, 'currency' => 'ARS', 'status' => 'active', 'billing_mode' => 'manual', 'paid_until' => '2027-02-01 10:00:00', 'terms_version' => 'test'], $changes));
    }

    private function root(): User
    {
        $root = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($root);

        return $root;
    }

    public function test_reminders_and_configuration_are_root_only(): void
    {
        $sub = $this->membership('Privada');
        $this->get('/gestion-comercial/recordatorios')->assertRedirect('/login');
        $this->actingAs($sub->user)->get('/gestion-comercial/recordatorios')->assertForbidden();
        $this->post('/gestion-comercial/recordatorios/configuracion', ['reminders_enabled' => '1', 'confirmed' => '1'])->assertForbidden();
    }

    public function test_calendar_window_includes_seven_days_and_thirty_days_overdue(): void
    {
        $this->membership('Siete días incluidos', ['paid_until' => '2027-02-07 23:59:59']);
        $this->membership('Ocho días excluidos', ['paid_until' => '2027-02-08 00:00:00']);
        $this->membership('Treinta días incluidos', ['paid_until' => '2027-01-01 00:00:00']);
        $this->membership('Treinta y uno excluidos', ['paid_until' => '2026-12-31 23:59:59']);
        $this->root();
        $r = $this->get('/gestion-comercial/recordatorios')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertSame(['Treinta días incluidos', 'Siete días incluidos'], $r->viewData('reminders')->pluck('user.name')->all());
        $r->assertDontSee('SALUD-NO-COMERCIAL');
    }

    public function test_provider_cancelled_and_unmanaged_legacy_accounts_are_excluded(): void
    {
        $this->membership('Proveedor', ['provider_id' => 'mp-123', 'billing_mode' => 'mercadopago']);
        $this->membership('Cancelada', ['cancel_at_period_end' => true]);
        $legacy = $this->membership('Anterior sin cobro');
        $legacy->gym->forceFill(['billing_managed' => false])->save();
        $this->membership('Asesoría incluida', ['kind' => 'coaching', 'gym_id' => null]);
        $this->root();
        $r = $this->get('/gestion-comercial/recordatorios')->assertOk();
        $this->assertSame(['Asesoría incluida'], $r->viewData('reminders')->pluck('user.name')->all());
    }

    public function test_trial_is_used_only_when_no_paid_period_exists(): void
    {
        $this->membership('Prueba próxima', ['paid_until' => null, 'trial_ends_at' => '2027-02-02 11:00:00']);
        $this->membership('Pago posterior a la prueba', ['paid_until' => '2027-03-01 10:00:00', 'trial_ends_at' => '2027-02-01 10:00:00']);
        $this->root();
        $r = $this->get('/gestion-comercial/recordatorios')->assertOk();
        $this->assertSame(['Prueba próxima'], $r->viewData('reminders')->pluck('user.name')->all());
        $r->assertSee('Prueba gratuita');
    }

    public function test_a_recorded_renewal_removes_the_previous_expiry(): void
    {
        $sub = $this->membership('Renovación de prueba');
        $this->root();
        $this->get('/gestion-comercial/recordatorios')->assertOk()->assertSee('Renovación de prueba');
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', ['operation_id' => (string) Str::uuid(), 'amount' => '14900', 'months' => 1, 'method' => 'cash', 'paid_on' => '2027-01-31', 'confirmed' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/gestion-comercial/recordatorios')->assertOk()->assertDontSee('Renovación de prueba');
    }

    public function test_email_activation_resolves_only_the_current_root_and_preserves_business_settings(): void
    {
        Mail::fake();
        CommercialSettings::saveValues(['operator_name' => 'Titular real', 'manual_payment_instructions' => 'Alias: visionfit.prueba']);
        $root = $this->root();
        $other = User::factory()->create(['role' => 'super_admin']);
        $this->post('/gestion-comercial/recordatorios/configuracion', ['reminders_enabled' => '1', 'confirmed' => '1', 'reminder_recipient_id' => $other->id, 'email' => 'otro@example.test'])->assertRedirect('/gestion-comercial/recordatorios')->assertSessionHasNoErrors();
        $values = CommercialSettings::values();
        $this->assertSame($root->id, $values['reminder_recipient_id']);
        $this->assertTrue((bool) $values['reminders_enabled']);
        $this->assertSame('Titular real', $values['operator_name']);
        $this->assertSame('Alias: visionfit.prueba', $values['manual_payment_instructions']);
        Mail::assertNothingSent();
        $this->post('/gestion-comercial/configuracion', ['operator_name' => 'Titular actualizado'])->assertRedirect();
        $this->assertSame($root->id, CommercialSettings::values()['reminder_recipient_id']);
        $this->assertTrue((bool) CommercialSettings::values()['reminders_enabled']);
    }

    public function test_activation_requires_explicit_confirmation_and_can_be_disabled(): void
    {
        $this->root();
        $this->post('/gestion-comercial/recordatorios/configuracion', ['reminders_enabled' => '1'])->assertSessionHasErrors('confirmed');
        $this->post('/gestion-comercial/recordatorios/configuracion', ['reminders_enabled' => '1', 'confirmed' => '1'])->assertRedirect();
        $this->post('/gestion-comercial/recordatorios/configuracion', ['reminders_enabled' => '0', 'confirmed' => '1'])->assertRedirect();
        $this->assertFalse((bool) CommercialSettings::values()['reminders_enabled']);
        $this->assertNull(CommercialSettings::values()['reminder_recipient_id']);
    }
}
