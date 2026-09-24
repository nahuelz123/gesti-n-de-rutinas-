<?php

namespace Tests\Feature\Auth;

use App\Models\Gym;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $gym = Gym::create(['name' => 'Test Gym', 'active' => true]);
        $response = $this->get(route('gym-join.show', $gym->invite_code));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $gym = Gym::create(['name' => 'Test Gym', 'active' => true]);
        $response = $this->post(route('gym-join.register', $gym->invite_code), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('client.dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'gym_id' => $gym->id, 'role' => 'client']);
    }
}
