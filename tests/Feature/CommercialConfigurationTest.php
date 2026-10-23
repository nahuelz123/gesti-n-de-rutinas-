<?php

namespace Tests\Feature;

use App\Models\CommercialSettings;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_configuration_is_private_and_root_only(): void
    {
        $this->get('/gestion-comercial/configuracion')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/gestion-comercial/configuracion')->assertForbidden();
        $this->post('/gestion-comercial/configuracion', ['operator_name' => 'Cambio ajeno'])->assertForbidden();
    }

    public function test_root_can_configure_real_business_data_without_storing_gateway_secrets(): void
    {
        $gym = Gym::create(['name' => 'Espacio personal']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->get('/gestion-comercial/configuracion')->assertOk();
        $this->post('/gestion-comercial/configuracion', [
            'operator_name' => 'Responsable de prueba', 'contact_email' => 'contacto@example.test',
            'operator_address' => 'Domicilio de prueba', 'tax_id' => 'CUIT informado',
            'manual_payment_instructions' => 'Transferencia al alias de prueba <b>sin HTML</b>',
            'coaching_gym_id' => $gym->id, 'mercadopago_token' => 'NO-PERSISTIR', 'smtp_password' => 'NO-PERSISTIR',
        ])->assertRedirect('/gestion-comercial/configuracion');
        $values = CommercialSettings::values();
        $this->assertSame('Responsable de prueba', $values['operator_name']);
        $this->assertSame($gym->id, (int) $values['coaching_gym_id']);
        $this->assertArrayNotHasKey('mercadopago_token', $values);
        $this->assertArrayNotHasKey('smtp_password', $values);
        $this->get('/gestion-comercial/configuracion')->assertSee('&lt;b&gt;sin HTML&lt;/b&gt;', false)->assertDontSee('NO-PERSISTIR');
        auth()->logout();
        $this->get('/privacidad')->assertOk()->assertSee('Responsable de prueba')->assertSee('contacto@example.test')->assertSee('Domicilio de prueba');
    }

    public function test_blank_configuration_keeps_environment_defaults(): void
    {
        config(['legal.operator_name' => 'Configuración existente', 'legal.contact_email' => 'existente@example.test']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->post('/gestion-comercial/configuracion', ['operator_name' => '', 'contact_email' => ''])->assertRedirect();
        $this->get('/privacidad')->assertSee('Configuración existente')->assertSee('existente@example.test');
    }

    public function test_invalid_contacts_and_nonexistent_coaching_spaces_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->post('/gestion-comercial/configuracion', ['contact_email' => 'correo inválido', 'coaching_gym_id' => 999999])->assertSessionHasErrors(['contact_email', 'coaching_gym_id']);
    }

    public function test_clearing_settings_restores_defaults_without_leaking_the_previous_request(): void
    {
        config(['legal.operator_name' => 'Titular original']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->post('/gestion-comercial/configuracion', ['operator_name' => 'Titular actualizado'])->assertRedirect();
        $this->get('/privacidad')->assertSee('Titular actualizado');
        $this->post('/gestion-comercial/configuracion', ['operator_name' => ''])->assertRedirect();
        $this->get('/privacidad')->assertSee('Titular original')->assertDontSee('Titular actualizado');
    }
}
