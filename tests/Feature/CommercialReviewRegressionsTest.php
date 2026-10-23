<?php

namespace Tests\Feature;

use App\Filament\Resources\Gyms\Pages\ListGyms;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\CommercialPayment;
use App\Models\CommercialSubscription;
use App\Models\Gym;
use App\Models\User;
use App\Services\CommercialBilling;
use App\Services\CommercialMemberWriter;
use App\Services\RoutinePhotoReader;
use App\Support\CommercialCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommercialReviewRegressionsTest extends TestCase
{
    use RefreshDatabase;

    private function enroll(): CommercialSubscription
    {
        $this->post('/empezar/coach-inicial', ['catalog_quote' => CommercialCatalog::quote(), 'name' => 'Profesional', 'business_name' => 'Espacio', 'email' => 'owner@example.test', 'password' => 'Password-test-123!', 'password_confirmation' => 'Password-test-123!', 'privacy_accepted' => '1', 'terms_accepted' => '1'])->assertRedirect('/mi-suscripcion');

        return CommercialSubscription::firstOrFail();
    }

    public function test_filament_creation_cannot_check_a_different_gym_to_bypass_quotas(): void
    {
        $legacy = Gym::create(['name' => 'Otro gimnasio']);
        $sub = $this->enroll();
        config(['commerce.plans.coach-inicial.students' => 0]);
        Livewire::test(CreateUser::class)->fillForm(['gym_id' => $legacy->id, 'name' => 'Alumno', 'email' => 'forged@example.test', 'role' => 'client', 'password' => 'Password123!', 'commercial_access_enabled' => true])
            ->call('create')->assertHasFormErrors(['gym_id']);
        $this->assertDatabaseMissing('users', ['email' => 'forged@example.test']);
        $this->assertSame(1, $sub->gym->users()->count());
    }

    public static function forgedUpdates(): array
    {
        return ['reactivation' => [false, 'client', ['commercial_access_enabled' => true]], 'staff-role' => [true, 'client', ['role' => 'coach']]];
    }

    #[DataProvider('forgedUpdates')]
    public function test_filament_updates_cannot_bypass_quotas_with_another_gym(bool $enabled, string $role, array $changes): void
    {
        $legacy = Gym::create(['name' => 'Otro gimnasio']);
        $sub = $this->enroll();
        $member = User::factory()->create(['gym_id' => $sub->gym_id, 'role' => $role, 'commercial_access_enabled' => $enabled]);
        config(['commerce.plans.coach-inicial.students' => 0, 'commerce.plans.coach-inicial.staff' => 1]);
        Livewire::test(EditUser::class, ['record' => $member->id])->fillForm($changes + ['gym_id' => $legacy->id])->call('save')->assertHasFormErrors(['gym_id']);
        $this->assertSame($role, $member->fresh()->role);
        $this->assertSame($enabled, $member->fresh()->commercial_access_enabled);
    }

    public function test_writer_rejects_editing_a_member_from_another_tenant(): void
    {
        $other = User::factory()->create(['role' => 'client']);
        $this->enroll();
        $this->expectException(ValidationException::class);
        app(CommercialMemberWriter::class)->update($other, ['goals' => 'No autorizado']);
    }

    public function test_authenticated_qr_signup_cannot_create_an_account_in_a_different_tenant(): void
    {
        $legacy = Gym::create(['name' => 'Otro gimnasio', 'active' => true]);
        $sub = $this->enroll();
        config(['commerce.plans.coach-inicial.students' => 0]);
        $this->post('/unirse/'.$legacy->invite_code, ['name' => 'Alumno', 'email' => 'qr-forged@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'privacy_accepted' => '1', 'terms_accepted' => '1'])->assertRedirect('/admin');
        $this->assertDatabaseMissing('users', ['email' => 'qr-forged@example.test']);
        $this->assertAuthenticatedAs($sub->user);
    }

    public static function deletionModes(): array
    {
        return ['single' => [false], 'bulk' => [true]];
    }

    #[DataProvider('deletionModes')]
    public function test_deleting_a_gym_preserves_live_coaching_payers(bool $bulk): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $sub = CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $client->id, 'kind' => 'coaching', 'plan_code' => 'asesoria', 'amount_cents' => 6990000, 'terms_version' => 'test', 'provider_id' => 'coaching-live']);
        $root = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($root);
        try {
            if ($bulk) {
                Livewire::test(ListGyms::class)->callTableBulkAction('delete', [$client->gym]);
            } else {
                $client->gym->delete();
            }
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('gym', $exception->errors());
        }
        $this->assertModelExists($client);
        $this->assertModelExists($sub);
        $sub->update(['cancel_at_period_end' => true]);
        $client->gym->delete();
        $this->assertModelMissing($sub);
    }

    public static function blockedProfessionals(): array
    {
        return ['expired-owner' => [true], 'archived-coach' => [false]];
    }

    #[DataProvider('blockedProfessionals')]
    public function test_blocked_professionals_cannot_read_documents_with_ai(bool $expired): void
    {
        $sub = $this->enroll();
        if ($expired) {
            $sub->update(['trial_ends_at' => now()->subDay()]);
        } else {
            $coach = User::factory()->create(['gym_id' => $sub->gym_id, 'role' => 'coach', 'commercial_access_enabled' => false]);
            $this->actingAs($coach);
        }
        $this->mock(RoutinePhotoReader::class)->shouldNotReceive('read');
        $get = $this->get('/rutinas/desde-foto');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3ioAAAAASUVORK5CYII=');
        $post = $this->post('/rutinas/desde-foto', ['photo' => UploadedFile::fake()->createWithContent('rutina.png', $png), 'photo_processing_consent' => '1']);
        if ($expired) {
            $get->assertRedirect('/mi-suscripcion');
            $post->assertRedirect('/mi-suscripcion');
        } else {
            $get->assertForbidden();
            $post->assertForbidden();
        }
    }

    public function test_legacy_professionals_keep_document_access(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/rutinas/desde-foto')->assertOk();
    }

    public static function paymentOrdering(): array
    {
        return ['refunded-wins' => ['approved', 'refunded', false], 'approved-wins' => ['rejected', 'approved', true]];
    }

    #[DataProvider('paymentOrdering')]
    public function test_delayed_old_payment_snapshots_cannot_overwrite_newer_events(string $older, string $newer, bool $access): void
    {
        $sub = $this->enroll();
        $sub->update(['trial_ends_at' => now()->subDay(), 'provider_id' => 'preapproval-demo']);
        CommercialPayment::create(['commercial_subscription_id' => $sub->id, 'provider_payment_id' => '456', 'status' => $older, 'amount_cents' => $sub->amount_cents, 'currency' => 'ARS', 'period_start' => now(), 'period_end' => now()->addMonth()]);
        $calls = 0;
        Http::fake(function () use (&$calls, $older, $newer) {
            $first = ++$calls === 1;
            if ($first) {
                app(CommercialBilling::class)->syncPayment('456');
            }

            return Http::response(['id' => 456, 'status' => $first ? $older : $newer, 'transaction_amount' => 14900, 'currency_id' => 'ARS', 'date_approved' => now()->toIso8601String(), 'date_last_updated' => $first ? '2026-10-23T10:00:00.100-03:00' : '2026-10-23T10:00:00.200-03:00']);
        });
        app(CommercialBilling::class)->syncPayment('456');
        $this->assertSame(2, $calls);
        $this->assertDatabaseHas('commercial_payments', ['provider_payment_id' => '456', 'status' => $newer]);
        $this->assertSame($access, $sub->fresh()->hasAccess());
    }
}
