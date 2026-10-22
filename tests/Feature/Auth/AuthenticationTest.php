<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_remembered_login_with_a_valid_csrf_token_issues_a_recaller_cookie(): void
    {
        $this->app['env'] = 'local'; // Exercise CSRF middleware, which testing normally bypasses.
        $user = User::factory()->create();

        $this->withSession(['_token' => 'valid-login-token'])
            ->post(route('login.store'), [
                '_token' => 'valid-login-token',
                'email' => $user->email,
                'password' => 'password',
                'remember' => 'on',
            ])
            ->assertRedirect('/app')
            ->assertCookie(auth()->guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_login_session_can_refresh_an_expired_forms_token_without_being_cached(): void
    {
        $response = $this->getJson('/auth/session');

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertSame(session()->token(), $response->json('token'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_login_session_restores_remembered_authentication_before_returning_the_token(): void
    {
        $user = User::factory()->create(['remember_token' => 'remembered-user-token']);
        $recaller = auth()->guard()->getRecallerName();

        $response = $this->withCredentials()->withCookie($recaller, $user->id.'|'.$user->remember_token.'|'.$user->password)
            ->getJson('/auth/session');

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(session()->token(), $response->json('token'));
    }

    public function test_expired_login_preserves_email_and_remember_choice_but_never_the_password(): void
    {
        $this->app['env'] = 'local';

        $this->withSession(['_token' => 'current-token'])
            ->post(route('login.store'), [
                '_token' => 'expired-token',
                'email' => 'client@example.test',
                'password' => 'private-password',
                'remember' => 'on',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasInput('email', 'client@example.test')
            ->assertSessionHasInput('remember', 'on')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input._token')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_csrf_token_still_rejects_json_login_requests(): void
    {
        $this->app['env'] = 'local';
        $user = User::factory()->create();

        $this->withSession(['_token' => 'current-token'])
            ->postJson(route('login.store'), [
                '_token' => 'expired-token',
                'email' => $user->email,
                'password' => 'password',
                'remember' => 'on',
            ])->assertStatus(419);

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
