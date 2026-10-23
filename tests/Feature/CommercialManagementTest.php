<?php

namespace Tests\Feature;

use App\Models\CommercialPayment;
use App\Models\CommercialSettings;
use App\Models\CommercialSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommercialManagementTest extends TestCase
{
    use RefreshDatabase;

    private function membership(string $name = 'Titular de prueba'): CommercialSubscription
    {
        $this->travelTo(Carbon::parse('2027-01-31 10:00:00'));
        $owner = User::factory()->create(['role' => 'admin', 'name' => $name, 'medical_notes' => 'DATOS-MEDICOS-NO-COMERCIALES']);

        return CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'gym_id' => $owner->gym_id, 'kind' => 'software', 'plan_code' => 'coach-inicial', 'amount_cents' => 1490000, 'currency' => 'ARS', 'billing_mode' => 'manual', 'status' => 'awaiting_payment', 'terms_version' => 'test']);
    }

    private function root(): User
    {
        $root = User::factory()->create(['role' => 'super_admin', 'name' => 'Operador comercial']);
        $this->actingAs($root);

        return $root;
    }

    private function pay(CommercialSubscription $sub): CommercialPayment
    {
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', ['operation_id' => (string) Str::uuid(), 'amount' => '14900', 'months' => 1, 'method' => 'transfer', 'reference' => 'MOV-'.$sub->id, 'paid_on' => '2027-01-31', 'confirmed' => '1'])->assertRedirect()->assertSessionHasNoErrors();

        return $sub->payments()->firstOrFail();
    }

    public function test_membership_management_and_exports_are_root_only(): void
    {
        $sub = $this->membership();
        $this->get('/gestion-comercial/membresias')->assertRedirect('/login');
        $this->actingAs($sub->user)->get('/gestion-comercial/membresias')->assertForbidden();
        $this->get('/gestion-comercial/membresias/'.$sub->id)->assertForbidden();
        $this->get('/gestion-comercial/movimientos.csv')->assertForbidden();
    }

    public function test_root_can_find_memberships_and_filter_overdue_access(): void
    {
        $expired = $this->membership('Espacio vencido');
        $expired->update(['paid_until' => now()->subDays(8)]);
        $active = $this->membership('Espacio vigente');
        $active->update(['trial_ends_at' => now()->addDays(5)]);
        $this->root();
        $this->get('/gestion-comercial/membresias?search=vencido')->assertOk()->assertSee('Espacio vencido')->assertDontSee('Espacio vigente')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/gestion-comercial/membresias?state=overdue')->assertOk()->assertSee('Espacio vencido')->assertDontSee('Espacio vigente');
    }

    public function test_management_list_is_paginated(): void
    {
        for ($i = 0; $i < 22; $i++) {
            $this->membership('Cuenta número '.$i);
        }
        $this->root();
        $response = $this->get('/gestion-comercial/membresias')->assertOk();
        $this->assertSame(20, $response->viewData('subscriptions')->count());
        $this->assertSame(22, $response->viewData('subscriptions')->total());
    }

    public function test_detail_forms_show_recorded_money_and_audit_without_health_notes(): void
    {
        $sub = $this->membership();
        $this->root();
        $this->get('/gestion-comercial/membresias/'.$sub->id)->assertOk()->assertSee('Registrar cobro recibido')->assertSee('name="operation_id"', false);
        $this->pay($sub);
        $this->get('/gestion-comercial/membresias/'.$sub->id)->assertOk()->assertSee('28/02/2027')->assertSee('Operador comercial')->assertSee('MOV-'.$sub->id)->assertDontSee('DATOS-MEDICOS-NO-COMERCIALES');
    }

    public function test_owner_sees_manual_instructions_and_is_not_offered_a_duplicate_checkout(): void
    {
        $sub = $this->membership();
        $this->root();
        $this->pay($sub);
        CommercialSettings::saveValues(['manual_payment_instructions' => 'Alias de pago: prueba.visionfit <script>NO-EJECUTAR</script>']);
        config(['commerce.mercadopago_token' => 'TEST-token', 'commerce.mercadopago_webhook_secret' => 'secret', 'legal.operator_name' => 'Prueba', 'legal.contact_email' => 'contacto@example.test']);
        $this->actingAs($sub->user)->get('/mi-suscripcion')->assertOk()->assertSee('Alias de pago: prueba.visionfit')->assertSee('&lt;script&gt;NO-EJECUTAR&lt;/script&gt;', false)->assertDontSee('<script>NO-EJECUTAR</script>', false)->assertDontSee('Contratar con Mercado Pago')->assertSee('Comprobante');
    }

    public function test_receipts_are_private_to_owner_and_root_even_within_the_same_gym(): void
    {
        $sub = $this->membership();
        $this->root();
        $payment = $this->pay($sub);
        $path = '/mi-suscripcion/comprobantes/'.$payment->public_id;
        $this->get($path)->assertOk()->assertSee('Registro interno')->assertDontSee('DATOS-MEDICOS-NO-COMERCIALES');
        $this->actingAs($sub->user)->get($path)->assertOk()->assertSee('14.900')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $peer = User::factory()->create(['gym_id' => $sub->gym_id, 'role' => 'coach']);
        $this->actingAs($peer)->get($path)->assertForbidden();
        auth()->logout();
        $this->get($path)->assertRedirect('/login');
    }

    public function test_root_can_attach_a_fiscal_reference_later_without_changing_the_payment_amount(): void
    {
        $sub = $this->membership();
        $this->root();
        $payment = $this->pay($sub);
        $this->post('/gestion-comercial/pagos/'.$payment->id.'/referencia-fiscal', ['operation_id' => (string) Str::uuid(), 'fiscal_reference' => 'Factura C 0001-00000025', 'confirmed' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1490000, $payment->fresh()->amount_cents);
        $this->actingAs($sub->user)->get('/mi-suscripcion/comprobantes/'.$payment->public_id)->assertOk()->assertSee('Factura C 0001-00000025');
        $this->post('/gestion-comercial/pagos/'.$payment->id.'/referencia-fiscal', ['operation_id' => (string) Str::uuid(), 'fiscal_reference' => 'Cambio ajeno', 'confirmed' => '1'])->assertForbidden();
    }

    public function test_csv_export_escapes_spreadsheet_formulas_and_includes_refunds_as_separate_movements(): void
    {
        $name = '=HYPERLINK("https://example.invalid")';
        $sub = $this->membership($name);
        $this->root();
        $payment = $this->pay($sub);
        $this->post('/gestion-comercial/pagos/'.$payment->id.'/reintegros', ['operation_id' => (string) Str::uuid(), 'amount' => '900', 'reason' => 'Reintegro parcial realizado', 'performed_on' => '2027-01-31', 'confirmed' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $response = $this->get('/gestion-comercial/movimientos.csv?from=2027-01-01&to=2027-01-31')->assertOk();
        $body = $response->streamedContent();
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $body);
        rewind($stream);
        fgetcsv($stream, null, ',', '"', '');
        $paid = fgetcsv($stream, null, ',', '"', '');
        $refunded = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);
        $this->assertSame("'".$name, $paid[2]);
        $this->assertSame('14900.00', $paid[5]);
        $this->assertSame('Reintegro', $refunded[1]);
        $this->assertSame('-900.00', $refunded[5]);
        $this->assertStringNotContainsString('DATOS-MEDICOS-NO-COMERCIALES', $body);
    }
}
