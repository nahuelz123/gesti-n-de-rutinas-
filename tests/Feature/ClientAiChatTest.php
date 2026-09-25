<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\User;
use App\Services\DeepSeekClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAiChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_receives_messages_without_a_redirect_and_can_clear_them(): void
    {
        $gym = Gym::create(['name' => 'Gym chat']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $this->mock(DeepSeekClient::class)->shouldReceive('chat')->once()->andReturn('¡Hola!');

        $this->actingAs($client)->postJson(route('client.ai-chat.send'), ['message' => 'Hola'])
            ->assertOk()
            ->assertJsonPath('messages.0.content', 'Hola')
            ->assertJsonPath('messages.1.content', '¡Hola!');

        $this->assertDatabaseCount('ai_conversations', 2);

        $this->actingAs($client)->postJson(route('client.ai-chat.reset'))
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseCount('ai_conversations', 0);
    }
}
