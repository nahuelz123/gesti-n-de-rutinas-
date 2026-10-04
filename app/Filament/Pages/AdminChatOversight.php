<?php

namespace App\Filament\Pages;

use App\Models\Message;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AdminChatOversight extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $navigationLabel = 'Conversaciones';

    protected static ?string $title = 'Supervisión de chats';

    protected string $view = 'filament.pages.admin-chat-oversight';

    public ?string $selectedKey = null;

    public string $search = '';

    public int $threadLimit = 100;

    /**
     * Agrupa todos los mensajes del gym en conversaciones únicas coach↔cliente,
     * ordenadas por el mensaje más reciente.
     */
    public function getConversationsProperty(): Collection
    {
        $user = Auth::user();

        $personA = 'CASE WHEN sender_id < recipient_id THEN sender_id ELSE recipient_id END';
        $personB = 'CASE WHEN sender_id < recipient_id THEN recipient_id ELSE sender_id END';

        // Agrupar en SQL evita traer y crear un modelo por cada mensaje del gimnasio.
        $threads = Message::query()
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id))
            ->selectRaw("{$personA} AS person_a, {$personB} AS person_b, MAX(id) AS latest_message_id, COUNT(*) AS message_count")
            ->groupByRaw("{$personA}, {$personB}")
            ->orderByDesc('latest_message_id')
            ->get();

        $latestMessages = Message::query()
            ->with(['sender', 'recipient'])
            ->whereIn('id', $threads->pluck('latest_message_id'))
            ->get()
            ->keyBy('id');

        $conversations = $threads->map(function ($thread) use ($latestMessages) {
            $last = $latestMessages->get($thread->latest_message_id);

            if (! $last || ! $last->sender || ! $last->recipient) {
                return null;
            }

            $isSenderStaff = in_array($last->sender->role, ['coach', 'admin', 'super_admin']);
            $coach = $isSenderStaff ? $last->sender : $last->recipient;
            $client = $isSenderStaff ? $last->recipient : $last->sender;

            return [
                'key' => $this->pairKey($last->sender_id, $last->recipient_id),
                'coach' => $coach,
                'client' => $client,
                'last_message' => $last->body,
                'last_at' => $last->created_at,
                'count' => (int) $thread->message_count,
            ];
        })->filter()->values();

        if (trim($this->search) === '') {
            return $conversations;
        }

        $needle = mb_strtolower(trim($this->search));

        return $conversations->filter(
            fn ($conv) => str_contains(mb_strtolower($conv['coach']->name), $needle)
                || str_contains(mb_strtolower($conv['client']->name), $needle)
        )->values();
    }

    public function getThreadProperty(): Collection
    {
        if (! $this->selectedKey) {
            return collect();
        }

        [$idA, $idB] = explode('-', $this->selectedKey);
        $user = Auth::user();

        return Message::query()
            ->betweenUsers((int) $idA, (int) $idB)
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id))
            ->orderByDesc('id')
            ->limit($this->threadLimit)
            ->get()
            ->reverse()
            ->values();
    }

    public function selectConversation(string $key): void
    {
        $this->selectedKey = $key;
        $this->threadLimit = 100;
    }

    public function loadOlderMessages(): void
    {
        $this->threadLimit = min($this->threadLimit + 100, 2000);
    }

    private function pairKey(int $a, int $b): string
    {
        return $a < $b ? "{$a}-{$b}" : "{$b}-{$a}";
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin']);
    }
}
