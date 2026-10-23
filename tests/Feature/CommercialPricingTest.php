<?php

namespace Tests\Feature;

use App\Models\CommercialSettings;
use App\Models\CommercialSubscription;
use App\Models\User;
use App\Support\CommercialCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommercialPricingTest extends TestCase
{
    use RefreshDatabase;

    private function root(): User
    {
        $root = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($root);

        return $root;
    }

    private function data(int $version = 0): array
    {
        return [
            'catalog_version' => $version,
            'prices' => ['coach-inicial' => '20000.50', 'coach-profesional' => '24900', 'gym-inicial' => '34900', 'gym-profesional' => '59900', 'asesoria' => '69900', 'asesoria-premium' => '109900', 'rutina-puntual' => '29900'],
            'promotion' => ['title' => 'Semana VisionFit', 'description' => 'Empezá con un plan.', 'percent' => 10, 'plan_codes' => ['coach-inicial']],
        ];
    }

    private function save(array $data): void
    {
        $this->post('/gestion-comercial/precios', $data)->assertRedirect('/gestion-comercial/precios')->assertSessionHasNoErrors();
    }

    private function enable(int $version = 1): void
    {
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => '1', 'catalog_version' => $version])->assertRedirect('/gestion-comercial/precios')->assertSessionHasNoErrors();
    }

    public function test_guests_and_non_root_roles_cannot_read_or_change_the_catalog(): void
    {
        $this->get('/gestion-comercial/precios')->assertRedirect('/login');
        $this->post('/gestion-comercial/precios', $this->data())->assertRedirect('/login');
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => 1, 'catalog_version' => 0])->assertRedirect('/login');
        foreach (['admin', 'coach', 'client'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get('/gestion-comercial/precios')->assertForbidden();
            $this->post('/gestion-comercial/precios', $this->data())->assertForbidden();
            $this->post('/gestion-comercial/precios/promocion', ['enabled' => 1, 'catalog_version' => 0])->assertForbidden();
        }
        $this->assertDatabaseCount('commercial_operations', 0);
    }

    public function test_root_edits_prices_and_public_pages_refresh_without_a_deploy(): void
    {
        $root = $this->root();
        $this->get('/planes')->assertSee('$14.900'); // Warm the shared settings cache.
        $this->save($this->data());
        $this->get('/gestion-comercial/precios')->assertOk()->assertSee('Precios y promociones')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/planes')->assertSee('$20.000,50')->assertDontSee('data-promotion-banner', false);
        $this->get('/software-entrenadores-personales')->assertSee('$20.000,50');
        $this->get('/empezar/coach-inicial')->assertSee('$20.000,50');
        $this->assertDatabaseHas('commercial_operations', ['actor_id' => $root->id, 'action' => 'catalog_updated']);
    }

    public function test_one_click_activates_a_real_discount_only_for_selected_plans(): void
    {
        $this->root();
        $this->save($this->data());
        $this->enable();
        $this->get('/')->assertSee('data-promotion-banner', false)->assertSee('10 % de descuento')->assertSee('Semana VisionFit');
        $this->get('/planes')->assertSee('$18.000,45')->assertSee('$20.000,50')->assertSee('$34.900')->assertDontSee('$31.410');
        $this->get('/empezar/coach-inicial')->assertSee('$18.000,45');
        $this->assertDatabaseHas('commercial_operations', ['action' => 'promotion_enabled']);
    }

    public function test_one_click_disables_the_offer_and_preserves_its_configuration(): void
    {
        $this->root();
        $this->save($this->data());
        $this->enable();
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => '0', 'catalog_version' => 2])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/planes')->assertSee('$20.000,50')->assertDontSee('$18.000,45')->assertDontSee('data-promotion-banner', false);
        $this->get('/gestion-comercial/precios')->assertSee('Semana VisionFit')->assertSee('Activar descuento');
        $this->assertDatabaseHas('commercial_operations', ['action' => 'promotion_disabled']);
    }

    public function test_details_can_be_updated_while_active_without_resetting_the_switch(): void
    {
        $this->root();
        $this->save($this->data());
        $this->enable();
        $data = $this->data(2);
        $data['promotion']['percent'] = 15;
        $data['promotion']['title'] = 'Nueva oferta';
        $this->save($data);
        $this->get('/planes')->assertSee('15 % de descuento')->assertSee('Nueva oferta')->assertSee('$17.000,43');
    }

    public function test_stale_forms_and_duplicate_switch_submissions_cannot_overwrite_newer_changes(): void
    {
        $this->root();
        $this->save($this->data());
        $stale = $this->data();
        $stale['prices']['coach-inicial'] = '1';
        $this->post('/gestion-comercial/precios', $stale)->assertSessionHasErrors('catalog_version');
        $this->enable();
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => '1', 'catalog_version' => 1])->assertSessionHasErrors('catalog_version');
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => '0', 'catalog_version' => 1])->assertSessionHasErrors('catalog_version');
        $this->get('/planes')->assertSee('$18.000,45')->assertSee('data-promotion-banner', false);
        $this->assertDatabaseCount('commercial_operations', 2);
    }

    public function test_catalog_writes_preserve_business_and_reminder_settings_and_ignore_secrets(): void
    {
        $root = $this->root();
        CommercialSettings::saveValues(['operator_name' => 'Titular existente', 'reminders_enabled' => true, 'reminder_recipient_id' => $root->id]);
        $data = $this->data();
        $data['operator_name'] = 'No sustituir';
        $data['smtp_password'] = 'NO-PERSISTIR';
        $data['enabled'] = true;
        $this->save($data);
        $values = CommercialSettings::findOrFail(1)->values;
        $this->assertSame('Titular existente', $values['operator_name']);
        $this->assertTrue($values['reminders_enabled']);
        $this->assertSame($root->id, $values['reminder_recipient_id']);
        $this->assertArrayNotHasKey('smtp_password', $values);
        $this->get('/planes')->assertDontSee('data-promotion-banner', false);
        $this->post('/gestion-comercial/configuracion', ['operator_name' => 'Titular actualizado'])->assertRedirect();
        $this->get('/planes')->assertSee('$20.000,50');
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_prices_are_rejected_atomically(string $amount): void
    {
        $this->root();
        $data = $this->data();
        $data['prices']['coach-inicial'] = $amount;
        $this->post('/gestion-comercial/precios', $data)->assertSessionHasErrors('prices.coach-inicial');
        $this->assertDatabaseCount('commercial_operations', 0);
        $this->get('/planes')->assertSee('$14.900')->assertDontSee('$20.000,50');
    }

    public static function invalidPrices(): array
    {
        return [['0'], ['-1'], ['1e3'], ['0.001'], ['999999999.99'], ['1000000.01'], ['20.000,50']];
    }

    #[DataProvider('invalidPromotion')]
    public function test_invalid_offers_are_rejected(string $field, mixed $value, string $error): void
    {
        $this->root();
        $data = $this->data();
        $data['promotion'][$field] = $value;
        $this->post('/gestion-comercial/precios', $data)->assertSessionHasErrors($error);
        $this->assertDatabaseCount('commercial_operations', 0);
    }

    public static function invalidPromotion(): array
    {
        return [['percent', 0, 'promotion.percent'], ['percent', 100, 'promotion.percent'], ['percent', '10.5', 'promotion.percent'], ['plan_codes', [], 'promotion.plan_codes'], ['plan_codes', ['inventado'], 'promotion.plan_codes.0'], ['plan_codes', ['coach-inicial', 'coach-inicial'], 'promotion.plan_codes.0'], ['title', str_repeat('x', 121), 'promotion.title']];
    }

    public function test_unknown_nested_price_and_promotion_fields_are_rejected(): void
    {
        $this->root();
        $data = $this->data();
        $data['prices']['inventado'] = '10';
        $data['promotion']['enabled'] = true;
        $this->post('/gestion-comercial/precios', $data)->assertSessionHasErrors(['prices', 'promotion']);
        $this->assertDatabaseCount('commercial_operations', 0);
    }

    public function test_html_is_escaped_and_the_banner_stays_out_of_private_and_legal_pages(): void
    {
        $this->root();
        $data = $this->data();
        $data['promotion']['title'] = '<script>alert(1)</script>';
        $this->save($data);
        $this->enable();
        $this->get('/')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        foreach (['/privacidad', '/gestion-comercial/precios', '/mi-suscripcion'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('data-promotion-banner', false);
        }
    }

    public function test_a_discount_that_would_make_the_price_zero_cannot_be_activated(): void
    {
        $this->root();
        $data = $this->data();
        $data['prices']['coach-inicial'] = '0.01';
        $data['promotion']['percent'] = 99;
        $this->save($data);
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => '1', 'catalog_version' => 1])->assertSessionHasErrors('promotion');
        $this->get('/planes')->assertSee('$0,01')->assertDontSee('data-promotion-banner', false);
    }

    public function test_discount_rounding_uses_centavos_and_half_up(): void
    {
        $this->root();
        $data = $this->data();
        $data['prices']['coach-inicial'] = '0.15';
        $this->save($data);
        $this->enable();
        $this->get('/planes')->assertSee('$0,14')->assertSee('$0,15');
    }

    public function test_the_one_off_routine_has_its_own_editable_price_and_discount_scope(): void
    {
        $this->root();
        $data = $this->data();
        $data['prices']['rutina-puntual'] = '99.99';
        $data['promotion']['plan_codes'] = ['rutina-puntual'];
        $data['promotion']['percent'] = 15;
        $this->save($data);
        $this->enable();
        $this->get('/asesorias-online')->assertSee('$84,99')->assertSee('$99,99')->assertSee('$69.900');
        $this->get('/planes')->assertSee('$84,99')->assertSee('$20.000,50');
    }

    public function test_new_manual_agreements_use_the_published_amount_without_repricing_existing_accounts(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $this->root();
        $this->post('/gestion-comercial/membresias', ['catalog_quote' => CommercialCatalog::quote(), 'operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => 'coach-inicial', 'confirmed' => '1'])->assertSessionHasNoErrors();
        $sub = CommercialSubscription::where('user_id', $owner->id)->firstOrFail();
        $this->save($this->data());
        $this->enable();
        $this->assertSame(1490000, $sub->fresh()->amount_cents);
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/plan', ['catalog_quote' => CommercialCatalog::quote(), 'operation_id' => (string) Str::uuid(), 'plan_code' => 'coach-inicial', 'reason' => 'Nuevo precio acordado con titular', 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(1800045, $sub->fresh()->next_amount_cents);
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/cobros', ['operation_id' => (string) Str::uuid(), 'amount' => '18000.45', 'months' => 1, 'method' => 'cash', 'paid_on' => now()->toDateString(), 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('commercial_payments', ['commercial_subscription_id' => $sub->id, 'amount_cents' => 1800045]);
        $this->actingAs($owner)->get('/mi-suscripcion')->assertSee('$18.000,45');
    }

    public function test_new_enrollment_uses_the_same_discount_as_the_public_price(): void
    {
        $this->root();
        $this->save($this->data());
        $this->enable();
        auth()->logout();
        $this->post('/empezar/coach-inicial', ['catalog_quote' => CommercialCatalog::quote(), 'name' => 'Nueva cuenta', 'business_name' => 'Nuevo espacio', 'email' => 'alta@example.test', 'password' => 'password-segura-10', 'password_confirmation' => 'password-segura-10', 'privacy_accepted' => '1', 'terms_accepted' => '1'])->assertRedirect('/mi-suscripcion')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('commercial_subscriptions', ['plan_code' => 'coach-inicial', 'amount_cents' => 1800045]);
        $this->get('/mi-suscripcion')->assertSee('$18.000,45');
    }

    private function displayedQuote(string $path): string
    {
        preg_match('/name="catalog_quote" value="([^"]+)"/', $this->get($path)->assertOk()->getContent(), $match);

        return html_entity_decode($match[1] ?? 'missing-quote');
    }

    public function test_a_changed_price_requires_reconfirmation_before_scheduling_a_plan(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $this->root();
        $this->post('/gestion-comercial/membresias', ['catalog_quote' => CommercialCatalog::quote(), 'operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => 'coach-inicial', 'confirmed' => '1'])->assertSessionHasNoErrors();
        $sub = CommercialSubscription::where('user_id', $owner->id)->firstOrFail();
        $this->save($this->data());
        $this->enable();
        $quote = $this->displayedQuote('/gestion-comercial/membresias/'.$sub->id);
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => 0, 'catalog_version' => 2])->assertSessionHasNoErrors();
        $this->post('/gestion-comercial/membresias/'.$sub->id.'/plan', ['catalog_quote' => $quote, 'operation_id' => (string) Str::uuid(), 'plan_code' => 'coach-inicial', 'reason' => 'Confirmar el precio que estaba publicado', 'confirmed' => 1])->assertSessionHasErrors('catalog_quote');
        $this->assertNull($sub->fresh()->next_amount_cents);
        $this->assertSame(1490000, $sub->fresh()->amount_cents);
    }

    public function test_a_changed_catalog_cannot_silently_reprice_an_open_assignment_form(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $this->root();
        $quote = $this->displayedQuote('/gestion-comercial/membresias');
        $this->save($this->data());
        $this->post('/gestion-comercial/membresias', ['catalog_quote' => $quote, 'operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => 'coach-inicial', 'confirmed' => 1])->assertSessionHasErrors('catalog_quote');
        $this->assertDatabaseCount('commercial_subscriptions', 0);
    }

    public function test_a_stale_signup_quote_does_not_create_an_account_at_a_different_price(): void
    {
        $root = $this->root();
        $this->save($this->data());
        $this->enable();
        auth()->logout();
        $quote = $this->displayedQuote('/empezar/coach-inicial');
        $this->actingAs($root)->post('/gestion-comercial/precios/promocion', ['enabled' => 0, 'catalog_version' => 2])->assertSessionHasNoErrors();
        auth()->logout();
        $this->post('/empezar/coach-inicial', ['catalog_quote' => $quote, 'name' => 'Alta obsoleta', 'business_name' => 'Espacio', 'email' => 'viejo@example.test', 'password' => 'password-segura-10', 'password_confirmation' => 'password-segura-10', 'privacy_accepted' => 1, 'terms_accepted' => 1])->assertSessionHasErrors('catalog_quote');
        $this->assertDatabaseMissing('users', ['email' => 'viejo@example.test']);
        $this->assertDatabaseCount('commercial_subscriptions', 0);
    }

    public function test_missing_or_forged_quotes_cannot_create_price_agreements(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $this->root();
        foreach ([null, str_repeat('0', 64)] as $quote) {
            $this->post('/gestion-comercial/membresias', ['catalog_quote' => $quote, 'operation_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'plan_code' => 'coach-inicial', 'confirmed' => 1])->assertSessionHasErrors('catalog_quote');
        }
        $this->assertDatabaseCount('commercial_subscriptions', 0);
    }

    public function test_a_discount_must_produce_actual_savings_before_it_is_advertised(): void
    {
        $this->root();
        $data = $this->data();
        $data['prices']['coach-inicial'] = '0.01';
        $this->save($data);
        $this->post('/gestion-comercial/precios/promocion', ['enabled' => 1, 'catalog_version' => 1])->assertSessionHasErrors('promotion');
        $this->get('/planes')->assertDontSee('data-promotion-banner', false);
    }

    public function test_invalid_nested_input_returns_a_usable_editor_after_validation(): void
    {
        $this->root();
        $data = $this->data();
        $data['catalog_version'] = ['nested' => 'invalid'];
        $data['prices']['coach-inicial'] = ['nested' => 'invalid'];
        $data['promotion']['title'] = ['nested' => 'invalid'];
        $data['promotion']['description'] = ['nested' => 'invalid'];
        $data['promotion']['percent'] = ['nested' => 'invalid'];
        $data['promotion']['plan_codes'] = 'invalid';
        $this->from('/gestion-comercial/precios')->post('/gestion-comercial/precios', $data)->assertRedirect('/gestion-comercial/precios')->assertSessionHasErrors();
        $this->get('/gestion-comercial/precios')->assertOk()->assertSee('Guardar precios y detalles');
        $this->assertDatabaseCount('commercial_operations', 0);
    }
}
