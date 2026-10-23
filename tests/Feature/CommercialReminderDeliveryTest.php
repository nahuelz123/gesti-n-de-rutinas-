<?php

namespace Tests\Feature;

use App\Mail\MembershipReminderDigest;
use App\Models\CommercialSettings;
use App\Models\CommercialSubscription;
use App\Models\User;
use App\Services\CommercialReminderDispatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CommercialReminderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function setupReminder(): array
    {
        $this->travelTo(Carbon::parse('2027-01-31 09:00:00'));
        $root = User::factory()->create(['role' => 'super_admin', 'email' => 'operador@example.test']);
        $owner = User::factory()->create(['role' => 'admin', 'name' => 'Titular que vence', 'medical_notes' => 'NO-INCLUIR-SALUD']);
        $owner->gym->forceFill(['billing_managed' => true])->save();
        $sub = CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'gym_id' => $owner->gym_id, 'kind' => 'software', 'plan_code' => 'coach-inicial', 'amount_cents' => 1490000, 'currency' => 'ARS', 'billing_mode' => 'manual', 'status' => 'active', 'paid_until' => '2027-02-01 10:00:00', 'terms_version' => 'test']);
        CommercialSettings::saveValues(['reminders_enabled' => true, 'reminder_recipient_id' => $root->id]);
        config(['mail.default' => 'smtp', 'commerce.public_url' => 'https://visionfit.example.test']);

        return [$root, $sub];
    }

    public function test_daily_digest_uses_operator_account_and_has_no_health_data(): void
    {
        [$root,$sub] = $this->setupReminder();
        Mail::fake();
        $this->artisan('memberships:remind')->assertSuccessful();
        Mail::assertSent(MembershipReminderDigest::class, function ($mail) use ($root) {
            $body = $mail->render();
            $this->assertStringContainsString('Titular que vence', $body);
            $this->assertStringContainsString('01/02/2027', $body);
            $this->assertStringContainsString('https://visionfit.example.test/gestion-comercial/recordatorios', $body);
            $this->assertStringNotContainsString('NO-INCLUIR-SALUD', $body);

            return $mail->hasTo($root->email);
        });
        Mail::assertSentCount(1);
        $this->assertDatabaseHas('commercial_reminder_runs', ['recipient_user_id' => $root->id, 'day' => '2027-01-31', 'status' => 'sent', 'item_count' => 1]);
        $this->assertDatabaseCount('commercial_payments', 0);
        $this->assertSame('2027-02-01 10:00:00', $sub->fresh()->paid_until->format('Y-m-d H:i:s'));
    }

    public function test_repeated_commands_attempt_once_per_day_and_next_day_can_send_again(): void
    {
        $this->setupReminder();
        Mail::fake();
        $this->artisan('memberships:remind')->assertSuccessful();
        $this->artisan('memberships:remind')->assertSuccessful();
        Mail::assertSentCount(1);
        $this->travelTo(Carbon::parse('2027-02-01 09:00:00'));
        $this->artisan('memberships:remind')->assertSuccessful();
        Mail::assertSentCount(2);
        $this->assertDatabaseCount('commercial_reminder_runs', 2);
    }

    public function test_disabled_or_unconfigured_mail_does_not_consume_the_daily_attempt(): void
    {
        $this->setupReminder();
        Mail::fake();
        CommercialSettings::saveValues(['reminders_enabled' => false]);
        $this->assertSame('disabled', app(CommercialReminderDispatcher::class)->dispatch());
        CommercialSettings::saveValues(['reminders_enabled' => true]);
        config(['mail.default' => 'log']);
        $this->assertSame('not_ready', app(CommercialReminderDispatcher::class)->dispatch());
        $this->assertDatabaseCount('commercial_reminder_runs', 0);
        Mail::assertNothingSent();
    }

    public function test_recipient_role_is_revalidated_and_never_falls_back_to_another_root(): void
    {
        [$root] = $this->setupReminder();
        Mail::fake();
        User::factory()->create(['role' => 'super_admin']);
        $root->forceFill(['role' => 'admin'])->save();
        $this->assertSame('not_ready', app(CommercialReminderDispatcher::class)->dispatch());
        $this->assertDatabaseCount('commercial_reminder_runs', 0);
        Mail::assertNothingSent();
    }

    public function test_local_delivery_window_skips_early_and_late_runs(): void
    {
        $this->setupReminder();
        Mail::fake();
        foreach (['2027-01-31 08:59:59', '2027-01-31 21:00:00'] as $time) {
            $this->travelTo(Carbon::parse($time));
            $this->assertSame('outside_hours', app(CommercialReminderDispatcher::class)->dispatch());
        }Mail::assertNothingSent();
        $this->assertDatabaseCount('commercial_reminder_runs', 0);
    }

    public function test_renewed_or_cancelled_memberships_are_not_in_a_fresh_digest(): void
    {
        [,$sub] = $this->setupReminder();
        Mail::fake();
        $sub->update(['paid_until' => '2027-03-01 10:00:00']);
        $this->assertSame('empty', app(CommercialReminderDispatcher::class)->dispatch());
        $this->travelTo(Carbon::parse('2027-02-01 09:00:00'));
        $sub->update(['paid_until' => '2027-02-02 10:00:00', 'cancel_at_period_end' => true]);
        $this->assertSame('empty', app(CommercialReminderDispatcher::class)->dispatch());
        Mail::assertNothingSent();
    }

    public function test_transport_failure_stays_visible_and_is_not_automatically_retried(): void
    {
        [$root] = $this->setupReminder();
        Mail::shouldReceive('to')->once()->with($root->email)->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('private SMTP detail'));
        $this->assertSame('failed', app(CommercialReminderDispatcher::class)->dispatch());
        $this->assertSame('already_attempted', app(CommercialReminderDispatcher::class)->dispatch());
        $this->assertDatabaseHas('commercial_reminder_runs', ['status' => 'failed', 'error_code' => 'delivery_failed']);
        $this->actingAs($root)->get('/gestion-comercial/recordatorios')->assertOk()->assertSee('El transporte no confirmó el envío')->assertDontSee('private SMTP detail');
    }

    public function test_a_claim_left_by_an_interrupted_process_is_not_retried(): void
    {
        [$root] = $this->setupReminder();
        Mail::fake();
        DB::table('commercial_reminder_runs')->insert(['day' => '2027-01-31', 'recipient_user_id' => $root->id, 'status' => 'sending', 'item_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame('already_attempted', app(CommercialReminderDispatcher::class)->dispatch());
        Mail::assertNothingSent();
        $this->actingAs($root)->get('/gestion-comercial/recordatorios')->assertOk()->assertSee('Envío sin confirmar');
    }

    public function test_large_digest_is_bounded_and_links_to_the_full_private_list(): void
    {
        $this->setupReminder();
        Mail::fake();
        for ($i = 0; $i < 100; $i++) {
            $owner = User::factory()->create(['role' => 'client']);
            CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'kind' => 'coaching', 'plan_code' => 'asesoria', 'amount_cents' => 6990000, 'currency' => 'ARS', 'billing_mode' => 'manual', 'status' => 'active', 'paid_until' => '2027-02-02 10:00:00', 'terms_version' => 'test']);
        }$this->artisan('memberships:remind')->assertSuccessful();
        Mail::assertSent(MembershipReminderDigest::class, fn ($mail) => $mail->reminders->count() === 100 && $mail->total === 101 && str_contains($mail->render(), '101'));
    }

    public static function revokedBeforeHandoff(): array
    {
        return ['role removed' => ['role'], 'account deleted' => ['delete'], 'opted out' => ['optout'], 'recipient changed' => ['recipient']];
    }

    #[DataProvider('revokedBeforeHandoff')]
    public function test_authorization_is_current_at_mail_handoff(string $change): void
    {
        [$root] = $this->setupReminder();
        $replacement = User::factory()->create(['role' => 'super_admin']);
        Mail::fake();
        $changed = false;
        CommercialSubscription::retrieved(function () use ($root, $replacement, $change, &$changed) {
            if ($changed) {
                return;
            }
            $changed = true;
            match ($change) {
                'role' => $root->forceFill(['role' => 'admin'])->save(),
                'delete' => $root->delete(),
                'optout' => CommercialSettings::saveValues(['reminders_enabled' => false]),
                'recipient' => CommercialSettings::saveValues(['reminder_recipient_id' => $replacement->id]),
            };
        });
        $this->assertSame('not_ready', app(CommercialReminderDispatcher::class)->dispatch());
        Mail::assertNothingSent();
        $this->assertDatabaseHas('commercial_reminder_runs', ['status' => 'revoked']);
    }

    public function test_handoff_uses_the_authorized_accounts_current_email(): void
    {
        [$root] = $this->setupReminder();
        Mail::fake();
        $changed = false;
        CommercialSubscription::retrieved(function () use ($root, &$changed) {
            if ($changed) {
                return;
            }
            $changed = true;
            $root->forceFill(['email' => 'actualizado@example.test'])->save();
        });
        $this->assertSame('sent', app(CommercialReminderDispatcher::class)->dispatch());
        Mail::assertSent(MembershipReminderDigest::class, fn ($mail) => $mail->hasTo('actualizado@example.test'));
    }

    public static function nondeliveringUrls(): array
    {
        return ['URL selects log' => ['log://default'], 'URL selects array' => ['array://default']];
    }

    #[DataProvider('nondeliveringUrls')]
    public function test_url_selected_nondelivery_does_not_consume_a_daily_attempt(string $url): void
    {
        $this->setupReminder();
        Mail::fake();
        config(['mail.mailers.smtp.url' => $url]);
        $this->assertSame('not_ready', app(CommercialReminderDispatcher::class)->dispatch());
        $this->assertDatabaseCount('commercial_reminder_runs', 0);
        Mail::assertNothingSent();
    }
}
