<?php

namespace Tests\Feature;

use App\Mail\CommercialReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommercialInquiriesTest extends TestCase
{
    use RefreshDatabase;

    private function details(): array
    {
        return ['name' => 'Consulta de prueba', 'email' => 'consulta@example.test', 'audience' => 'gym', 'message' => 'Quiero una demo', 'privacy_accepted' => '1'];
    }

    public function test_contact_persists_a_private_request_and_shows_a_reference(): void
    {
        $this->post('/contacto', $this->details())->assertRedirect();
        $this->assertDatabaseHas('commercial_inquiries', ['email' => 'consulta@example.test', 'type' => 'contact', 'status' => 'new']);
        $this->get('/contacto/recibido')->assertOk()->assertSee('Referencia')->assertDontSee('consulta@example.test');
    }

    public function test_privacy_acknowledgement_is_required_and_honeypot_does_not_store(): void
    {
        $details = $this->details();
        unset($details['privacy_accepted']);
        $this->post('/contacto', $details)->assertSessionHasErrors('privacy_accepted');
        $this->post('/contacto', $this->details() + ['website' => 'spam'])->assertRedirect();
        $this->assertDatabaseCount('commercial_inquiries', 0);
    }

    public function test_consumer_and_privacy_requests_do_not_require_an_account(): void
    {
        foreach (['baja', 'arrepentimiento', 'privacidad/solicitud'] as $path) {
            $this->get('/'.$path)->assertOk();
            $this->post('/'.$path, $this->details())->assertRedirect();
        }
        $this->assertDatabaseCount('commercial_inquiries', 3);
    }

    public function test_only_super_admin_can_read_or_change_requests(): void
    {
        $this->post('/contacto', $this->details());
        $client = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($client)->get('/gestion-comercial')->assertForbidden();
        $this->post('/gestion-comercial/solicitudes/1', ['status' => 'closed'])->assertForbidden();
        $this->actingAs($admin)->get('/gestion-comercial')->assertOk()->assertSee('consulta@example.test');
        $this->post('/gestion-comercial/solicitudes/1', ['status' => 'closed'])->assertRedirect();
        $this->assertDatabaseHas('commercial_inquiries', ['id' => 1, 'status' => 'closed']);
    }

    public function test_consumer_rights_do_not_require_an_additional_consent_checkbox(): void
    {
        $data = $this->details();
        unset($data['privacy_accepted']);
        $this->post('/baja', $data)->assertRedirect();
        $this->assertDatabaseHas('commercial_inquiries', ['email' => $data['email'], 'type' => 'baja']);
    }

    public function test_configured_mail_sends_a_reference_receipt_and_notifies_the_operator(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'commerce.inbox_email' => 'operador@example.test']);
        $this->post('/contacto', $this->details())->assertRedirect();
        Mail::assertSent(CommercialReceipt::class, fn ($mail) => $mail->hasTo('consulta@example.test'));
        Mail::assertSent(CommercialReceipt::class, fn ($mail) => $mail->hasTo('operador@example.test'));
    }
}
