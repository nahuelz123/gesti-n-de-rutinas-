<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminChatOversight;
use App\Filament\Pages\Chat;
use App\Models\Gym;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_chat_loads_the_latest_hundred_messages_and_can_expand_history(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        foreach (range(1, 105) as $number) {
            Message::create([
                'gym_id' => $gym->id,
                'sender_id' => $coach->id,
                'recipient_id' => $client->id,
                'body' => "Mensaje {$number}",
            ]);
        }

        $this->actingAs($coach);

        $page = Livewire::test(Chat::class)
            ->call('selectClient', $client->id);

        $messages = $page->instance()->getMessagesProperty();
        $this->assertCount(100, $messages);
        $this->assertSame('Mensaje 6', $messages->first()->body);
        $this->assertSame('Mensaje 105', $messages->last()->body);

        $page->call('loadOlderMessages');
        $this->assertCount(105, $page->instance()->getMessagesProperty());
    }

    public function test_chat_oversight_groups_messages_in_a_conversation(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $admin = User::factory()->create(['role' => 'admin', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        foreach (['Primero', 'Segundo', 'Último'] as $body) {
            Message::create([
                'gym_id' => $gym->id,
                'sender_id' => $admin->id,
                'recipient_id' => $client->id,
                'body' => $body,
            ]);
        }

        $this->actingAs($admin);

        $conversations = Livewire::test(AdminChatOversight::class)
            ->instance()
            ->getConversationsProperty();

        $this->assertCount(1, $conversations);
        $this->assertSame('Último', $conversations->first()['last_message']);
        $this->assertSame(3, $conversations->first()['count']);
    }

    public function test_client_chat_can_page_older_messages(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        foreach (range(1, 105) as $number) {
            Message::create([
                'gym_id' => $gym->id,
                'sender_id' => $coach->id,
                'recipient_id' => $client->id,
                'body' => "Mensaje {$number}",
            ]);
        }

        $this->actingAs($client)
            ->get(route('client.chat.index'))
            ->assertOk()
            ->assertSee('Mensaje 6')
            ->assertSee('Mensaje 105')
            ->assertDontSee('Mensaje 5');

        $response = $this->getJson(route('client.chat.fetch', ['before_id' => 6]));
        $response->assertOk()
            ->assertJsonPath('messages.0.body', 'Mensaje 1')
            ->assertJsonPath('messages.4.body', 'Mensaje 5')
            ->assertJsonPath('has_older', false);
    }
}
