<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Gym;
use App\Models\User;
use App\Services\DeepSeekClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAiChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_chat_history_is_loaded_in_bounded_pages_for_the_current_client(): void
    {
        $gym = Gym::create(['name' => 'Gym history']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $otherClient = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        foreach (range(1, 105) as $number) {
            AiConversation::create([
                'user_id' => $client->id,
                'role' => $number % 2 ? 'user' : 'assistant',
                'content' => "Mensaje {$number}",
            ]);
        }

        AiConversation::create([
            'user_id' => $otherClient->id,
            'role' => 'user',
            'content' => 'Privado de otro cliente',
        ]);

        $this->actingAs($client)->get(route('client.ai-chat.index'))
            ->assertOk()
            ->assertViewHas('hasOlder', true)
            ->assertViewHas('history', fn ($history) => $history->count() === 100
                && $history->first()->content === 'Mensaje 6'
                && $history->last()->content === 'Mensaje 105');

        $this->actingAs($client)->getJson(route('client.ai-chat.history', ['before_id' => 7]))
            ->assertOk()
            ->assertJsonPath('messages.0.content', 'Mensaje 1')
            ->assertJsonPath('messages.5.content', 'Mensaje 6')
            ->assertJsonPath('has_older', false)
            ->assertDontSee('Privado de otro cliente');
    }

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
