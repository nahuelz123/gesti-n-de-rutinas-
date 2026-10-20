<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Gym;
use App\Models\PushSubscription;
use App\Models\RestAlert;
use App\Models\Routine;
use App\Models\User;
use App\Services\PushDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestAlertTest extends TestCase
{
    use RefreshDatabase;

    private function workout(): array
    {
        $gym = Gym::create(['name' => 'Push gym']);
        $user = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $user->id, 'title' => 'Routine']);
        $assignment = Assignment::create(['gym_id' => $gym->id, 'client_id' => $user->id, 'routine_id' => $routine->id, 'status' => 'active', 'assigned_at' => now()]);

        return [$user, $assignment];
    }

    private function subscription(User $user): PushSubscription
    {
        return PushSubscription::create(['user_id' => $user->id, 'endpoint_hash' => hash('sha256', 'test-'.$user->id), 'subscription' => ['endpoint' => 'https://web.push.apple.com/test', 'keys' => ['p256dh' => 'key', 'auth' => 'auth']]]);
    }

    public function test_only_owner_can_schedule_rest_and_subscription_must_belong_to_owner(): void
    {
        [$user, $assignment] = $this->workout();
        $other = User::factory()->create(['role' => 'client', 'gym_id' => $user->gym_id]);
        $subscription = $this->subscription($user);
        $payload = ['subscription_id' => $subscription->id, 'sequence' => 100, 'deadline' => now()->addMinute()->getTimestampMs()];
        $this->actingAs($other)->putJson('/app/rest-alerts/'.$assignment->id, $payload)->assertNotFound();
        $foreignSubscription = $this->subscription($other);
        $this->actingAs($user)->putJson('/app/rest-alerts/'.$assignment->id, [...$payload, 'subscription_id' => $foreignSubscription->id])->assertNotFound();
        $this->putJson('/app/rest-alerts/'.$assignment->id, $payload)->assertOk();
        $this->assertDatabaseCount('rest_alerts', 1);
    }

    public function test_cancellation_and_restart_ignore_stale_or_duplicate_requests(): void
    {
        [$user, $assignment] = $this->workout();
        $subscription = $this->subscription($user);
        $this->actingAs($user);
        $url = '/app/rest-alerts/'.$assignment->id;
        $payload = ['subscription_id' => $subscription->id, 'sequence' => 100, 'deadline' => now()->addMinute()->getTimestampMs()];
        $this->putJson($url, $payload)->assertOk();
        $this->putJson($url, [...$payload, 'sequence' => 101, 'deadline' => null])->assertOk();
        $this->putJson($url, $payload)->assertOk();
        $this->assertSame('cancelled', RestAlert::first()->status);
        $this->putJson($url, [...$payload, 'sequence' => 102])->assertOk();
        $deadline = RestAlert::first()->due_at;
        $this->travel(10)->seconds();
        $this->putJson($url, [...$payload, 'sequence' => 102])->assertOk();
        $this->assertTrue(RestAlert::first()->due_at->equalTo($deadline));
        $this->assertDatabaseCount('rest_alerts', 1);
    }

    public function test_subscription_rejects_internal_arbitrary_and_redirect_endpoints(): void
    {
        [$user] = $this->workout();
        foreach (['http://web.push.apple.com/test', 'https://127.0.0.1/test', 'https://evil.test/test', 'https://web.push.apple.com.evil.test/test', 'https://web.push.apple.com:444/test', 'https://user@web.push.apple.com/test'] as $endpoint) {
            $this->actingAs($user)->postJson('/app/push/subscriptions', ['endpoint' => $endpoint, 'keys' => ['p256dh' => str_repeat('a', 87), 'auth' => str_repeat('b', 22)]])->assertUnprocessable();
        }
    }

    public function test_worker_sends_due_alert_once_and_does_not_send_cancelled_or_expired_alerts(): void
    {
        [$user, $assignment] = $this->workout();
        $subscription = $this->subscription($user);
        $alert = RestAlert::create(['push_subscription_id' => $subscription->id, 'assignment_id' => $assignment->id, 'sequence' => 100, 'due_at' => now()->subSecond(), 'status' => 'pending', 'attempts' => 0]);
        $delivery = $this->mock(PushDelivery::class);
        $delivery->shouldReceive('send')->once()->andReturn('sent');
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        $this->assertSame('sent', $alert->fresh()->status);
        $alert->update(['status' => 'cancelled']);
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        $alert->update(['status' => 'pending', 'due_at' => now()->subMinutes(5)]);
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        $this->assertSame('expired', $alert->fresh()->status);
    }

    public function test_worker_retries_failures_and_removes_expired_subscriptions(): void
    {
        [$user, $assignment] = $this->workout();
        $subscription = $this->subscription($user);
        $alert = RestAlert::create(['push_subscription_id' => $subscription->id, 'assignment_id' => $assignment->id, 'sequence' => 100, 'due_at' => now()->subSecond(), 'status' => 'pending', 'attempts' => 0]);
        $delivery = $this->mock(PushDelivery::class);
        $delivery->shouldReceive('send')->once()->andReturn('retry');
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        $this->assertSame('pending', $alert->fresh()->status);
        $this->assertSame(1, $alert->fresh()->attempts);
        $this->travel(6)->seconds();
        $delivery->shouldReceive('send')->once()->andReturn('expired');
        $this->artisan('rest-alerts:work', ['--once' => true])->assertSuccessful();
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
    }

    public function test_same_device_account_switch_cancels_old_alert_and_rebinds_subscription(): void
    {
        [$user, $assignment] = $this->workout();
        $subscription = $this->subscription($user);
        $data = ['endpoint' => 'https://web.push.apple.com/test', 'keys' => ['p256dh' => rtrim(strtr(base64_encode("\x04".str_repeat('a', 64)), '+/', '-_'), '='), 'auth' => rtrim(strtr(base64_encode(str_repeat('b', 16)), '+/', '-_'), '=')]];
        $subscription->update(['endpoint_hash' => hash('sha256', $data['endpoint']), 'subscription' => $data]);
        RestAlert::create(['push_subscription_id' => $subscription->id, 'assignment_id' => $assignment->id, 'sequence' => 100, 'due_at' => now()->addMinute(), 'status' => 'pending', 'attempts' => 0]);
        $other = User::factory()->create(['role' => 'client', 'gym_id' => $user->gym_id]);
        $this->actingAs($other)->postJson('/app/push/subscriptions', $data)->assertOk();
        $this->assertSame($other->id, $subscription->fresh()->user_id);
        $this->assertSame('cancelled', RestAlert::first()->status);
        $this->deleteJson('/app/push/subscriptions', ['endpoint' => $data['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_subscription_keys_are_encrypted_and_vapid_identity_is_shared_and_stable(): void
    {
        [$user] = $this->workout();
        $data = ['endpoint' => 'https://web.push.apple.com/new-device', 'keys' => ['p256dh' => rtrim(strtr(base64_encode("\x04".str_repeat('c', 64)), '+/', '-_'), '='), 'auth' => rtrim(strtr(base64_encode(str_repeat('d', 16)), '+/', '-_'), '=')]];
        $this->actingAs($user)->postJson('/app/push/subscriptions', $data)->assertOk();
        $subscription = PushSubscription::first();
        $this->assertSame($data, $subscription->subscription);
        $this->assertStringNotContainsString($data['endpoint'], $subscription->getRawOriginal('subscription'));
        $first = $this->getJson('/app/push/key')->assertOk()->json('publicKey');
        $this->assertSame($first, $this->getJson('/app/push/key')->assertOk()->json('publicKey'));
        $this->assertCount(1, DB::table('web_push_keys')->get());
        $this->assertStringNotContainsString($first, DB::table('web_push_keys')->value('keys'));
    }

    public function test_transport_delay_does_not_extend_the_absolute_rest_deadline(): void
    {
        [$user, $assignment] = $this->workout();
        $subscription = $this->subscription($user);
        $deadline = now()->addMinute()->startOfSecond();
        $this->travel(10)->seconds();
        $this->actingAs($user)->putJson('/app/rest-alerts/'.$assignment->id, ['subscription_id' => $subscription->id, 'sequence' => 100, 'deadline' => $deadline->getTimestampMs()])->assertOk();
        $this->assertTrue(RestAlert::first()->due_at->equalTo($deadline));
    }

    public function test_logout_cancels_only_this_sessions_push_device_even_without_javascript(): void
    {
        [$user, $assignment] = $this->workout();
        $subscription = $this->subscription($user);
        $otherDevice = PushSubscription::create(['user_id' => $user->id, 'endpoint_hash' => hash('sha256', 'second-device'), 'subscription' => $subscription->subscription]);
        RestAlert::create(['push_subscription_id' => $subscription->id, 'assignment_id' => $assignment->id, 'sequence' => 100, 'due_at' => now()->addMinute(), 'status' => 'pending']);
        $this->actingAs($user)->withSession(['push_subscription_id' => $subscription->id])->post(route('logout'))->assertRedirect();
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $otherDevice->id]);
        $this->assertDatabaseCount('rest_alerts', 0);
    }

    public function test_guest_cannot_use_push_api(): void
    {
        $this->getJson('/app/push/key')->assertUnauthorized();
    }
}
